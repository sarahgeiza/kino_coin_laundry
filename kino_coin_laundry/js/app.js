// Kino Coin Laundry - Client Interactions

document.addEventListener("DOMContentLoaded", function () {
  initTransactionForm();
  initTutupShiftForm();
  initModalHandlers();
});

// Format Rupiah helper
function formatRupiah(number) {
  return "Rp " + Number(number).toLocaleString("id-ID");
}

// Transaction Form live calculator
function initTransactionForm() {
  const form = document.getElementById("trx-form");
  if (!form) return;

  const serviceSelect = document.getElementById("service-select");
  const weightInput = document.getElementById("weight-input");
  const capacityBar = document.getElementById("capacity-bar");
  const capacityText = document.getElementById("capacity-text");
  const totalBillDisplay = document.getElementById("total-bill");
  const machineCoinDisplay = document.getElementById("machine-coin-count");

  function calculate() {
    const selectedOption = serviceSelect
      ? serviceSelect.options[serviceSelect.selectedIndex]
      : null;
    const ratePerKg = selectedOption
      ? Number(selectedOption.dataset.price || 0)
      : 0;
    const machineCoins = selectedOption
      ? Number(selectedOption.dataset.coins || 0)
      : 0;
    const weight = Number(weightInput?.value || 0);

    if (capacityBar && capacityText) {
      const pct = Math.min((weight / 7.5) * 100, 100);
      capacityBar.style.width = pct + "%";
      if (weight > 7.5) {
        capacityBar.style.backgroundColor = "#dc2626";
        capacityText.textContent = "Berat melebihi kapasitas mesin (maks. 7,5 kg).";
        capacityText.style.color = "#dc2626";
      } else {
        capacityBar.style.backgroundColor = "#3b82f6";
        capacityText.textContent = "Maksimal 7,5 kg per transaksi.";
        capacityText.style.color = "#64748b";
      }
    }

    const total = Math.round(weight * ratePerKg);
    if (totalBillDisplay) totalBillDisplay.textContent = formatRupiah(total);
    if (machineCoinDisplay) machineCoinDisplay.textContent = machineCoins + " koin";
  }

  serviceSelect?.addEventListener("change", calculate);
  weightInput?.addEventListener("input", calculate);
  calculate();
}

// Tutup Shift reconciliation calculator
function initTutupShiftForm() {
  const fisikInput = document.getElementById("input-fisik-kas");
  const totalSistemEl = document.getElementById("total-sistem-val");
  const selisihDisplay = document.getElementById("selisih-val");

  if (!fisikInput || !totalSistemEl || !selisihDisplay) return;

  const totalSistem = parseInt(totalSistemEl.getAttribute("data-value") || 0);

  function checkDiff() {
    const fisik = parseInt(fisikInput.value || 0);
    const selisih = fisik - totalSistem;

    if (selisih === 0) {
      selisihDisplay.textContent = "Rp 0 (Sesuai)";
      selisihDisplay.style.color = "#16a34a";
    } else if (selisih > 0) {
      selisihDisplay.textContent = "+" + formatRupiah(selisih) + " (Lebih)";
      selisihDisplay.style.color = "#2563eb";
    } else {
      selisihDisplay.textContent =
        "-" + formatRupiah(Math.abs(selisih)) + " (Selisih Kurang)";
      selisihDisplay.style.color = "#dc2626";
    }
  }

  fisikInput.addEventListener("input", checkDiff);
  checkDiff();
}

// Modal dialog helpers
function initModalHandlers() {
  document.querySelectorAll("[data-close-modal]").forEach((btn) => {
    btn.addEventListener("click", function () {
      const modal = this.closest(".modal-overlay");
      if (modal) modal.classList.remove("active");
    });
  });

  // Close when clicking backdrop
  document.querySelectorAll(".modal-overlay").forEach((modal) => {
    modal.addEventListener("click", function (e) {
      if (e.target === this) {
        this.classList.remove("active");
      }
    });
  });
}

function openModal(modalId) {
  const m = document.getElementById(modalId);
  if (m) m.classList.add("active");
}

function closeModal(modalId) {
  const m = document.getElementById(modalId);
  if (m) m.classList.remove("active");
}
