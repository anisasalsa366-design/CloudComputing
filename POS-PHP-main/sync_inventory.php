<?php
header('Content-Type: application/json; charset=utf-8');

function respond($status, $message, $extra = [])
{
    http_response_code($status);
    echo json_encode(array_merge([
        'success' => $status >= 200 && $status < 300,
        'message' => $message
    ], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, 'Method not allowed');
}

$expectedToken = getenv('SYNC_TOKEN') ?: '';
$providedToken = $_SERVER['HTTP_X_SYNC_TOKEN'] ?? '';

if (
    $expectedToken === '' ||
    $providedToken === '' ||
    !hash_equals($expectedToken, $providedToken)
) {
    respond(401, 'Unauthorized');
}

$apiUrl = 'http://inventory-api/api/products';
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents($apiUrl, false, $context);

if ($response === false) {
    respond(502, 'Inventory API tidak dapat dihubungi');
}

$payload = json_decode($response, true);

if (
    !is_array($payload) ||
    ($payload['status'] ?? '') !== 'success' ||
    !isset($payload['data']) ||
    !is_array($payload['data'])
) {
    respond(502, 'Respons Inventory API tidak valid');
}

require __DIR__ . '/src/server/connection.php';

if (!isset($conn) || !$conn) {
    respond(500, 'Koneksi database POS gagal');
}

mysqli_set_charset($conn, 'utf8mb4');

$select = mysqli_prepare(
    $conn,
    'SELECT item_id FROM items WHERE item_id_txt = ? LIMIT 1'
);
$insert = mysqli_prepare(
    $conn,
    'INSERT INTO items (item_id_txt, item_name, item_price, create_at, last_modified)
     VALUES (?, ?, ?, ?, ?)'
);
$update = mysqli_prepare(
    $conn,
    'UPDATE items SET item_name = ?, item_price = ?, last_modified = ?
     WHERE item_id_txt = ?'
);

if (!$select || !$insert || !$update) {
    respond(500, 'Gagal menyiapkan query sinkronisasi');
}

date_default_timezone_set('Asia/Jakarta');
$now = date('m/d/Y h:i:s a');
$synced = 0;
$skipped = 0;

mysqli_begin_transaction($conn);

try {
    foreach ($payload['data'] as $product) {
        $sku = trim((string)($product['sku'] ?? ''));
        $name = trim((string)($product['name'] ?? ''));
        $price = (string)($product['price'] ?? '');

        if (
            $sku === '' ||
            strlen($sku) > 15 ||
            $name === '' ||
            strlen($name) > 50 ||
            !is_numeric($price) ||
            !is_finite((float)$price) ||
            (float)$price < 0 ||
            strlen($price) > 30
        ) {
            $skipped++;
            continue;
        }

        mysqli_stmt_bind_param($select, 's', $sku);
        if (!mysqli_stmt_execute($select)) {
            throw new Exception('Gagal memeriksa SKU');
        }

        $result = mysqli_stmt_get_result($select);
        $existing = $result ? mysqli_fetch_assoc($result) : null;

        if ($existing) {
            mysqli_stmt_bind_param(
                $update, 'ssss', $name, $price, $now, $sku
            );
            if (!mysqli_stmt_execute($update)) {
                throw new Exception('Gagal memperbarui SKU');
            }
        } else {
            mysqli_stmt_bind_param(
                $insert, 'sssss', $sku, $name, $price, $now, $now
            );
            if (!mysqli_stmt_execute($insert)) {
                throw new Exception('Gagal menambahkan SKU');
            }
        }

        $synced++;
    }

    mysqli_commit($conn);
} catch (Throwable $e) {
    mysqli_rollback($conn);
    error_log('Inventory sync failed: ' . $e->getMessage());
    respond(500, 'Sinkronisasi gagal; transaksi dibatalkan');
}

mysqli_stmt_close($select);
mysqli_stmt_close($insert);
mysqli_stmt_close($update);
mysqli_close($conn);

respond(200, 'Sinkronisasi selesai', [
    'processed' => $synced,
    'skipped' => $skipped
]);
