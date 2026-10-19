<?php

// Mengambil barang dari database lokal POS.
// Digunakan oleh halaman Manage Items dan fitur edit/hapus.
function get_items($conn)
{
    $query = "
        SELECT
            item_id,
            item_id_txt,
            item_name,
            item_price,
            last_modified,
            create_at
        FROM items
        ORDER BY item_id ASC
    ";

    $result = mysqli_query($conn, $query);

    if ($result === false) {
        error_log('Failed to retrieve local POS items.');
        return [];
    }

    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Mengambil produk dari Inventory API.
// Digunakan untuk pilihan barang saat transaksi.
function get_inventory_products()
{
    $apiUrl = 'http://inventory-api/api/products';

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            'ignore_errors' => true,
            'header' => "Accept: application/json\r\n"
        ]
    ]);

    $response = @file_get_contents($apiUrl, false, $context);

    if ($response === false) {
        error_log('Inventory API request failed.');
        return [];
    }

    $result = json_decode($response, true);

    if (
        !is_array($result) ||
        ($result['status'] ?? '') !== 'success' ||
        !isset($result['data']) ||
        !is_array($result['data'])
    ) {
        error_log('Inventory API returned an invalid response.');
        return [];
    }

    $products = [];

    foreach ($result['data'] as $product) {
        $products[] = [
            'item_id' => (int) ($product['id'] ?? 0),
            'item_id_txt' => (string) ($product['sku'] ?? ''),
            'item_name' => (string) ($product['name'] ?? ''),
            'item_price' => (string) ($product['price'] ?? '0'),
            'last_modified' => (string) ($product['updated_at'] ?? ''),
            'create_at' => (string) ($product['created_at'] ?? ''),
            'stock' => (int) ($product['stock'] ?? 0)
        ];
    }

    return $products;
}
