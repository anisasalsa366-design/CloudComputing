<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Idempotency-Key');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

function respond(int $statusCode, array $body): void
{
    http_response_code($statusCode);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

function inventory_db(): PDO
{
    $host = getenv('DB_HOST') ?: 'db';
    $database = getenv('DB_NAME') ?: 'inventory_management';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') ?: '';

    return new PDO(
        "mysql:host={$host};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
}

function read_json_body(): array
{
    $body = json_decode(file_get_contents('php://input') ?: '', true);

    if (!is_array($body)) {
        respond(400, [
            'status' => 'error',
            'message' => 'Request body must be valid JSON'
        ]);
    }

    return $body;
}

function valid_idempotency_key(string $key): bool
{
    return $key !== ''
        && strlen($key) <= 64
        && preg_match('/^[A-Za-z0-9._:-]+$/', $key) === 1;
}

function saved_response(array $record): array
{
    $decoded = json_decode($record['response_json'] ?? '', true);
    return is_array($decoded) ? $decoded : [];
}

function sale_failure_status(array $response): int
{
    return match ($response['message'] ?? '') {
        'Product not found' => 404,
        'Insufficient stock' => 409,
        default => 409
    };
}

if ($method === 'GET' && $path === '/api/health') {
    respond(200, [
        'status' => 'ok',
        'service' => 'inventory-api',
        'message' => 'Inventory API is running'
    ]);
}

if ($method === 'GET' && $path === '/api/products') {
    try {
        $pdo = inventory_db();

        $products = $pdo->query(
            'SELECT id, category_id, name, sku, description,
                    price, stock, minimum_stock
             FROM products
             ORDER BY id ASC'
        )->fetchAll();

        respond(200, [
            'status' => 'success',
            'count' => count($products),
            'data' => $products
        ]);
    } catch (Throwable $exception) {
        error_log('Inventory product lookup failed: ' . $exception->getMessage());

        respond(500, [
            'status' => 'error',
            'message' => 'Unable to retrieve products'
        ]);
    }
}

/*
 * POST /api/sales
 *
 * Mengurangi stok satu kali untuk setiap Idempotency-Key.
 * Request yang sama akan memperoleh respons yang tersimpan.
 */
if ($method === 'POST' && $path === '/api/sales') {
    $key = trim($_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? '');

    if (!valid_idempotency_key($key)) {
        respond(400, [
            'status' => 'error',
            'message' => 'A valid Idempotency-Key header is required'
        ]);
    }

    $payload = read_json_body();
    $sku = trim((string) ($payload['sku'] ?? ''));
    $quantity = filter_var(
        $payload['quantity'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        $sku === '' ||
        strlen($sku) > 50 ||
        $quantity === false ||
        $quantity < 1 ||
        $quantity > 1000000
    ) {
        respond(400, [
            'status' => 'error',
            'message' => 'A valid SKU and quantity are required'
        ]);
    }

    $requestHash = hash(
        'sha256',
        json_encode(
            ['sku' => $sku, 'quantity' => $quantity],
            JSON_UNESCAPED_UNICODE
        )
    );

    $pdo = null;
    $transactionOpen = false;

    try {
        $pdo = inventory_db();
        $pdo->beginTransaction();
        $transactionOpen = true;

        $reserve = $pdo->prepare(
            "INSERT INTO api_sale_requests
                (idempotency_key, request_hash, status)
             VALUES (?, ?, 'processing')"
        );
        $reserve->execute([$key, $requestHash]);

        $productStmt = $pdo->prepare(
            'SELECT id, name, sku, price, stock
             FROM products
             WHERE sku = ?
             FOR UPDATE'
        );
        $productStmt->execute([$sku]);
        $product = $productStmt->fetch();

        if (!$product) {
            $response = [
                'status' => 'error',
                'message' => 'Product not found'
            ];

            $save = $pdo->prepare(
                "UPDATE api_sale_requests
                 SET status = 'failed', response_json = ?
                 WHERE idempotency_key = ?"
            );
            $save->execute([
                json_encode($response, JSON_UNESCAPED_UNICODE),
                $key
            ]);

            $pdo->commit();
            $transactionOpen = false;

            respond(404, $response);
        }

        $stock = (int) $product['stock'];

        if ($stock < $quantity) {
            $response = [
                'status' => 'error',
                'message' => 'Insufficient stock',
                'available_stock' => $stock
            ];

            $save = $pdo->prepare(
                "UPDATE api_sale_requests
                 SET status = 'failed', response_json = ?
                 WHERE idempotency_key = ?"
            );
            $save->execute([
                json_encode($response, JSON_UNESCAPED_UNICODE),
                $key
            ]);

            $pdo->commit();
            $transactionOpen = false;

            respond(409, $response);
        }

        $price = (float) $product['price'];
        $total = round($price * $quantity, 2);

        $update = $pdo->prepare(
            'UPDATE products
             SET stock = stock - ?
             WHERE id = ? AND stock >= ?'
        );
        $update->execute([
            $quantity,
            (int) $product['id'],
            $quantity
        ]);

        if ($update->rowCount() !== 1) {
            throw new RuntimeException('Stock update did not succeed');
        }

        $response = [
            'status' => 'success',
            'message' => 'Stock reserved successfully',
            'sale' => [
                'sku' => $product['sku'],
                'name' => $product['name'],
                'quantity' => $quantity,
                'unit_price' => number_format($price, 2, '.', ''),
                'total' => number_format($total, 2, '.', ''),
                'remaining_stock' => $stock - $quantity
            ]
        ];

        $save = $pdo->prepare(
            "UPDATE api_sale_requests
             SET status = 'completed', response_json = ?
             WHERE idempotency_key = ?"
        );
        $save->execute([
            json_encode($response, JSON_UNESCAPED_UNICODE),
            $key
        ]);

        $pdo->commit();
        $transactionOpen = false;

        respond(200, $response);

    } catch (PDOException $exception) {
        if ($transactionOpen && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($exception->getCode() === '23000') {
            try {
                $checkPdo = inventory_db();
                $check = $checkPdo->prepare(
                    'SELECT request_hash, status, response_json
                     FROM api_sale_requests
                     WHERE idempotency_key = ?'
                );
                $check->execute([$key]);
                $existing = $check->fetch();

                if ($existing) {
                    if (!hash_equals($existing['request_hash'], $requestHash)) {
                        respond(409, [
                            'status' => 'error',
                            'message' => 'Idempotency-Key was already used for a different request'
                        ]);
                    }

                    if ($existing['status'] === 'failed') {
                        $response = saved_response($existing);
                        respond(sale_failure_status($response), $response);
                    }

                    if ($existing['status'] === 'completed') {
                        $compensationCheck = $checkPdo->prepare(
                            "SELECT status
                             FROM api_sale_compensations
                             WHERE sale_idempotency_key = ?"
                        );
                        $compensationCheck->execute([$key]);
                        $compensation = $compensationCheck->fetch();

                        if ($compensation) {
                            respond(409, [
                                'status' => 'error',
                                'message' => 'This sale has already been compensated'
                            ]);
                        }

                        respond(200, saved_response($existing));
                    }
                }
            } catch (Throwable $lookupException) {
                error_log('Sale idempotency lookup failed: ' . $lookupException->getMessage());
            }
        }

        error_log('Inventory sale database error: ' . $exception->getMessage());

        respond(500, [
            'status' => 'error',
            'message' => 'Unable to process sale'
        ]);

    } catch (Throwable $exception) {
        if ($transactionOpen && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Inventory sale failed: ' . $exception->getMessage());

        respond(500, [
            'status' => 'error',
            'message' => 'Unable to process sale'
        ]);
    }
}

/*
 * POST /api/sales/compensate
 *
 * Body:
 * {
 *   "sale_key": "original-sale-idempotency-key"
 * }
 *
 * Idempotency-Key pada header harus merupakan kunci kompensasi yang baru.
 */
if ($method === 'POST' && $path === '/api/sales/compensate') {
    $compensationKey = trim(
        $_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? ''
    );

    if (!valid_idempotency_key($compensationKey)) {
        respond(400, [
            'status' => 'error',
            'message' => 'A valid compensation Idempotency-Key is required'
        ]);
    }

    $payload = read_json_body();
    $saleKey = trim((string) ($payload['sale_key'] ?? ''));

    if (!valid_idempotency_key($saleKey)) {
        respond(400, [
            'status' => 'error',
            'message' => 'A valid sale_key is required'
        ]);
    }

    $requestHash = hash(
        'sha256',
        json_encode(['sale_key' => $saleKey], JSON_UNESCAPED_UNICODE)
    );

    $pdo = null;
    $transactionOpen = false;

    try {
        $pdo = inventory_db();
        $pdo->beginTransaction();
        $transactionOpen = true;

        // Claim the compensation key. The unique constraints prevent
        // more than one compensation from restoring the same sale.
        $insert = $pdo->prepare(
            "INSERT INTO api_sale_compensations
                (sale_idempotency_key, compensation_key, request_hash, status)
             VALUES (?, ?, ?, 'processing')"
        );
        $insert->execute([$saleKey, $compensationKey, $requestHash]);

        // Lock the original sale and ensure it really completed.
        $saleStmt = $pdo->prepare(
            'SELECT status, response_json
             FROM api_sale_requests
             WHERE idempotency_key = ?
             FOR UPDATE'
        );
        $saleStmt->execute([$saleKey]);
        $sale = $saleStmt->fetch();

        if (!$sale || $sale['status'] !== 'completed') {
            $pdo->rollBack();
            $transactionOpen = false;

            respond(409, [
                'status' => 'error',
                'message' => 'Only a completed sale can be compensated'
            ]);
        }

        $saleResponse = saved_response($sale);
        $saleDetails = $saleResponse['sale'] ?? null;

        if (
            !is_array($saleDetails) ||
            !isset($saleDetails['sku'], $saleDetails['quantity']) ||
            (int) $saleDetails['quantity'] < 1
        ) {
            throw new RuntimeException('Saved sale details are invalid');
        }

        $sku = (string) $saleDetails['sku'];
        $quantity = (int) $saleDetails['quantity'];

        $productStmt = $pdo->prepare(
            'SELECT id, stock
             FROM products
             WHERE sku = ?
             FOR UPDATE'
        );
        $productStmt->execute([$sku]);
        $product = $productStmt->fetch();

        if (!$product) {
            throw new RuntimeException('Product for compensation was not found');
        }

        $restore = $pdo->prepare(
            'UPDATE products
             SET stock = stock + ?
             WHERE id = ?'
        );
        $restore->execute([$quantity, (int) $product['id']]);

        if ($restore->rowCount() !== 1) {
            throw new RuntimeException('Stock restoration did not succeed');
        }

        $response = [
            'status' => 'success',
            'message' => 'Sale compensated successfully',
            'sale_key' => $saleKey,
            'sku' => $sku,
            'quantity_restored' => $quantity,
            'stock_after_compensation' => (int) $product['stock'] + $quantity
        ];

        $save = $pdo->prepare(
            "UPDATE api_sale_compensations
             SET status = 'completed', response_json = ?
             WHERE sale_idempotency_key = ?"
        );
        $save->execute([
            json_encode($response, JSON_UNESCAPED_UNICODE),
            $saleKey
        ]);

        $pdo->commit();
        $transactionOpen = false;

        respond(200, $response);

    } catch (PDOException $exception) {
        if ($transactionOpen && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($exception->getCode() === '23000') {
            try {
                $checkPdo = inventory_db();
                $check = $checkPdo->prepare(
                    'SELECT compensation_key, request_hash, status, response_json
                     FROM api_sale_compensations
                     WHERE sale_idempotency_key = ?'
                );
                $check->execute([$saleKey]);
                $existing = $check->fetch();

                if (!$existing) {
                    $check = $checkPdo->prepare(
                        'SELECT compensation_key, request_hash, status, response_json
                         FROM api_sale_compensations
                         WHERE compensation_key = ?'
                    );
                    $check->execute([$compensationKey]);
                    $existing = $check->fetch();
                }

                if ($existing) {
                    if (
                        !hash_equals($existing['request_hash'], $requestHash) ||
                        $existing['compensation_key'] !== $compensationKey
                    ) {
                        respond(409, [
                            'status' => 'error',
                            'message' => 'Compensation key conflicts with an existing request'
                        ]);
                    }

                    if ($existing['status'] === 'completed') {
                        respond(200, saved_response($existing));
                    }
                }
            } catch (Throwable $lookupException) {
                error_log('Compensation lookup failed: ' . $lookupException->getMessage());
            }

            respond(409, [
                'status' => 'error',
                'message' => 'This sale may already have a compensation request'
            ]);
        }

        error_log('Compensation database error: ' . $exception->getMessage());

        respond(500, [
            'status' => 'error',
            'message' => 'Unable to compensate sale'
        ]);

    } catch (Throwable $exception) {
        if ($transactionOpen && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Compensation failed: ' . $exception->getMessage());

        respond(500, [
            'status' => 'error',
            'message' => 'Unable to compensate sale'
        ]);
    }
}

respond(404, [
    'status' => 'error',
    'message' => 'Endpoint not found'
]);
