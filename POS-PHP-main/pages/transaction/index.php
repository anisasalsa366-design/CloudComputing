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

if (!is_array($inventory_products)) {
    $inventory_products = [];
}

?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f6f8fc">

    <link rel="manifest" href="../../manifest.json">
    <link rel="icon" href="../../src/public/images/icons/icon-144x144.png"
          sizes="144x144" type="image/png">

    <link href="../../node_modules/bootstrap/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link rel="stylesheet"
          href="../../node_modules/@fortawesome/fontawesome-free/css/all.css">

    <title>Buat Transaksi | POS</title>

    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --page-bg: #f6f8fc;
            --text-main: #172033;
            --text-muted: #7b8498;
            --border: #edf0f5;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--page-bg);
            color: var(--text-main);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
        }

        .transaction-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 24px 52px;
        }

        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 40px;
            padding: 0 15px;
            border: 1px solid #e5e9f2;
            border-radius: 10px;
            background: #fff;
            color: var(--primary-dark);
            font-size: 13px;
            font-weight: 750;
            text-decoration: none;
            transition: background .2s ease;
        }

        .btn-home:hover {
            background: #eef2ff;
            color: #4338ca;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin: 20px 0 26px;
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
            font-weight: 800;
            letter-spacing: -1px;
        }

        .page-subtitle {
            margin: 9px 0 0;
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.7;
        }

        .page-heading-icon {
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

        .transaction-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.55fr) minmax(290px, .85fr);
            align-items: start;
            gap: 22px;
        }

        .form-panel, .summary-panel {
            min-width: 0;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 5px 22px rgba(24, 39, 75, .035);
        }

        .panel-heading {
            padding: 22px 25px;
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
            line-height: 1.6;
        }

        .form-content { padding: 25px; }

        .field-label {
            display: block;
            margin-bottom: 9px;
            color: #414b60;
            font-size: 12px;
            font-weight: 750;
        }

        .required { color: #ef4444; }

        .form-control, .form-select, .input-group-text {
            min-height: 47px;
            border-color: #e5e9f2;
            font-size: 13px;
        }

        .form-control, .form-select { border-radius: 10px; }

        .form-control:focus, .form-select:focus {
            border-color: #a5b4fc;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .1);
        }

        .input-group .input-group-text {
            border-color: #e5e9f2;
            background: #f8f9fc;
            color: #68738a;
            font-size: 12px;
            font-weight: 700;
        }

        .form-text {
            margin-top: 7px;
            color: #98a1b2;
            font-size: 11px;
            line-height: 1.6;
        }

        .form-divider {
            height: 1px;
            margin: 24px 0;
            background: #f0f2f7;
        }

        .btn-add {
            width: 100%;
            min-height: 46px;
            margin-top: 15px;
            border: 0;
            border-radius: 10px;
            background: var(--primary);
            color: #fff;
            font-size: 13px;
            font-weight: 750;
        }

        .btn-add:hover { background: var(--primary-dark); color: #fff; }

        .cart-table-wrap { width: 100%; overflow-x: auto; }
        .cart-table { width: 100%; margin: 0; vertical-align: middle; }

        .cart-table th {
            padding: 12px 10px;
            background: #f8f9fc;
            color: #68738a;
            font-size: 11px;
            white-space: nowrap;
        }

        .cart-table td {
            padding: 13px 10px;
            border-color: #f0f2f7;
            font-size: 12px;
        }

        .cart-product-name {
            min-width: 120px;
            font-weight: 750;
            overflow-wrap: anywhere;
        }

        .cart-product-price {
            margin-top: 4px;
            color: var(--text-muted);
            font-size: 11px;
        }

        .cart-quantity {
            width: 76px;
            min-width: 76px;
            min-height: 38px !important;
            padding: 6px 8px;
            border-radius: 8px !important;
        }

        .cart-subtotal { font-weight: 800; white-space: nowrap; }

        .btn-remove {
            display: inline-grid;
            width: 34px;
            height: 34px;
            place-items: center;
            border: 1px solid #fee2e2;
            border-radius: 9px;
            background: #fff5f5;
            color: #dc2626;
        }

        .btn-remove:hover { background: #fee2e2; }

        .empty-cart {
            padding: 28px 15px !important;
            color: var(--text-muted);
            text-align: center;
        }

        .cart-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 17px 20px;
            border-top: 1px solid var(--border);
            background: #fff;
        }

        .cart-footer .label, .summary-total .label {
            color: var(--text-muted);
            font-size: 12px;
        }

        .cart-footer .value {
            color: var(--primary-dark);
            font-size: 17px;
            font-weight: 850;
        }

        .summary-panel {
            position: sticky;
            top: 22px;
            overflow: hidden;
        }

        .summary-header {
            padding: 23px 24px;
            background: linear-gradient(125deg, #312e81, #6366f1);
            color: #fff;
        }

        .summary-header .small-label {
            color: #c7d2fe;
            font-size: 11px;
            font-weight: 750;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .summary-header h2 {
            margin: 8px 0 0;
            font-size: 19px;
            font-weight: 800;
        }

        .summary-body { padding: 23px 24px; }

        .summary-line {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 12px;
        }

        .summary-line .label { color: var(--text-muted); }

        .summary-line .value {
            max-width: 65%;
            color: var(--text-main);
            font-weight: 750;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .summary-total {
            margin: 23px 0;
            padding: 20px;
            border: 1px solid #e0e7ff;
            border-radius: 14px;
            background: #f5f6ff;
        }

        .summary-total .label { color: #656e89; font-weight: 650; }

        .summary-total .amount {
            margin-top: 8px;
            color: var(--primary-dark);
            font-size: clamp(24px, 3vw, 30px);
            font-weight: 850;
            letter-spacing: -.8px;
            overflow-wrap: anywhere;
        }

        .payment-field { margin-top: 22px; }

        .change-box {
            margin-top: 18px;
            padding: 15px;
            border: 1px solid #d1fae5;
            border-radius: 12px;
            background: #f0fdf4;
        }

        .change-box.is-short { border-color: #fecaca; background: #fef2f2; }

        .change-box .label {
            color: #15803d;
            font-size: 11px;
            font-weight: 750;
        }

        .change-box.is-short .label, .change-box.is-short .amount {
            color: #b91c1c;
        }

        .change-box .amount {
            margin-top: 6px;
            color: #166534;
            font-size: 23px;
            font-weight: 850;
            overflow-wrap: anywhere;
        }

        .payment-status {
            margin-top: 9px;
            font-size: 11px;
            font-weight: 650;
            line-height: 1.5;
        }

        .button-stack { display: grid; gap: 10px; margin-top: 24px; }

        .btn-submit, .btn-reset {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 47px;
            border-radius: 11px;
            font-size: 13px;
            font-weight: 750;
        }

        .btn-submit { border: 0; background: var(--primary); color: #fff; }

        .btn-submit:hover:not(:disabled) {
            background: var(--primary-dark);
            color: #fff;
        }

        .btn-submit:disabled { cursor: not-allowed; opacity: .55; }

        .btn-reset {
            border: 1px solid #e5e9f2;
            background: #fff;
            color: #59647a;
        }

        .info-note {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 19px;
            padding: 13px;
            border-radius: 11px;
            background: #f8f9fc;
            color: var(--text-muted);
            font-size: 11px;
            line-height: 1.7;
        }

        .info-note i { margin-top: 2px; color: var(--primary); }

        @media (max-width: 850px) {
            .transaction-layout { grid-template-columns: 1fr; }
            .summary-panel { position: static; }
        }

        @media (max-width: 576px) {
            .transaction-container { padding: 22px 14px 35px; }
            .page-heading { align-items: flex-start; margin-top: 15px; }
            .page-heading-icon { width: 47px; height: 47px; flex-basis: 47px; }
            .panel-heading, .form-content { padding: 19px; }
            .summary-header, .summary-body { padding: 20px; }
        }
    </style>
</head>

<body>
    <section data-root="navbar"></section>
    <section data-root="jumbotron"></section>

    <main class="transaction-container">
        <div data-root="breadcumb"></div>

        <div class="mb-3">
            <!-- Home berada di root POS-PHP-main/index.php -->
            <a href="../../index.php" class="btn-home">
                <i class="fas fa-house"></i>
                Home
            </a>
        </div>

        <header class="page-heading">
            <div>
                <div class="eyebrow">Point of Sale System</div>
                <h1 class="page-title">Buat Transaksi</h1>
                <p class="page-subtitle">
                    Tambahkan beberapa produk ke keranjang dalam satu transaksi.
                </p>
            </div>
            <div class="page-heading-icon" aria-hidden="true">
                <i class="fas fa-cart-shopping"></i>
            </div>
        </header>

        <?php print_alert(); ?>

        <form id="transactionForm" method="POST"
              action="../../src/server/setter/set_transaction.php">
            <input type="hidden" name="cart_json" id="cartJson" value="">

            <div class="transaction-layout">
                <div>
                    <section class="form-panel">
                        <div class="panel-heading">
                            <h2>
                                <i class="fas fa-file-invoice me-2" style="color:#6366f1;"></i>
                                Detail Transaksi
                            </h2>
                            <p>Pilih produk, tentukan jumlah, lalu masukkan ke keranjang.</p>
                        </div>

                        <div class="form-content">
                            <div class="mb-4">
                                <label for="idTransaction" class="field-label">
                                    ID Transaksi <span class="required">*</span>
                                </label>
                                <input type="text"
                                       placeholder="Contoh: TRX0001"
                                       name="transaction_id_txt"
                                       class="form-control"
                                       id="idTransaction"
                                       maxlength="15"
                                       pattern="[A-Za-z0-9_-]+"
                                       required>
                                <div class="form-text">Gunakan ID unik untuk setiap transaksi.</div>
                            </div>

                            <div class="form-divider"></div>

                            <div class="mb-3">
                                <label for="nameOfItem" class="field-label">
                                    Pilih Produk <span class="required">*</span>
                                </label>
                                <select class="form-select" id="nameOfItem">
                                    <option value="">-- Pilih produk --</option>

                                    <?php foreach ($inventory_products as $item): ?>
                                        <?php
                                        $sku = (string) ($item["item_id_txt"] ?? "");
                                        $name = (string) ($item["item_name"] ?? "");
                                        $price = $item["item_price"] ?? 0;
                                        $stock = (int) ($item["stock"] ?? 0);

                                        if ($sku === "" || !is_numeric($price)) {
                                            continue;
                                        }

                                        $option_data = json_encode([
                                            "sku" => $sku,
                                            "name" => $name,
                                            "price" => (float) $price,
                                            "stock" => $stock
                                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);

                                        $safe_name = htmlspecialchars($name, ENT_QUOTES, "UTF-8");
                                        ?>
                                        <option
                                            value="<?= htmlspecialchars($option_data, ENT_QUOTES, "UTF-8"); ?>"
                                            <?= $stock < 1 ? "disabled" : ""; ?>>
                                            <?= $safe_name; ?> — Rp <?= number_format((float) $price, 0, ",", "."); ?>
                                            (Stok: <?= $stock; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="amountOfItem" class="field-label">
                                    Jumlah <span class="required">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" min="1" step="1" value="1"
                                           class="form-control" id="amountOfItem"
                                           inputmode="numeric">
                                    <span class="input-group-text">Pcs</span>
                                </div>
                                <div class="form-text" id="stockHint">
                                    Pilih produk untuk melihat stok tersedia.
                                </div>
                            </div>

                            <button type="button" class="btn btn-add" id="addToCart">
                                <i class="fas fa-plus me-2"></i>
                                Tambah ke Keranjang
                            </button>
                        </div>
                    </section>

                    <section class="form-panel mt-4">
                        <div class="panel-heading">
                            <h2>
                                <i class="fas fa-basket-shopping me-2" style="color:#6366f1;"></i>
                                Keranjang Belanja
                            </h2>
                            <p id="cartCount">Belum ada barang di keranjang.</p>
                        </div>

                        <div class="cart-table-wrap">
                            <table class="table cart-table">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Harga</th>
                                        <th>Jumlah</th>
                                        <th>Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="cartBody">
                                    <tr>
                                        <td colspan="5" class="empty-cart">
                                            <i class="fas fa-cart-shopping me-2"></i>
                                            Keranjang masih kosong.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="cart-footer">
                            <div>
                                <div class="label">Total jenis barang</div>
                                <div class="value" id="cartItemKinds">0 produk</div>
                            </div>
                            <div class="text-end">
                                <div class="label">Total kuantitas</div>
                                <div class="value" id="cartQuantity">0 pcs</div>
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="summary-panel">
                    <div class="summary-header">
                        <div class="small-label">Ringkasan pembayaran</div>
                        <h2><i class="fas fa-wallet me-2"></i>Total Belanja</h2>
                    </div>

                    <div class="summary-body">
                        <div class="summary-line">
                            <span class="label">Jenis produk</span>
                            <span class="value" id="summaryProduct">0 produk</span>
                        </div>
                        <div class="summary-line">
                            <span class="label">Total jumlah</span>
                            <span class="value" id="summaryQuantity">0 pcs</span>
                        </div>

                        <div class="summary-total">
                            <div class="label">Total yang harus dibayar</div>
                            <div class="amount">Rp <span id="summaryTotal">0</span></div>
                        </div>

                        <div class="payment-field">
                            <label for="cash" class="field-label">
                                Uang Pembayaran <span class="required">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text"
                                       placeholder="Contoh: 50.000"
                                       name="transaction_cash"
                                       class="form-control"
                                       id="cash"
                                       value=""
                                       inputmode="numeric"
                                       autocomplete="off"
                                       required>
                            </div>
                        </div>

                        <div class="change-box" id="changeBox">
                            <div class="label" id="changeLabel">
                                <i class="fas fa-money-bill-transfer me-1"></i>
                                Uang Kembalian
                            </div>
                            <div class="amount">Rp <span id="summaryChange">0</span></div>
                            <div class="payment-status" id="paymentStatus" aria-live="polite">
                                Tambahkan produk ke keranjang terlebih dahulu.
                            </div>
                        </div>

                        <div class="button-stack">
                            <button name="create_new_transaction"
                                    id="submitTransaction"
                                    class="btn btn-submit"
                                    type="submit"
                                    disabled>
                                <i class="fas fa-check-circle"></i>
                                Simpan Transaksi
                            </button>
                            <button class="btn btn-reset" type="reset">
                                <i class="fas fa-rotate-left"></i>
                                Reset Form
                            </button>
                        </div>

                        <div class="info-note">
                            <i class="fas fa-shield-halved"></i>
                            <span>
                                Harga final dan ketersediaan stok tetap diverifikasi
                                oleh Inventory saat transaksi diproses.
                            </span>
                        </div>
                    </div>
                </aside>
            </div>
        </form>
    </main>

    <section data-root="footer"></section>

    <script src="../../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../src/main.js" type="module"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const form = document.getElementById("transactionForm");
            const productSelect = document.getElementById("nameOfItem");
            const quantityInput = document.getElementById("amountOfItem");
            const stockHint = document.getElementById("stockHint");
            const addButton = document.getElementById("addToCart");
            const cartBody = document.getElementById("cartBody");
            const cartJson = document.getElementById("cartJson");
            const cashInput = document.getElementById("cash");
            const submitButton = document.getElementById("submitTransaction");
            const changeBox = document.getElementById("changeBox");
            const paymentStatus = document.getElementById("paymentStatus");

            const summaryProduct = document.getElementById("summaryProduct");
            const summaryQuantity = document.getElementById("summaryQuantity");
            const summaryTotal = document.getElementById("summaryTotal");
            const summaryChange = document.getElementById("summaryChange");

            const cartCount = document.getElementById("cartCount");
            const cartItemKinds = document.getElementById("cartItemKinds");
            const cartQuantity = document.getElementById("cartQuantity");

            const cart = new Map();

            function formatRupiah(value) {
                return Math.round(value).toLocaleString("id-ID");
            }

            function getSelectedProduct() {
                if (!productSelect.value) return null;

                try {
                    const item = JSON.parse(productSelect.value);

                    if (
                        !item ||
                        typeof item.sku !== "string" ||
                        !Number.isFinite(Number(item.price)) ||
                        !Number.isInteger(Number(item.stock))
                    ) {
                        return null;
                    }

                    return {
                        sku: item.sku,
                        name: String(item.name),
                        price: Number(item.price),
                        stock: Number(item.stock)
                    };
                } catch (error) {
                    return null;
                }
            }

            function getCash() {
                const digits = String(cashInput.value || "").replace(/\D/g, "");
                const value = Number(digits);
                return Number.isSafeInteger(value) ? value : 0;
            }

            function getCartTotals() {
                let total = 0;
                let quantity = 0;

                for (const item of cart.values()) {
                    total += item.price * item.quantity;
                    quantity += item.quantity;
                }

                return { total, quantity, kinds: cart.size };
            }

            function renderCart() {
                cartBody.replaceChildren();

                if (cart.size === 0) {
                    const row = document.createElement("tr");
                    const cell = document.createElement("td");

                    cell.colSpan = 5;
                    cell.className = "empty-cart";
                    cell.textContent = "Keranjang masih kosong.";

                    row.appendChild(cell);
                    cartBody.appendChild(row);
                } else {
                    for (const item of cart.values()) {
                        const row = document.createElement("tr");

                        const nameCell = document.createElement("td");
                        const name = document.createElement("div");
                        name.className = "cart-product-name";
                        name.textContent = item.name;

                        const sku = document.createElement("div");
                        sku.className = "cart-product-price";
                        sku.textContent = "SKU: " + item.sku;
                        nameCell.append(name, sku);

                        const priceCell = document.createElement("td");
                        priceCell.textContent = "Rp " + formatRupiah(item.price);

                        const quantityCell = document.createElement("td");
                        const quantity = document.createElement("input");

                        quantity.type = "number";
                        quantity.min = "1";
                        quantity.max = String(item.stock);
                        quantity.step = "1";
                        quantity.value = String(item.quantity);
                        quantity.className = "form-control cart-quantity";
                        quantity.setAttribute("aria-label", "Jumlah " + item.name);

                        quantity.addEventListener("change", function () {
                            const next = Number(quantity.value);

                            if (!Number.isInteger(next) || next < 1 || next > item.stock) {
                                alert(
                                    "Jumlah harus antara 1 dan " +
                                    item.stock + " untuk " + item.name + "."
                                );
                                quantity.value = String(item.quantity);
                                return;
                            }

                            item.quantity = next;
                            renderCart();
                        });

                        quantityCell.appendChild(quantity);

                        const subtotalCell = document.createElement("td");
                        subtotalCell.className = "cart-subtotal";
                        subtotalCell.textContent =
                            "Rp " + formatRupiah(item.price * item.quantity);

                        const actionCell = document.createElement("td");
                        const removeButton = document.createElement("button");

                        removeButton.type = "button";
                        removeButton.className = "btn-remove";
                        removeButton.title = "Hapus " + item.name;
                        removeButton.setAttribute("aria-label", "Hapus " + item.name);

                        const removeIcon = document.createElement("i");
                        removeIcon.className = "fas fa-trash";
                        removeButton.appendChild(removeIcon);

                        removeButton.addEventListener("click", function () {
                            cart.delete(item.sku);
                            renderCart();
                        });

                        actionCell.appendChild(removeButton);

                        row.append(
                            nameCell,
                            priceCell,
                            quantityCell,
                            subtotalCell,
                            actionCell
                        );

                        cartBody.appendChild(row);
                    }
                }

                const totals = getCartTotals();

                cartCount.textContent = totals.kinds === 0
                    ? "Belum ada barang di keranjang."
                    : totals.kinds + " jenis produk dalam keranjang.";

                cartItemKinds.textContent = totals.kinds + " produk";
                cartQuantity.textContent = totals.quantity + " pcs";
                summaryProduct.textContent = totals.kinds + " produk";
                summaryQuantity.textContent = totals.quantity + " pcs";
                summaryTotal.textContent = formatRupiah(totals.total);

                cartJson.value = JSON.stringify(
                    Array.from(cart.values()).map(function (item) {
                        return { sku: item.sku, quantity: item.quantity };
                    })
                );

                updatePayment();
            }

            function updatePayment() {
                const totals = getCartTotals();
                const cash = getCash();
                const difference = cash - totals.total;

                const validCart =
                    totals.kinds > 0 &&
                    totals.total > 0 &&
                    Number.isSafeInteger(totals.total);

                const enoughPayment = validCart && cash >= totals.total;

                summaryChange.textContent =
                    (difference < 0 ? "-" : "") +
                    formatRupiah(Math.abs(difference));

                changeBox.classList.toggle(
                    "is-short",
                    validCart && cash < totals.total
                );

                if (!validCart) {
                    paymentStatus.textContent =
                        "Tambahkan produk ke keranjang terlebih dahulu.";
                    paymentStatus.style.color = "#7b8498";
                } else if (cash < totals.total) {
                    paymentStatus.textContent =
                        "Uang kurang Rp " + formatRupiah(totals.total - cash) + ".";
                    paymentStatus.style.color = "#b91c1c";
                } else {
                    paymentStatus.textContent = "Pembayaran mencukupi.";
                    paymentStatus.style.color = "#15803d";
                }

                submitButton.disabled = !enoughPayment;
            }

            productSelect.addEventListener("change", function () {
                const product = getSelectedProduct();

                if (!product) {
                    stockHint.textContent =
                        "Pilih produk untuk melihat stok tersedia.";
                    quantityInput.max = "";
                    return;
                }

                quantityInput.max = String(product.stock);
                stockHint.textContent = "Stok tersedia: " + product.stock + " pcs.";

                if (Number(quantityInput.value) > product.stock) {
                    quantityInput.value = String(Math.max(1, product.stock));
                }
            });

            addButton.addEventListener("click", function () {
                const product = getSelectedProduct();
                const quantity = Number(quantityInput.value);

                if (!product) {
                    alert("Pilih produk terlebih dahulu.");
                    return;
                }

                if (
                    !Number.isInteger(quantity) ||
                    quantity < 1 ||
                    quantity > product.stock
                ) {
                    alert("Jumlah harus antara 1 dan " + product.stock + " pcs.");
                    return;
                }

                const existing = cart.get(product.sku);
                const newQuantity = (existing ? existing.quantity : 0) + quantity;

                if (newQuantity > product.stock) {
                    alert(
                        "Jumlah " + product.name +
                        " di keranjang melebihi stok yang ditampilkan (" +
                        product.stock + " pcs)."
                    );
                    return;
                }

                if (existing) {
                    existing.quantity = newQuantity;
                } else {
                    cart.set(product.sku, {
                        sku: product.sku,
                        name: product.name,
                        price: product.price,
                        stock: product.stock,
                        quantity: quantity
                    });
                }

                renderCart();
                quantityInput.value = "1";
            });

            cashInput.addEventListener("input", function () {
                const digits = String(cashInput.value || "").replace(/\D/g, "");
                const number = Number(digits);

                cashInput.value = digits && Number.isSafeInteger(number)
                    ? formatRupiah(number)
                    : "";

                updatePayment();
            });

            form.addEventListener("submit", function (event) {
                const totals = getCartTotals();
                const cash = getCash();

                updatePayment();

                if (
                    cart.size === 0 ||
                    !Number.isSafeInteger(totals.total) ||
                    totals.total <= 0 ||
                    cash < totals.total
                ) {
                    event.preventDefault();
                    alert("Periksa keranjang dan pastikan pembayaran mencukupi.");
                    return;
                }

                if (!cartJson.value) {
                    event.preventDefault();
                    alert("Data keranjang belum siap.");
                    return;
                }

                submitButton.disabled = true;
                submitButton.innerHTML =
                    '<i class="fas fa-spinner fa-spin"></i> Memproses transaksi...';
            });

            form.addEventListener("reset", function () {
                window.setTimeout(function () {
                    cart.clear();
                    productSelect.value = "";
                    quantityInput.value = "1";
                    quantityInput.max = "";
                    cashInput.value = "";
                    stockHint.textContent =
                        "Pilih produk untuk melihat stok tersedia.";

                    submitButton.innerHTML =
                        '<i class="fas fa-check-circle"></i> Simpan Transaksi';

                    renderCart();
                }, 0);
            });

            renderCart();
        });
    </script>
</body>
</html>