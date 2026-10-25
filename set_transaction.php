<?php

require_once __DIR__ . "/../connection.php";
require_once __DIR__ . "/../getter/get_items.php";

function redirect_transaction($type, $message)
{
    header(
        "Location: ../../../pages/transaction/index.php?" .
        $type . "=" . urlencode($message)
    );
    exit;
}

/**
 * Request ke Inventory API.
 */
function inventory_api_request(
    $method,
    $endpoint,
    $payload = null,
    $idempotency_key = null
) {
    $api_url = "http://inventory-api" . $endpoint;
    $last_error = "Inventory API tidak dapat dihubungi.";

    for ($attempt = 1; $attempt <= 3; $attempt++) {
        $ch = curl_init($api_url);
        $headers = ["Accept: application/json"];

        if ($payload !== null) {
            $headers[] = "Content-Type: application/json";
        }

        if ($idempotency_key !== null) {
            $headers[] = "Idempotency-Key: " . $idempotency_key;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 10,
        ]);

        if ($payload !== null) {
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                json_encode($payload)
            );
        }

        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (
            $response !== false &&
            $http_code >= 200 &&
            $http_code < 300
        ) {
            $decoded = json_decode($response, true);

            if (!is_array($decoded)) {
                throw new RuntimeException(
                    "Respons Inventory API tidak valid."
                );
            }

            return $decoded;
        }

        $last_error = $response === false
            ? "Koneksi Inventory gagal: " . $curl_error
            : "Inventory API HTTP " . $http_code .
              ". Respons: " . substr((string) $response, 0, 300);

        if (
            $response !== false &&
            $http_code >= 400 &&
            $http_code < 500 &&
            $http_code !== 408 &&
            $http_code !== 429
        ) {
            throw new RuntimeException($last_error);
        }
    }

    throw new RuntimeException($last_error);
}

/**
 * Validasi respons penjualan Inventory.
 */
function validate_sale_response($response, $sku, $quantity)
{
    if (($response["status"] ?? "") !== "success") {
        throw new RuntimeException(
            "Penjualan SKU " . $sku . " tidak dikonfirmasi."
        );
    }

    $data = $response["sale"] ?? $response["data"] ?? null;

    if (!is_array($data)) {
        throw new RuntimeException(
            "Data penjualan Inventory tidak valid."
        );
    }

    $response_sku = (string) ($data["sku"] ?? "");
    $response_quantity = (int) ($data["quantity"] ?? 0);
    $unit_price = (float) ($data["unit_price"] ?? -1);
    $total = (float) ($data["total"] ?? -1);

    if (
        $response_sku !== $sku ||
        $response_quantity !== $quantity ||
        $unit_price < 0 ||
        $total < 0 ||
        abs($total - ($unit_price * $quantity)) > 0.01
    ) {
        throw new RuntimeException(
            "Data penjualan SKU " . $sku . " tidak sesuai."
        );
    }

    return [
        "sku" => $response_sku,
        "name" => (string) ($data["name"] ?? ""),
        "quantity" => $response_quantity,
        "unit_price" => $unit_price,
        "total" => $total,
        "remaining_stock" => (int) ($data["remaining_stock"] ?? 0),
    ];
}

/**
 * Kompensasi stok jika penyimpanan POS gagal.
 */
function compensate_sales($confirmed_sales, $transaction_key)
{
    $all_compensated = true;

    foreach (array_reverse($confirmed_sales) as $sale) {
        try {
            $response = inventory_api_request(
                "POST",
                "/api/sales/compensate",
                ["sale_key" => $sale["sale_key"]],
                "comp-" . $transaction_key . "-" . $sale["sku"]
            );

            if (($response["status"] ?? "") !== "success") {
                $all_compensated = false;
                error_log(
                    "Kompensasi gagal untuk SKU " . $sale["sku"]
                );
            }
        } catch (Throwable $e) {
            $all_compensated = false;
            error_log(
                "Kompensasi gagal: " . $e->getMessage()
            );
        }
    }

    return $all_compensated;
}

/**
 * Request harus POST.
 */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect_transaction("error", "Metode request tidak valid.");
}

$transaction_key = trim(
    (string) ($_POST["transaction_id_txt"] ?? "")
);

$cash_input = trim(
    (string) ($_POST["transaction_cash"] ?? "")
);

$cart_json = (string) ($_POST["cart_json"] ?? "");

if (
    $transaction_key === "" ||
    strlen($transaction_key) > 15 ||
    !preg_match('/^[A-Za-z0-9_-]+$/', $transaction_key)
) {
    redirect_transaction(
        "error",
        "ID transaksi tidak valid. Maksimal 15 karakter."
    );
}

/**
 * Normalisasi nominal pembayaran.
 */
$cash_normalized = preg_replace(
    '/^(?:Rp\.?\s*)/i',
    '',
    $cash_input
);

$cash_normalized = preg_replace(
    '/[\s.,]/',
    '',
    $cash_normalized
);

if (
    $cash_normalized === "" ||
    !ctype_digit($cash_normalized)
) {
    redirect_transaction(
        "error",
        "Nominal uang bayar tidak valid."
    );
}

$cash = (float) $cash_normalized;

$cart = json_decode($cart_json, true);

if (!is_array($cart) || count($cart) === 0) {
    redirect_transaction("error", "Keranjang masih kosong.");
}

/**
 * Ambil katalog dari Inventory API.
 */
try {
    $inventory_products = get_inventory_products();
} catch (Throwable $e) {
    error_log(
        "Gagal mengambil katalog Inventory: " . $e->getMessage()
    );

    redirect_transaction(
        "error",
        "Katalog Inventory sedang tidak tersedia."
    );
}

if (count($inventory_products) === 0) {
    redirect_transaction(
        "error",
        "Produk Inventory kosong atau API tidak tersedia."
    );
}

$catalog = [];

foreach ($inventory_products as $product) {
    $sku = trim((string) ($product["item_id_txt"] ?? ""));

    if ($sku !== "") {
        $catalog[$sku] = $product;
    }
}

/**
 * Sinkronisasi produk Inventory yang belum ada di POS.
 * Produk yang sudah ada tidak dibuat ulang sehingga item_id lama tetap.
 */
$local_items = get_items($conn);
$local_items_by_sku = [];

foreach ($local_items as $local_item) {
    $sku = trim((string) ($local_item["item_id_txt"] ?? ""));

    if ($sku !== "") {
        $local_items_by_sku[$sku] = $local_item;
    }
}

$check_stmt = mysqli_prepare(
    $conn,
    "SELECT item_id
     FROM items
     WHERE item_id_txt = ?
     LIMIT 1"
);

$insert_stmt = mysqli_prepare(
    $conn,
    "INSERT INTO items (
        item_id_txt,
        item_name,
        item_price,
        create_at
    ) VALUES (?, ?, ?, ?)"
);

if (!$check_stmt || !$insert_stmt) {
    redirect_transaction(
        "error",
        "Gagal menyiapkan sinkronisasi produk POS."
    );
}

foreach ($catalog as $sku => $product) {
    if (isset($local_items_by_sku[$sku])) {
        continue;
    }

    $name = trim((string) ($product["item_name"] ?? ""));
    $price = (string) ($product["item_price"] ?? "");

    if (
        $name === "" ||
        !is_numeric($price) ||
        (float) $price < 0
    ) {
        error_log(
            "Produk Inventory tidak valid untuk sinkronisasi: " . $sku
        );
        continue;
    }

    $created_at = date("m/d/Y h:i:s a");

    // Periksa ulang SKU untuk mengurangi risiko duplikasi.
    mysqli_stmt_bind_param($check_stmt, "s", $sku);

    if (!mysqli_stmt_execute($check_stmt)) {
        error_log(
            "Gagal memeriksa SKU " . $sku . ": " .
            mysqli_stmt_error($check_stmt)
        );
        continue;
    }

    $check_result = mysqli_stmt_get_result($check_stmt);

    if ($check_result && mysqli_num_rows($check_result) > 0) {
        continue;
    }

    mysqli_stmt_bind_param(
        $insert_stmt,
        "ssss",
        $sku,
        $name,
        $price,
        $created_at
    );

    if (!mysqli_stmt_execute($insert_stmt)) {
        error_log(
            "Gagal sinkron SKU " . $sku . ": " .
            mysqli_stmt_error($insert_stmt)
        );
    }
}

mysqli_stmt_close($check_stmt);
mysqli_stmt_close($insert_stmt);

/**
 * Muat ulang mapping agar item_id baru tersedia.
 */
$local_items = get_items($conn);
$local_items_by_sku = [];

foreach ($local_items as $local_item) {
    $sku = trim((string) ($local_item["item_id_txt"] ?? ""));

    if ($sku !== "") {
        $local_items_by_sku[$sku] = $local_item;
    }
}

/**
 * Validasi dan gabungkan SKU pada keranjang.
 */
$quantities = [];

foreach ($cart as $cart_item) {
    if (!is_array($cart_item)) {
        redirect_transaction(
            "error",
            "Format keranjang tidak valid."
        );
    }

    $sku = trim((string) (
        $cart_item["sku"] ??
        $cart_item["item_id_txt"] ??
        ""
    ));

    $quantity = filter_var(
        $cart_item["quantity"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        $sku === "" ||
        $quantity === false ||
        $quantity < 1
    ) {
        redirect_transaction(
            "error",
            "SKU atau jumlah produk tidak valid."
        );
    }

    if (!isset($catalog[$sku])) {
        redirect_transaction(
            "error",
            "SKU " . $sku . " tidak ditemukan di Inventory."
        );
    }

    if (!isset($local_items_by_sku[$sku])) {
        redirect_transaction(
            "error",
            "SKU " . $sku .
            " belum berhasil disinkronkan ke POS."
        );
    }

    $quantities[$sku] = ($quantities[$sku] ?? 0) + $quantity;
}

/**
 * Siapkan item berdasarkan ID lokal dan harga katalog Inventory.
 */
$expected_items = [];
$estimated_total = 0;

foreach ($quantities as $sku => $quantity) {
    $product = $catalog[$sku];
    $local_item = $local_items_by_sku[$sku];

    $unit_price = (float) ($product["item_price"] ?? 0);

    if ($unit_price < 0) {
        redirect_transaction(
            "error",
            "Harga SKU " . $sku . " tidak valid."
        );
    }

    $expected_items[] = [
        "item_id" => (int) $local_item["item_id"],
        "sku" => $sku,
        "item_name" => (string) $product["item_name"],
        "quantity" => $quantity,
        "estimated_unit_price" => $unit_price,
    ];

    $estimated_total += $unit_price * $quantity;
}

if ($cash < $estimated_total) {
    redirect_transaction(
        "error",
        "Uang bayar kurang dari perkiraan total transaksi."
    );
}

/**
 * Periksa ID transaksi duplikat.
 */
$stmt = mysqli_prepare(
    $conn,
    "SELECT transaction_id
     FROM transactions
     WHERE transaction_id_txt = ?
     LIMIT 1"
);

if (!$stmt) {
    redirect_transaction(
        "error",
        "Gagal memvalidasi ID transaksi."
    );
}

mysqli_stmt_bind_param($stmt, "s", $transaction_key);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$duplicate_exists = mysqli_num_rows($result) > 0;

mysqli_stmt_close($stmt);

if ($duplicate_exists) {
    redirect_transaction(
        "error",
        "ID transaksi sudah digunakan. Gunakan ID baru."
    );
}

/**
 * Penjualan Inventory.
 */
$confirmed_sales = [];
$actual_items = [];
$actual_total = 0;

try {
    foreach ($expected_items as $item) {
        $sku = $item["sku"];
        $quantity = (int) $item["quantity"];
        $sale_key = "pos-" . $transaction_key . "-" . $sku;

        /*
         * Catat kompensasi SEBELUM validasi respons.
         * Jika API berhasil menjual tetapi respons tidak sesuai,
         * proses pemulihan tetap mengetahui sale_key yang digunakan.
         */
        $response = inventory_api_request(
            "POST",
            "/api/sales",
            [
                "sku" => $sku,
                "quantity" => $quantity,
            ],
            $sale_key
        );

        if (($response["status"] ?? "") === "success") {
            $confirmed_sales[] = [
                "sku" => $sku,
                "sale_key" => $sale_key,
            ];
        }

        $sale = validate_sale_response(
            $response,
            $sku,
            $quantity
        );

        $sale["item_id"] = $item["item_id"];
        $sale["item_name"] = $item["item_name"];

        $actual_items[] = $sale;
        $actual_total += $sale["total"];
    }
} catch (Throwable $e) {
    error_log(
        "Penjualan Inventory gagal untuk " .
        $transaction_key . ": " . $e->getMessage()
    );

    $compensated = compensate_sales(
        $confirmed_sales,
        $transaction_key
    );

    redirect_transaction(
        "error",
        $compensated
            ? "Penjualan Inventory gagal. Periksa stok sebelum mencoba lagi."
            : "Penjualan gagal dan kompensasi belum tuntas. Periksa stok Inventory."
    );
}

/**
 * Validasi total aktual dan kembalian.
 */
if ($cash < $actual_total) {
    $compensated = compensate_sales(
        $confirmed_sales,
        $transaction_key
    );

    redirect_transaction(
        "error",
        $compensated
            ? "Uang bayar kurang dari total aktual Inventory."
            : "Uang bayar kurang dan kompensasi stok belum tuntas. Periksa stok."
    );
}

$change = $cash - $actual_total;
$total_quantity = 0;

foreach ($actual_items as $item) {
    $total_quantity += (int) $item["quantity"];
}

/**
 * Simpan header dan detail secara atomik.
 */
mysqli_begin_transaction($conn);

try {
    $first_item_id = (int) $actual_items[0]["item_id"];

    $transaction_create_at = date("Y-m-d H:i:s");
    $transaction_amount = (string) $total_quantity;
    $transaction_total = number_format($actual_total, 2, ".", "");
    $transaction_cash = number_format($cash, 2, ".", "");
    $transaction_money_changes = number_format($change, 2, ".", "");

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO transactions (
            item_id,
            transaction_id_txt,
            transaction_create_at,
            transaction_amount,
            transaction_total,
            transaction_cash,
            transaction_money_changes
        ) VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        throw new RuntimeException(
            "Gagal menyiapkan header transaksi."
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        "issssss",
        $first_item_id,
        $transaction_key,
        $transaction_create_at,
        $transaction_amount,
        $transaction_total,
        $transaction_cash,
        $transaction_money_changes
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new RuntimeException(
            "Gagal menyimpan header transaksi."
        );
    }

    $transaction_id = (int) mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    $detail_stmt = mysqli_prepare(
        $conn,
        "INSERT INTO transaction_details (
            transaction_id,
            item_id,
            item_name,
            item_price,
            quantity,
            subtotal
        ) VALUES (?, ?, ?, ?, ?, ?)"
    );

    if (!$detail_stmt) {
        throw new RuntimeException(
            "Gagal menyiapkan detail transaksi."
        );
    }

    foreach ($actual_items as $item) {
        $item_id = (int) $item["item_id"];
        $item_name = (string) $item["item_name"];
        $item_price = (float) $item["unit_price"];
        $quantity = (int) $item["quantity"];
        $subtotal = (float) $item["total"];

        mysqli_stmt_bind_param(
            $detail_stmt,
            "iisdid",
            $transaction_id,
            $item_id,
            $item_name,
            $item_price,
            $quantity,
            $subtotal
        );

        if (!mysqli_stmt_execute($detail_stmt)) {
            throw new RuntimeException(
                "Gagal menyimpan detail " . $item_name . "."
            );
        }
    }

    mysqli_stmt_close($detail_stmt);

    if (!mysqli_commit($conn)) {
        throw new RuntimeException(
            "Commit transaksi gagal."
        );
    }
} catch (Throwable $e) {
    mysqli_rollback($conn);

    error_log(
        "Penyimpanan POS gagal untuk " .
        $transaction_key . ": " . $e->getMessage()
    );

    $compensated = compensate_sales(
        $confirmed_sales,
        $transaction_key
    );

    redirect_transaction(
        "error",
        $compensated
            ? "Transaksi gagal disimpan ke POS. Periksa stok sebelum mencoba lagi."
            : "Penyimpanan POS gagal dan kompensasi belum tuntas. Periksa stok."
    );
}

/**
 * Transaksi berhasil.
 */
redirect_transaction(
    "success",
    "Transaksi " . $transaction_key .
    " berhasil. " . count($actual_items) .
    " jenis produk, total Rp " .
    number_format($actual_total, 0, ",", ".") .
    ", kembalian Rp " .
    number_format($change, 0, ",", ".") . "."
);
