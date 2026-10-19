<?php
require("../../src/server/connection.php");
require("../../src/server/getter/get_items.php");
require("../../src/server/helper/print_alert.php");
require("../../src/server/helper/auth.php");

if (!auth()) {
    header("Location: ../login");
    exit;
}

$inventory_products = get_inventory_products();
$product_count = count($inventory_products);
$total_stock = 0;
$low_stock_count = 0;

foreach ($inventory_products as $product) {
    $stock = (int) ($product["stock"] ?? 0);
    $total_stock += $stock;

    if ($stock <= 5) {
        $low_stock_count++;
    }
}

function item_h($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function item_date($value): string {
    if (empty($value) || $value === "-") {
        return "-";
    }

    $timestamp = strtotime((string) $value);

    return $timestamp !== false
        ? date("d M Y, H:i", $timestamp)
        : (string) $value;
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="manifest" href="../../manifest.json">
    <meta name="theme-color" content="#f8fafc">
    <link rel="icon"
          href="../../src/public/images/icons/icon-144x144.png"
          sizes="144x144" type="image/png">

    <link href="../../node_modules/bootstrap/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link rel="stylesheet"
          href="../../node_modules/@fortawesome/fontawesome-free/css/all.css">

    <link rel="stylesheet"
          href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

    <title>Produk | POS Dashboard</title>

    <style>
        :root {
            --page-bg: #f6f8fc;
            --primary: #6366f1;
            --text-main: #172033;
            --text-muted: #7b8498;
            --border: #edf0f5;
        }

        body {
            background: var(--page-bg);
            color: var(--text-main);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
        }

        .page-container {
            max-width: 1440px;
            padding: 32px 28px 56px;
        }

        .page-eyebrow {
            margin-bottom: 7px;
            color: var(--primary);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .page-title {
            margin: 0;
            font-size: clamp(25px, 3vw, 32px);
            font-weight: 800;
            letter-spacing: -1px;
        }

        .page-subtitle {
            margin: 9px 0 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .source-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 13px;
            border: 1px solid #d9f3e5;
            border-radius: 999px;
            background: #ecfdf5;
            color: #087443;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .source-pill i {
            font-size: 10px;
        }

        .stat-card {
            height: 100%;
            padding: 22px;
            border: 1px solid var(--border);
            border-radius: 17px;
            background: #fff;
            box-shadow: 0 4px 18px rgba(24, 39, 75, .025);
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 600;
        }

        .stat-icon {
            display: grid;
            width: 43px;
            height: 43px;
            flex-shrink: 0;
            place-items: center;
            border-radius: 13px;
            font-size: 17px;
        }

        .icon-purple {
            background: #eef2ff;
            color: #6366f1;
        }

        .icon-blue {
            background: #eaf5ff;
            color: #2584dc;
        }

        .icon-orange {
            background: #fff3e8;
            color: #ea790b;
        }

        .stat-number {
            margin-top: 15px;
            font-size: 29px;
            font-weight: 800;
            letter-spacing: -.8px;
        }

        .stat-caption {
            margin-top: 4px;
            color: #9aa2b1;
            font-size: 12px;
        }

        .content-card {
            overflow: hidden;
            margin-top: 27px;
            border: 1px solid var(--border);
            border-radius: 17px;
            background: #fff;
            box-shadow: 0 4px 18px rgba(24, 39, 75, .025);
        }

        .content-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 23px 24px;
            border-bottom: 1px solid var(--border);
        }

        .content-title {
            margin: 0;
            font-size: 17px;
            font-weight: 750;
        }

        .content-caption {
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 12px;
        }

        .table-area {
            padding: 10px 24px 22px;
        }

        #table_id {
            width: 100% !important;
            border-collapse: separate;
            border-spacing: 0;
        }

        #table_id thead th {
            padding: 17px 13px;
            border-bottom: 1px solid #e9edf4;
            background: #fafbfe;
            color: #788196;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .45px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        #table_id tbody td {
            padding: 16px 13px;
            border-bottom: 1px solid #f0f2f7;
            color: #4b5568;
            font-size: 13px;
            vertical-align: middle;
        }

        #table_id tbody tr:hover td {
            background: #fafbff;
        }

        #table_id tbody tr:last-child td {
            border-bottom: 0;
        }

        .row-number {
            color: #a0a8b8;
            font-size: 12px;
        }

        .sku-code {
            display: inline-block;
            padding: 6px 9px;
            border: 1px solid #e8eafe;
            border-radius: 7px;
            background: #f5f6ff;
            color: #5558c9;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .product-name {
            color: #202a3d;
            font-weight: 700;
        }

        .price-text {
            color: #202a3d;
            font-weight: 750;
            white-space: nowrap;
        }

        .stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 9px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 750;
            white-space: nowrap;
        }

        .stock-good {
            background: #eafaf1;
            color: #12834e;
        }

        .stock-low {
            background: #fff4e5;
            color: #b96708;
        }

        .stock-empty {
            background: #fff0f0;
            color: #c53535;
        }

        .date-text {
            color: #7b8498;
            font-size: 12px;
            white-space: nowrap;
        }

        .dataTables_wrapper {
            font-size: 13px;
        }

        .dataTables_wrapper .dataTables_filter {
            margin: 12px 0 18px;
            text-align: right;
        }

        .dataTables_wrapper .dataTables_filter label {
            color: #788196;
            font-size: 12px;
        }

        .dataTables_wrapper .dataTables_filter input {
            width: min(260px, 55vw);
            margin-left: 8px;
            padding: 10px 13px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            outline: none;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99,102,241,.1);
        }

        .dataTables_wrapper .dataTables_length select {
            margin: 0 6px;
            padding: 6px 25px 6px 9px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }

        .dataTables_wrapper .dataTables_info {
            padding-top: 16px;
            color: #8a93a5;
            font-size: 12px;
        }

        .dataTables_wrapper .pagination {
            gap: 4px;
            margin-top: 12px;
        }

        .dataTables_wrapper .page-link {
            border: 1px solid #edf0f5;
            border-radius: 8px !important;
            color: #596174;
        }

        .dataTables_wrapper .page-item.active .page-link {
            border-color: var(--primary);
            background: var(--primary);
            color: #fff;
        }

        .empty-state {
            padding: 38px 20px !important;
            color: #7b8498 !important;
            text-align: center;
        }

        .info-footer {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            margin-top: 18px;
            padding: 15px 17px;
            border: 1px solid #e2eaff;
            border-radius: 12px;
            background: #f2f6ff;
            color: #536484;
            font-size: 12px;
            line-height: 1.7;
        }

        .info-footer i {
            margin-top: 3px;
            color: #6366f1;
        }

        @media (max-width: 767px) {
            .page-container {
                padding: 24px 15px 40px;
            }

            .page-heading {
                align-items: flex-start !important;
                flex-direction: column;
            }

            .stat-card {
                padding: 17px;
            }

            .stat-number {
                font-size: 25px;
            }

            .content-header {
                align-items: flex-start;
                padding: 19px 17px;
            }

            .table-area {
                padding: 10px 14px 18px;
            }

            .dataTables_wrapper .dataTables_filter {
                text-align: left;
            }

            .dataTables_wrapper .dataTables_filter input {
                display: block;
                width: 100%;
                margin: 8px 0 0;
            }
        }
    </style>
</head>

<body>
    <section data-root="navbar"></section>
    <section data-root="jumbotron"></section>

    <main class="container-fluid page-container">
        <div class="mb-4" data-root="breadcumb"></div>

        <?php print_alert(); ?>

        <div class="page-heading d-flex justify-content-between align-items-center gap-3 mb-4">
            <div>
                <div class="page-eyebrow">Inventory management</div>
                <h1 class="page-title">Produk</h1>
                <p class="page-subtitle">
                    Pantau daftar produk dan ketersediaan stok dari Inventory.
                </p>
            </div>

            <span class="source-pill">
                <i class="fas fa-circle"></i>
                Terhubung ke Inventory
            </span>
        </div>

        <?php if (empty($inventory_products)): ?>
            <div class="alert alert-warning d-flex gap-2 align-items-start" role="alert">
                <i class="fas fa-triangle-exclamation mt-1"></i>
                <div>
                    <strong>Data produk belum tersedia.</strong><br>
                    Periksa koneksi Inventory API atau pastikan Inventory sudah memiliki produk.
                    Kemudian coba muat ulang halaman.
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-label">Total Produk</div>
                        <div class="stat-icon icon-purple">
                            <i class="fas fa-boxes-stacked"></i>
                        </div>
                    </div>
                    <div class="stat-number">
                        <?= number_format($product_count, 0, ",", "."); ?>
                    </div>
                    <div class="stat-caption">Produk yang diterima dari Inventory</div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-4">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-label">Total Stok</div>
                        <div class="stat-icon icon-blue">
                            <i class="fas fa-cubes"></i>
                        </div>
                    </div>
                    <div class="stat-number">
                        <?= number_format($total_stock, 0, ",", "."); ?>
                    </div>
                    <div class="stat-caption">Akumulasi stok seluruh produk</div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-4">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-label">Stok Menipis</div>
                        <div class="stat-icon icon-orange">
                            <i class="fas fa-triangle-exclamation"></i>
                        </div>
                    </div>
                    <div class="stat-number">
                        <?= number_format($low_stock_count, 0, ",", "."); ?>
                    </div>
                    <div class="stat-caption">Produk dengan stok 5 atau kurang</div>
                </div>
            </div>
        </div>

        <section class="content-card">
            <div class="content-header">
                <div>
                    <h2 class="content-title">Daftar Produk</h2>
                    <div class="content-caption">
                        Informasi SKU, harga, stok, dan tanggal produk
                    </div>
                </div>
                <i class="fas fa-table text-secondary"></i>
            </div>

            <div class="table-area">
                <table class="table" id="table_id">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>ID Item / SKU</th>
                            <th>Nama Produk</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Terakhir Diubah</th>
                            <th>Dibuat Pada</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($inventory_products as $index => $item): ?>
                        <?php
                            $item_id = (string) ($item["item_id_txt"] ?? "");
                            $item_name = (string) ($item["item_name"] ?? "");
                            $item_price = (float) ($item["item_price"] ?? 0);
                            $stock = (int) ($item["stock"] ?? 0);

                            if ($stock <= 0) {
                                $stock_class = "stock-empty";
                                $stock_label = "Habis";
                            } elseif ($stock <= 5) {
                                $stock_class = "stock-low";
                                $stock_label = "Menipis";
                            } else {
                                $stock_class = "stock-good";
                                $stock_label = "Tersedia";
                            }
                        ?>
                        <tr>
                            <td>
                                <span class="row-number"><?= $index + 1; ?></span>
                            </td>
                            <td>
                                <span class="sku-code"><?= item_h($item_id ?: "-"); ?></span>
                            </td>
                            <td>
                                <span class="product-name"><?= item_h($item_name ?: "Tanpa nama"); ?></span>
                            </td>
                            <td>
                                <span class="price-text">
                                    Rp <?= number_format($item_price, 0, ",", "."); ?>
                                </span>
                            </td>
                            <td data-order="<?= $stock; ?>">
                                <span class="stock-badge <?= $stock_class; ?>">
                                    <?= $stock; ?> · <?= $stock_label; ?>
                                </span>
                            </td>
                            <td>
                                <span class="date-text">
                                    <?= item_h(item_date($item["last_modified"] ?? "-")); ?>
                                </span>
                            </td>
                            <td>
                                <span class="date-text">
                                    <?= item_h(item_date($item["create_at"] ?? "-")); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="info-footer">
            <i class="fas fa-circle-info"></i>
            <div>
                <strong>Sinkronisasi Inventory</strong><br>
                Daftar ini menampilkan data dari Inventory. Untuk menambah atau
                mengubah produk, lakukan melalui aplikasi Inventory, lalu muat
                ulang halaman ini untuk melihat data terbaru.
            </div>
        </div>
    </main>

    <section data-root="footer"></section>

    <script src="../../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(function () {
            $('#table_id').DataTable({
                scrollX: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [],
                language: {
                    search: "Cari produk:",
                    lengthMenu: "Tampilkan _MENU_ produk",
                    info: "Menampilkan _START_–_END_ dari _TOTAL_ produk",
                    infoEmpty: "Belum ada produk",
                    zeroRecords: "Produk tidak ditemukan",
                    emptyTable: "Belum ada data produk dari Inventory",
                    paginate: {
                        first: "Awal",
                        last: "Akhir",
                        next: "Berikutnya",
                        previous: "Sebelumnya"
                    }
                }
            });
        });
    </script>

    <script src="../../src/main.js" type="module"></script>
    <script src="../../src/views/Items.js" type="module"></script>
</body>
</html>