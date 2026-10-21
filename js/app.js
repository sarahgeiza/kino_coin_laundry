// Kino Coin Laundry - Client Interactions

document.addEventListener('DOMContentLoaded', function () {
    initTransactionForm();
    initTutupShiftForm();
    initModalHandlers();
});

// Format Rupiah helper
function formatRupiah(number) {
    return 'Rp ' + Number(number).toLocaleString('id-ID');
}

// Transaction Form live calculator
function initTransactionForm() {
    const form = document.getElementById('trx-form');
    if (!form) return;

    const serviceSelect = document.getElementById('service-select');
    const weightInput = document.getElementById('weight-input');
    const capacityBar = document.getElementById('capacity-bar');
    const capacityText = document.getElementById('capacity-text');

    const coin500Input = document.getElementById('coin-500-input');
    const coin1000Input = document.getElementById('coin-1000-input');
    const subtotal500 = document.getElementById('subtotal-500');
    const subtotal1000 = document.getElementById('subtotal-1000');

    const totalBillDisplay = document.getElementById('total-bill');
    const totalCoinPaidDisplay = document.getElementById('total-coin-paid');
    const billStatusDisplay = document.getElementById('bill-status');

    function calculate() {
        const selectedOption = serviceSelect ? serviceSelect.options[serviceSelect.selectedIndex] : null;
        const ratePerKg = selectedOption ? parseFloat(selectedOption.getAttribute('data-price') || 0) : 0;
        const weight = parseFloat(weightInput ? weightInput.value : 0) || 0;

        // Capacity bar (7.5 kg max)
        if (capacityBar && capacityText) {
            const pct = Math.min((weight / 7.5) * 100, 100);
            capacityBar.style.width = pct + '%';
            if (weight > 7.5) {
                capacityBar.style.backgroundColor = '#dc2626';
                capacityText.innerHTML = '⚠️ Berat melebihi kapasitas standar mesin (Maks. 7,5 kg)';
                capacityText.style.color = '#dc2626';
            } else {
                capacityBar.style.backgroundColor = '#3b82f6';
                capacityText.innerHTML = '⚠️ Maks. 7,5 kg — pastikan sesuai standar koin';
                capacityText.style.color = '#64748b';
            }
        }

        const totalBill = Math.round(weight * ratePerKg);
        if (totalBillDisplay) {
            totalBillDisplay.textContent = formatRupiah(totalBill);
        }

        const qty500 = parseInt(coin500Input ? coin500Input.value : 0) || 0;
        const qty1000 = parseInt(coin1000Input ? coin1000Input.value : 0) || 0;

        const val500 = qty500 * 500;
        const val1000 = qty1000 * 1000;

        if (subtotal500) subtotal500.textContent = formatRupiah(val500);
        if (subtotal1000) subtotal1000.textContent = formatRupiah(val1000);

        const totalCoinPaid = val500 + val1000;
        if (totalCoinPaidDisplay) {
            totalCoinPaidDisplay.textContent = formatRupiah(totalCoinPaid);
        }

        if (billStatusDisplay) {
            const diff = totalCoinPaid - totalBill;
            if (totalBill === 0 && totalCoinPaid === 0) {
                billStatusDisplay.className = 'bill-status';
                billStatusDisplay.textContent = 'Menunggu input berat & koin';
                billStatusDisplay.style.background = '#e2e8f0';
                billStatusDisplay.style.color = '#475569';
            } else if (diff < 0) {
                billStatusDisplay.className = 'bill-status status-kurang';
                billStatusDisplay.textContent = '⚠️ Kurang ' + formatRupiah(Math.abs(diff));
            } else if (diff === 0) {
                billStatusDisplay.className = 'bill-status status-lunas';
                billStatusDisplay.textContent = '✅ Lunas (Pas)';
            } else {
                billStatusDisplay.className = 'bill-status status-lunas';
                billStatusDisplay.textContent = '✅ Lunas (Lebih ' + formatRupiah(diff) + ')';
            }
        }
    }

    if (serviceSelect) serviceSelect.addEventListener('change', calculate);
    if (weightInput) weightInput.addEventListener('input', calculate);
    if (coin500Input) coin500Input.addEventListener('input', calculate);
    if (coin1000Input) coin1000Input.addEventListener('input', calculate);

    calculate();
}

// Tutup Shift reconciliation calculator
function initTutupShiftForm() {
    const fisikInput = document.getElementById('input-fisik-kas');
    const totalSistemEl = document.getElementById('total-sistem-val');
    const selisihDisplay = document.getElementById('selisih-val');

    if (!fisikInput || !totalSistemEl || !selisihDisplay) return;

    const totalSistem = parseInt(totalSistemEl.getAttribute('data-value') || 0);

    function checkDiff() {
        const fisik = parseInt(fisikInput.value || 0);
        const selisih = fisik - totalSistem;

        if (selisih === 0) {
            selisihDisplay.textContent = 'Rp 0 (Sesuai)';
            selisihDisplay.style.color = '#16a34a';
        } else if (selisih > 0) {
            selisihDisplay.textContent = '+' + formatRupiah(selisih) + ' (Lebih)';
            selisihDisplay.style.color = '#2563eb';
        } else {
            selisihDisplay.textContent = '-' + formatRupiah(Math.abs(selisih)) + ' (Selisih Kurang)';
            selisihDisplay.style.color = '#dc2626';
        }
    }

    fisikInput.addEventListener('input', checkDiff);
    checkDiff();
}

// Modal dialog helpers
function initModalHandlers() {
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', function () {
            const modal = this.closest('.modal-overlay');
            if (modal) modal.classList.remove('active');
        });
    });

    // Close when clicking backdrop
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });
}

function openModal(modalId) {
    const m = document.getElementById(modalId);
    if (m) m.classList.add('active');
}

function closeModal(modalId) {
    const m = document.getElementById(modalId);
    if (m) m.classList.remove('active');
}
