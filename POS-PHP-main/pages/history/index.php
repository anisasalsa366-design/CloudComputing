<?php

require("../../src/server/connection.php");
require("../../src/server/getter/get_transactions.php");
require("../../src/server/helper/print_alert.php");
require("../../src/server/helper/auth.php");

if (!auth()) {
    header("Location: ../login");
    exit;
}

$transactions = get_transactions($conn);

function history_escape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function history_rupiah($value): string
{
    if (!is_numeric($value)) {
        return "0";
    }

    return number_format((float) $value, 0, ",", ".");
}

$transaction_count = count($transactions);
$sales_total = 0;

foreach ($transactions as $transaction) {
    $sales_total += (float) ($transaction["transaction_total"] ?? 0);
}

?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f6f8fc">

    <link rel="manifest" href="../../manifest.json">
    <link rel="icon"
          href="../../src/public/images/icons/icon-144x144.png"
          sizes="144x144"
          type="image/png">

    <link href="../../node_modules/bootstrap/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet"
          href="../../node_modules/@fortawesome/fontawesome-free/css/all.css">

    <link rel="stylesheet"
          href="https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap5.min.css">

    <title>Transaction History | POS</title>

    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --page-bg: #f6f8fc;
            --text-main: #172033;
            --text-muted: #7b8498;
            --border: #edf0f5;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--page-bg);
            color: var(--text-main);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
        }

        .history-container {
            width: 100%;
            max-width: 1500px;
            margin: 0 auto;
            padding: 30px 24px 50px;
        }

        .page-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin: 25px 0;
        }

        .eyebrow {
            margin-bottom: 8px;
            color: var(--primary);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .page-title {
            margin: 0;
            font-size: clamp(25px, 3vw, 33px);
            font-weight: 850;
            letter-spacing: -1px;
        }

        .page-subtitle {
            margin: 9px 0 0;
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.7;
        }

        .heading-icon {
            display: grid;
            width: 58px;
            height: 58px;
            flex: 0 0 58px;
            place-items: center;
            border-radius: 17px;
            background: #eef2ff;
            color: var(--primary);
            font-size: 23px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 5px 22px rgba(24, 39, 75, .025);
        }

        .stat-icon {
            display: grid;
            width: 45px;
            height: 45px;
            flex: 0 0 45px;
            place-items: center;
            border-radius: 13px;
            background: #eef2ff;
            color: var(--primary);
            font-size: 18px;
        }

        .stat-label {
            margin-bottom: 5px;
            color: var(--text-muted);
            font-size: 12px;
        }

        .stat-value {
            font-size: 22px;
            font-weight: 850;
            line-height: 1.2;
        }

        .history-panel {
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 17px;
            background: #fff;
            box-shadow: 0 5px 22px rgba(24, 39, 75, .035);
        }

        .panel-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 23px 25px;
            border-bottom: 1px solid var(--border);
        }

        .panel-heading h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
        }

        .panel-heading p {
            margin: 7px 0 0;
            color: var(--text-muted);
            font-size: 12px;
        }

        .count-badge {
            padding: 8px 11px;
            border-radius: 9px;
            background: #eef2ff;
            color: var(--primary-dark);
            font-size: 11px;
            font-weight: 750;
            white-space: nowrap;
        }

        .table-area {
            padding: 22px 24px 25px;
        }

        #table_id {
            width: 100% !important;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 12px;
        }

        #table_id thead th {
            padding: 14px 12px;
            border-bottom: 1px solid #e8ebf2;
            background: #f8f9fc;
            color: #626d82;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .4px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        #table_id tbody td {
            padding: 15px 12px;
            border-bottom: 1px solid #f0f2f7;
            color: #455066;
            vertical-align: top;
            white-space: nowrap;
        }

        #table_id tbody tr:hover td {
            background: #fafbff;
        }

        .transaction-id {
            color: var(--primary-dark);
            font-weight: 800;
        }

        .item-name {
            color: var(--text-main);
            font-weight: 700;
        }

        .money-value {
            font-weight: 750;
            font-variant-numeric: tabular-nums;
        }

        .money-negative {
            color: #dc2626;
        }

        .money-positive {
            color: #15803d;
        }

        .date-value {
            color: var(--text-muted);
            font-size: 11px;
        }

        .product-list {
            display: flex;
            min-width: 220px;
            flex-direction: column;
            gap: 12px;
        }

        .product-row {
            padding-bottom: 11px;
            border-bottom: 1px dashed #e8ebf2;
        }

        .product-row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .product-meta {
            margin-top: 4px;
            color: var(--text-muted);
            font-size: 11px;
        }

        .subtotal {
            color: var(--text-main);
            font-weight: 750;
        }

        .btn-delete {
            display: inline-grid;
            width: 34px;
            height: 34px;
            place-items: center;
            border: 1px solid #fee2e2;
            border-radius: 9px;
            background: #fff5f5;
            color: #dc2626;
            text-decoration: none;
            transition: background .2s, color .2s;
        }

        .btn-delete:hover {
            background: #dc2626;
            color: #fff;
        }

        .table-hint {
            margin: 17px 0 0;
            color: var(--text-muted);
            font-size: 11px;
            line-height: 1.7;
        }

        .dataTables_wrapper {
            color: var(--text-muted);
            font-size: 12px;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 18px;
            color: var(--text-muted);
        }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            min-height: 36px;
            margin-left: 6px;
            padding: 6px 10px;
            border: 1px solid #e5e9f2;
            border-radius: 9px;
            background: #fff;
            color: var(--text-main);
            outline: none;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #a5b4fc;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .1);
        }

        .dataTables_wrapper .dataTables_info {
            padding-top: 18px;
        }

        .dataTables_wrapper .dataTables_paginate {
            padding-top: 12px;
        }

        .dataTables_wrapper .page-link {
            border-color: #e5e9f2;
            color: var(--primary-dark);
            font-size: 12px;
        }

        .dataTables_wrapper .page-item.active .page-link {
            border-color: var(--primary);
            background: var(--primary);
            color: #fff;
        }

        @media (max-width: 650px) {
            .history-container {
                padding: 22px 14px 35px;
            }

            .page-heading {
                align-items: flex-start;
                margin-top: 17px;
            }

            .heading-icon {
                width: 47px;
                height: 47px;
                flex-basis: 47px;
                border-radius: 14px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 11px;
            }

            .stat-card {
                padding: 16px;
            }

            .panel-heading {
                align-items: flex-start;
                padding: 19px;
            }

            .table-area {
                padding: 16px;
            }

            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                float: none;
                width: 100%;
                text-align: left;
            }

            .dataTables_wrapper .dataTables_filter input {
                width: 100%;
                margin: 8px 0 0;
            }
        }
    </style>
</head>

<body>
    <section data-root="navbar"></section>
    <section data-root="jumbotron"></section>

    <main class="history-container">
        <div class="mb-4" data-root="breadcumb"></div>

        <header class="page-heading">
            <div>
                <div class="eyebrow">Point of Sale System</div>
                <h1 class="page-title">Riwayat Transaksi</h1>
                <p class="page-subtitle">
                    Pantau transaksi penjualan dan detail pembayarannya.
                </p>
            </div>

            <div class="heading-icon" aria-hidden="true">
                <i class="fas fa-clock-rotate-left"></i>
            </div>
        </header>

        <?php print_alert(); ?>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-receipt"></i>
                </div>

                <div>
                    <div class="stat-label">Jumlah Transaksi</div>
                    <div class="stat-value">
                        <?= number_format($transaction_count, 0, ",", "."); ?>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-wallet"></i>
                </div>

                <div>
                    <div class="stat-label">Total Penjualan Tercatat</div>
                    <div class="stat-value">
                        Rp <?= history_rupiah($sales_total); ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="history-panel">
            <div class="panel-heading">
                <div>
                    <h2>
                        <i class="fas fa-list-ul me-2"
                           style="color:#6366f1;"></i>
                        Data Transaksi
                    </h2>

                    <p>
                        Setiap baris mewakili satu transaksi, termasuk seluruh produknya.
                    </p>
                </div>

                <span class="count-badge">
                    <?= number_format($transaction_count, 0, ",", "."); ?> transaksi
                </span>
            </div>

            <div class="table-area">
                <div class="table-responsive">
                    <table class="table" id="table_id">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>ID Transaksi</th>
                                <th>Detail Produk</th>
                                <th>Total Transaksi</th>
                                <th>Uang Dibayar</th>
                                <th>Kembalian / Kekurangan</th>
                                <th>Waktu</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($transactions as $index => $transaction): ?>
                                <?php
                                $transaction_id_txt = history_escape(
                                    $transaction["transaction_id_txt"] ?? ""
                                );

                                $transaction_id = (int) (
                                    $transaction["transaction_id"] ?? 0
                                );

                                $transaction_total = (float) (
                                    $transaction["transaction_total"] ?? 0
                                );

                                $transaction_cash = (float) (
                                    $transaction["transaction_cash"] ?? 0
                                );

                                $transaction_change = (float) (
                                    $transaction["transaction_money_changes"] ?? 0
                                );

                                $transaction_created = history_escape(
                                    $transaction["transaction_create_at"] ?? "-"
                                );

                                $details = $transaction["details"] ?? [];

                                // Kompatibilitas dengan data transaksi lama.
                                if (empty($details) && !empty($transaction["item_name"])) {
                                    $details = [[
                                        "item_id_txt" => $transaction["item_id_txt"] ?? "-",
                                        "item_name" => $transaction["item_name"] ?? "-",
                                        "item_price" => $transaction["item_price"] ?? 0,
                                        "quantity" => $transaction["transaction_amount"] ?? 0,
                                        "subtotal" => (
                                            (float) ($transaction["item_price"] ?? 0)
                                            * (int) ($transaction["transaction_amount"] ?? 0)
                                        ),
                                    ]];
                                }

                                $change_class = $transaction_change < 0
                                    ? "money-negative"
                                    : "money-positive";
                                ?>

                                <tr>
                                    <td><?= $index + 1; ?></td>

                                    <td>
                                        <span class="transaction-id">
                                            <?= $transaction_id_txt; ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="product-list">
                                            <?php if (empty($details)): ?>
                                                <span class="text-muted">
                                                    Detail produk tidak tersedia
                                                </span>
                                            <?php else: ?>
                                                <?php foreach ($details as $detail): ?>
                                                    <?php
                                                    $item_id_txt = history_escape(
                                                        $detail["item_id_txt"] ?? "-"
                                                    );

                                                    $item_name = history_escape(
                                                        $detail["item_name"] ?? "-"
                                                    );

                                                    $quantity = (int) (
                                                        $detail["quantity"] ?? 0
                                                    );

                                                    $item_price = (float) (
                                                        $detail["item_price"] ?? 0
                                                    );

                                                    $subtotal = isset($detail["subtotal"])
                                                        ? (float) $detail["subtotal"]
                                                        : $item_price * $quantity;
                                                    ?>

                                                    <div class="product-row">
                                                        <div class="item-name">
                                                            <?= $item_name; ?>
                                                        </div>

                                                        <div class="product-meta">
                                                            ID: <?= $item_id_txt; ?>
                                                            &middot;
                                                            <?= number_format(
                                                                $quantity,
                                                                0,
                                                                ",",
                                                                "."
                                                            ); ?> pcs
                                                        </div>

                                                        <div class="product-meta">
                                                            <?= history_rupiah($item_price); ?>
                                                            &times;
                                                            <?= number_format(
                                                                $quantity,
                                                                0,
                                                                ",",
                                                                "."
                                                            ); ?>
                                                            =
                                                            <span class="subtotal">
                                                                Rp <?= history_rupiah($subtotal); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="money-value">
                                        Rp <?= history_rupiah($transaction_total); ?>
                                    </td>

                                    <td class="money-value">
                                        Rp <?= history_rupiah($transaction_cash); ?>
                                    </td>

                                    <td>
                                        <span class="money-value <?= $change_class; ?>">
                                            <?= $transaction_change < 0 ? "-" : ""; ?>Rp
                                            <?= history_rupiah(abs($transaction_change)); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="date-value">
                                            <?= $transaction_created; ?>
                                        </span>
                                    </td>

                                    <td>
                                        <a
                                            class="btn-delete"
                                            href="../../src/server/setter/remove_transaction.php?key=<?= $transaction_id; ?>"
                                            title="Hapus transaksi"
                                            aria-label="Hapus transaksi <?= $transaction_id_txt; ?>"
                                            onclick="return confirm('Yakin ingin menghapus transaksi ini?');"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <p class="table-hint">
                    <i class="fas fa-circle-info me-1"></i>
                    Total transaksi dihitung satu kali per transaksi. Menghapus transaksi
                    juga seharusnya menghapus detail produknya.
                </p>
            </div>
        </section>
    </main>

    <section data-root="footer"></section>

    <script src="../../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"
            integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
            crossorigin="anonymous"></script>

    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function () {
            $("#table_id").DataTable({
                scrollX: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_–_END_ dari _TOTAL_ transaksi",
                    infoEmpty: "Belum ada transaksi",
                    zeroRecords: "Transaksi tidak ditemukan",
                    emptyTable: "Belum ada data transaksi",
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
    <script src="../../src/views/History.js" type="module"></script>
</body>
</html>