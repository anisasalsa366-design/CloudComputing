<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require("./src/server/connection.php");
require("./src/server/getter/get_items.php");
require("./src/server/getter/get_transactions.php");
require("./src/server/helper/auth.php");
require("./src/server/helper/print_profile.php");

if (!auth()) {
    header("Location: ./pages/login");
    exit;
}

// Total transaksi tetap dihitung dari database POS.
$transactions = count(get_transactions($conn));

// Total item dihitung dari Inventory API,
// sama seperti sumber data halaman Manage Items.
$inventory_products = get_inventory_products();
$items = count($inventory_products);
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f6f8fc">

    <link rel="manifest" href="./manifest.json">
    <link rel="icon"
          href="./public/images/icons/icon-144x144.png"
          sizes="144x144"
          type="image/png">

    <link href="./node_modules/bootstrap/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link rel="stylesheet"
          href="./node_modules/@fortawesome/fontawesome-free/css/all.css">

    <title>Dashboard | POS</title>

    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --text-main: #172033;
            --text-muted: #7b8498;
            --border: #edf0f5;
            --page-bg: #f6f8fc;
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

        .dashboard-container {
            width: 100%;
            max-width: 1440px;
            margin: 0 auto;
            padding: 32px 28px 48px;
        }

        .dashboard-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 28px;
        }

        .eyebrow {
            margin-bottom: 8px;
            color: var(--primary);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .dashboard-title {
            margin: 0;
            font-size: clamp(27px, 3vw, 34px);
            font-weight: 800;
            letter-spacing: -1.2px;
        }

        .dashboard-subtitle {
            margin: 10px 0 0;
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.7;
        }

        .date-pill {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 11px 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff;
            color: #586176;
            font-size: 12px;
            font-weight: 650;
            white-space: nowrap;
        }

        .date-pill i {
            color: var(--primary);
        }

        .welcome-panel {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            min-height: 205px;
            margin-bottom: 26px;
            padding: 32px 36px;
            border-radius: 20px;
            background:
                radial-gradient(circle at 85% 20%, rgba(165,180,252,.32), transparent 27%),
                linear-gradient(120deg, #1e1b4b, #3730a3 64%, #6366f1);
            color: #fff;
            box-shadow: 0 12px 30px rgba(79,70,229,.13);
        }

        .welcome-copy {
            position: relative;
            z-index: 1;
            max-width: 590px;
        }

        .welcome-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 13px;
            color: #c7d2fe;
            font-size: 12px;
            font-weight: 700;
        }

        .welcome-title {
            margin: 0;
            font-size: clamp(23px, 3vw, 31px);
            font-weight: 800;
            letter-spacing: -.7px;
            line-height: 1.3;
        }

        .welcome-description {
            max-width: 480px;
            margin: 12px 0 0;
            color: #e0e7ff;
            font-size: 13px;
            line-height: 1.8;
        }

        .welcome-art {
            position: relative;
            z-index: 1;
            display: grid;
            width: 115px;
            height: 115px;
            flex: 0 0 115px;
            place-items: center;
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 28px;
            background: rgba(255,255,255,.12);
            color: #fff;
            font-size: 46px;
            transform: rotate(4deg);
        }

        .welcome-panel::after {
            content: "";
            position: absolute;
            right: 70px;
            bottom: -120px;
            width: 250px;
            height: 250px;
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 50%;
            pointer-events: none;
        }

        .section-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: 30px 0 17px;
        }

        .section-title {
            margin: 0;
            font-size: 17px;
            font-weight: 800;
        }

        .section-caption {
            margin-top: 5px;
            color: var(--text-muted);
            font-size: 12px;
        }

        .stat-card {
            height: 100%;
            padding: 22px;
            border: 1px solid var(--border);
            border-radius: 17px;
            background: #fff;
            box-shadow: 0 4px 18px rgba(24,39,75,.025);
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

        .purple {
            background: #eef2ff;
            color: #6366f1;
        }

        .blue {
            background: #eaf5ff;
            color: #2584dc;
        }

        .green {
            background: #eafaf1;
            color: #12834e;
        }

        .stat-number {
            margin-top: 17px;
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -.8px;
        }

        .stat-description {
            margin-top: 5px;
            color: #98a1b2;
            font-size: 12px;
        }

        .quick-card {
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: 24px;
            border: 1px solid var(--border);
            border-radius: 17px;
            background: #fff;
            color: inherit;
            text-decoration: none;
            box-shadow: 0 4px 18px rgba(24,39,75,.025);
            transition: transform .2s, border-color .2s, box-shadow .2s;
        }

        .quick-card:hover {
            transform: translateY(-4px);
            border-color: #d9dcff;
            box-shadow: 0 12px 28px rgba(24,39,75,.07);
            color: inherit;
        }

        .quick-icon {
            display: grid;
            width: 49px;
            height: 49px;
            margin-bottom: 22px;
            place-items: center;
            border-radius: 15px;
            font-size: 20px;
        }

        .quick-title {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
        }

        .quick-description {
            flex: 1;
            margin: 9px 0 23px;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.8;
        }

        .quick-action {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 15px;
            border-top: 1px solid #f0f2f7;
            color: var(--primary);
            font-size: 12px;
            font-weight: 750;
        }

        .account-panel {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-top: 29px;
            padding: 21px 23px;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: #fff;
        }

        .account-label {
            margin-bottom: 5px;
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .8px;
            text-transform: uppercase;
        }

        .account-content {
            font-size: 13px;
        }

        .account-content p {
            margin: 0;
        }

        .logout-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border: 1px solid #fee2e2;
            border-radius: 10px;
            background: #fff7f7;
            color: #c24141;
            font-size: 12px;
            font-weight: 750;
            text-decoration: none;
            transition: background .2s;
        }

        .logout-button:hover {
            background: #fee2e2;
            color: #991b1b;
        }

        @media (max-width: 767px) {
            .dashboard-container {
                padding: 23px 15px 35px;
            }

            .dashboard-heading {
                flex-direction: column;
                gap: 15px;
            }

            .welcome-panel {
                min-height: auto;
                padding: 25px 22px;
                border-radius: 17px;
            }

            .welcome-art {
                width: 65px;
                height: 65px;
                flex-basis: 65px;
                border-radius: 17px;
                font-size: 26px;
            }

            .welcome-title {
                font-size: 23px;
            }

            .stat-card {
                padding: 18px;
            }

            .stat-number {
                font-size: 26px;
            }

            .quick-card {
                padding: 21px;
            }

            .account-panel {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 420px) {
            .welcome-art {
                display: none;
            }

            .date-pill {
                white-space: normal;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                transition: none !important;
            }
        }
    </style>
</head>

<body>
    <section data-root="navbar"></section>
    <section data-root="jumbotron"></section>

    <!-- Data yang digunakan komponen Home.js -->
    <input type="hidden" name="transactions"
           value="<?= (int) $transactions; ?>">
    <input type="hidden" name="items"
           value="<?= (int) $items; ?>">

    <main class="dashboard-container">
        <div class="dashboard-heading">
            <div>
                <div class="eyebrow">Point of Sale System</div>
                <h1 class="dashboard-title">Dashboard</h1>
                <p class="dashboard-subtitle">
                    Ringkasan operasional dan akses cepat untuk aktivitas kasir.
                </p>
            </div>

            <div class="date-pill">
                <i class="far fa-calendar-alt"></i>
                <?= htmlspecialchars(
                    date("d F Y"),
                    ENT_QUOTES,
                    "UTF-8"
                ); ?>
            </div>
        </div>

        <section class="welcome-panel">
            <div class="welcome-copy">
                <div class="welcome-label">
                    <i class="fas fa-star"></i>
                    PUSAT KONTROL POS
                </div>

                <h2 class="welcome-title">
                    Semua aktivitas bisnis, satu dashboard.
                </h2>

                <p class="welcome-description">
                    Pantau data produk, kelola transaksi, dan akses riwayat
                    penjualan melalui menu yang tersedia.
                </p>
            </div>

            <div class="welcome-art" aria-hidden="true">
                <i class="fas fa-cubes"></i>
            </div>
        </section>

        <div class="section-heading">
            <div>
                <h2 class="section-title">Ringkasan Sistem</h2>
                <div class="section-caption">
                    Ikhtisar data yang tersedia di POS dan Inventory
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-label">Total Transaksi</div>
                        <div class="stat-icon purple">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>

                    <div class="stat-number">
                        <?= number_format($transactions, 0, ",", "."); ?>
                    </div>

                    <div class="stat-description">
                        Jumlah transaksi yang tercatat di POS
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-4">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-label">Total Produk Inventory</div>
                        <div class="stat-icon blue">
                            <i class="fas fa-boxes-stacked"></i>
                        </div>
                    </div>

                    <div class="stat-number">
                        <?= number_format($items, 0, ",", "."); ?>
                    </div>

                    <div class="stat-description">
                        Jumlah produk yang terdaftar di Inventory
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-4">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-label">Status Sistem</div>
                        <div class="stat-icon green">
                            <i class="fas fa-shield-halved"></i>
                        </div>
                    </div>

                    <div class="stat-number" style="font-size: 24px;">
                        Siap Digunakan
                    </div>

                    <div class="stat-description">
                        Dashboard tersedia untuk akun yang terautentikasi
                    </div>
                </div>
            </div>
        </div>

        <div class="section-heading">
            <div>
                <h2 class="section-title">Akses Cepat</h2>
                <div class="section-caption">
                    Pilih menu untuk melanjutkan aktivitas
                </div>
            </div>
        </div>

        <!-- Card menu dirender oleh Home.js -->
        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-4">
                <div data-root="card"></div>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <div data-root="card"></div>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <div data-root="card"></div>
            </div>
        </div>

        <section class="account-panel">
            <div class="account-content">
                <div class="account-label">Akun yang sedang masuk</div>
                <?php print_profile(); ?>
            </div>

            <a class="logout-button" href="./src/server/helper/logout.php">
                <i class="fas fa-right-from-bracket"></i>
                Logout
            </a>
        </section>
    </main>

    <section data-root="footer"></section>

    <script src="./node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./src/main.js" type="module"></script>
    <script src="./src/views/Home.js" type="module"></script>
</body>
</html>