// Handler for Transaction page

import Breadcumb from "../components/Breadcumb.js";

// Render breadcrumb
const breadcumbEl = document.querySelector("[data-root=breadcumb]");
if (breadcumbEl) {
  Breadcumb(breadcumbEl, {
    home: "../../",
    current: "Transaction"
  });
}

// Get elements
const selectItemsEl = document.querySelector("[data-role=select-items]");
const itemIdEl = document.querySelector("[name=itemId]");
const pricePerUnitEl = document.querySelector("[name=pricePerUnit]");
const amountOfItemEl = document.querySelector("[name=amountOfItem]");
const totalEl = document.querySelector("[name=total]");
const cashEl = document.querySelector("[name=cash]");
const moneyChangesEl = document.querySelector("[name=moneyChanges]");
const transactionForm = document.querySelector(
  'form[action*="set_transaction.php"]'
);

// Parse a raw numeric value, including API prices such as "25000.00".
function parseRawNumber(value) {
  const text = String(value ?? "").trim();

  if (text === "") return 0;

  const number = Number(text);
  return Number.isFinite(number) ? number : 0;
}

// Parse a formatted Rupiah value while preserving a minus sign.
function parseFormattedRupiah(value) {
  const text = String(value ?? "").trim();

  if (text === "") return 0;

  const negative = text.startsWith("-");
  const digits = text.replace(/\D/g, "");

  if (digits === "") return 0;

  const number = Number(digits);
  return negative ? -number : number;
}

// Format a number with Indonesian thousands separators.
function formatRupiah(value) {
  return Math.round(value).toLocaleString("id-ID");
}

// Calculate total based on unit price and quantity.
function updateTotal() {
  const price = parseFormattedRupiah(pricePerUnitEl.value);
  const quantity = Math.max(
    0,
    parseInt(amountOfItemEl.value, 10) || 0
  );

  const total = price * quantity;

  totalEl.value = formatRupiah(total);
  updateChange();
}

// Calculate change. Negative means the customer still owes money.
function updateChange() {
  const cash = parseFormattedRupiah(cashEl.value);
  const total = parseFormattedRupiah(totalEl.value);

  const difference = cash - total;

  moneyChangesEl.value = difference < 0
    ? "-" + formatRupiah(Math.abs(difference))
    : formatRupiah(difference);

  // Optional summary fields used by the redesigned transaction page.
  const summaryTotal = document.getElementById("summaryTotal");
  const summaryChange = document.getElementById("summaryChange");

  if (summaryTotal) {
    summaryTotal.textContent = formatRupiah(total);
  }

  if (summaryChange) {
    summaryChange.textContent = moneyChangesEl.value;
  }
}

// Format payment input as the user types.
function formatCashInput() {
  const digits = String(cashEl.value ?? "").replace(/\D/g, "");

  cashEl.value = digits === ""
    ? ""
    : formatRupiah(Number(digits));

  updateChange();
}

// Product selection.
if (selectItemsEl) {
  selectItemsEl.addEventListener("change", (event) => {
    const selectedValue = event.target.value;

    if (!selectedValue) {
      itemIdEl.value = "";
      pricePerUnitEl.value = "";
      amountOfItemEl.value = 1;
      totalEl.value = "0";
      moneyChangesEl.value = "0";

      updateChange();
      updateSummaryProduct();
      return;
    }

    const value = selectedValue.split("@");
    const itemId = value[0];
    const price = parseRawNumber(value[2]);

    itemIdEl.value = itemId;
    pricePerUnitEl.value = formatRupiah(price);
    amountOfItemEl.value = 1;

    updateTotal();
    updateSummaryProduct();
  });
}

// Update selected product in the visual summary.
function updateSummaryProduct() {
  const summaryProduct = document.getElementById("summaryProduct");

  if (!summaryProduct || !selectItemsEl) return;

  const option = selectItemsEl.options[selectItemsEl.selectedIndex];

  summaryProduct.textContent = selectItemsEl.value && option
    ? option.text.split(" — ")[0]
    : "Belum dipilih";
}

// Update quantity and totals.
if (amountOfItemEl) {
  amountOfItemEl.addEventListener("input", () => {
    updateTotal();

    const summaryQuantity = document.getElementById("summaryQuantity");

    if (summaryQuantity) {
      summaryQuantity.textContent =
        (parseInt(amountOfItemEl.value, 10) || 0) + " pcs";
    }
  });
}

// Payment changes.
if (cashEl) {
  cashEl.addEventListener("input", formatCashInput);
}

// Prevent submission when payment is insufficient.
if (transactionForm) {
  transactionForm.addEventListener("submit", (event) => {
    const cash = parseFormattedRupiah(cashEl.value);
    const total = parseFormattedRupiah(totalEl.value);

    if (!selectItemsEl.value) {
      event.preventDefault();
      alert("Silakan pilih produk terlebih dahulu.");
      selectItemsEl.focus();
      return;
    }

    if (total <= 0) {
      event.preventDefault();
      alert("Total transaksi harus lebih dari Rp 0.");
      return;
    }

    if (cash < total) {
      event.preventDefault();
      alert(
        "Pembayaran belum mencukupi. Kurang Rp " +
        formatRupiah(total - cash) + "."
      );
      cashEl.focus();
    }
  });
}

// Initial summary.
updateSummaryProduct();
updateChange();