<?php

require("../connection.php");

function redirect_transaction($code, $message = "")
{
    $url = "../../../pages/transaction?code=" . $code;

    if ($message !== "") {
        $url .= "&msg=" . rawurlencode($message);
    }

    header("Location: " . $url);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST"
    || !isset($_POST["create_new_transaction"])) {
    redirect_transaction(501, "Permintaan transaksi tidak valid.");
}

// Validasi input dasar.
$item_id = filter_var(
    $_POST["itemId"] ?? null,
    FILTER_VALIDATE_INT
);

$transaction_id = trim($_POST["transactionId"] ?? "");
$amount_raw = $_POST["amountOfItem"] ?? "";
$cash_raw = trim($_POST["cash"] ?? "");

$amount = filter_var($amount_raw, FILTER_VALIDATE_INT);

// Input uang menerima format seperti 45000 atau 45.000.
$cash_digits = preg_replace('/\D/', '', $cash_raw);
$cash = ($cash_digits !== "")
    ? filter_var($cash_digits, FILTER_VALIDATE_INT)
    : false;

if (
    $item_id === false || $item_id < 1 ||
    $amount === false || $amount < 1 ||
    $amount > 1000000 ||
    $transaction_id === "" ||
    strlen($transaction_id) > 15 ||
    $cash === false
) {
    redirect_transaction(
        501,
        "Input tidak valid. Periksa ID transaksi, jumlah barang, dan pembayaran."
    );
}

$in_transaction = false;

try {
    $conn->begin_transaction();
    $in_transaction = true;

    // Cari barang POS untuk mendapatkan SKU.
    $item_stmt = $conn->prepare(
        "SELECT item_id_txt
         FROM items
         WHERE item_id = ?"
    );
    $item_stmt->bind_param("i", $item_id);
    $item_stmt->execute();

    $item_result = $item_stmt->get_result();
    $item = $item_result->fetch_assoc();
    $item_stmt->close();

    if (!$item) {
        throw new Exception("Barang POS tidak ditemukan.");
    }

    $sku = $item["item_id_txt"];

    // Kunci baris produk agar stok tidak diproses bersamaan
    // oleh transaksi lain sebelum transaksi ini selesai.
    $product_stmt = $conn->prepare(
        "SELECT id, price, stock
         FROM inventory_management.products
         WHERE sku = ?
         FOR UPDATE"
    );
    $product_stmt->bind_param("s", $sku);
    $product_stmt->execute();

    $product_result = $product_stmt->get_result();
    $product = $product_result->fetch_assoc();
    $product_stmt->close();

    if (!$product) {
        throw new Exception(
            "SKU " . $sku . " tidak ditemukan di Inventory."
        );
    }

    $stock = (int) $product["stock"];
    $price = (int) round((float) $product["price"]);

    if ($price < 0) {
        throw new Exception("Harga produk tidak valid.");
    }

    if ($stock < $amount) {
        throw new Exception(
            "Stok tidak cukup. Stok tersedia: " . $stock .
            ", jumlah diminta: " . $amount . "."
        );
    }

    $total = $price * $amount;

    if ($cash < $total) {
        throw new Exception(
            "Uang pembayaran kurang. Total belanja: " .
            number_format($total, 0, ",", ".") . "."
        );
    }

    $change = $cash - $total;
    $created_at = date("m/d/Y h:i:s a");

    // Kurangi stok dengan syarat stok tidak menjadi negatif.
    $stock_stmt = $conn->prepare(
        "UPDATE inventory_management.products
         SET stock = stock - ?
         WHERE id = ? AND stock >= ?"
    );
    $product_id = (int) $product["id"];
    $stock_stmt->bind_param(
        "iii",
        $amount,
        $product_id,
        $amount
    );
    $stock_stmt->execute();

    if ($stock_stmt->affected_rows !== 1) {
        $stock_stmt->close();
        throw new Exception(
            "Stok berubah atau tidak mencukupi. Silakan coba lagi."
        );
    }

    $stock_stmt->close();

    // Simpan transaksi dengan angka yang dihitung oleh server.
    $total_text = number_format($total, 0, ",", ".");
    $cash_text = number_format($cash, 0, ",", ".");
    $change_text = number_format($change, 0, ",", ".");
    $amount_text = (string) $amount;

    $transaction_stmt = $conn->prepare(
        "INSERT INTO transactions
         (
             item_id,
             transaction_id_txt,
             transaction_create_at,
             transaction_amount,
             transaction_total,
             transaction_cash,
             transaction_money_changes
         )
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $transaction_stmt->bind_param(
        "issssss",
        $item_id,
        $transaction_id,
        $created_at,
        $amount_text,
        $total_text,
        $cash_text,
        $change_text
    );

    $transaction_stmt->execute();
    $transaction_stmt->close();

    // Stok dan transaksi berhasil disimpan bersama.
    $conn->commit();
    $in_transaction = false;

    redirect_transaction(200);

} catch (Throwable $e) {
    if ($in_transaction) {
        $conn->rollback();
    }

    error_log("POS transaction failed: " . $e->getMessage());

    // Tampilkan pesan validasi yang aman kepada pengguna.
    $message = $e->getMessage();

    redirect_transaction(501, $message);
}
