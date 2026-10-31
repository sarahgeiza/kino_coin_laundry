// ==========================================================================
// Kino Coin Laundry - Frontend Interactivity & Business Logic
// Aligned 100% with Figma Page 2 Design Overhaul
// ==========================================================================

document.addEventListener('DOMContentLoaded', function () {
    initModals();
    initTransactionCalculator();
    initCustomerAutocomplete();
    initUrlToasts();
});

// 1. Toast Notification Manager
function showToast(message, type = 'success', duration = 3000) {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let icon = '✔';
    if (type === 'error') icon = '⚠';
    if (type === 'dark') icon = 'ℹ';

    toast.innerHTML = `<span>${icon}</span><span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// Check URL params for toasts (e.g. ?toast=saved or ?logout=1)
function initUrlToasts() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('toast') === 'saved') {
        showToast('Transaksi tersimpan', 'success');
    }
    if (params.get('logout') === '1') {
        showToast('Anda telah logout', 'dark');
    }
    if (params.get('shift_started') === '1') {
        showToast('Shift berhasil dimulai', 'success');
    }
    if (params.get('shift_closed') === '1') {
        showToast('Shift berhasil ditutup', 'success');
    }
}

// 2. Modal Management (Bottom Sheet Overlay)
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function initModals() {
    // Close modal when clicking backdrop
    document.querySelectorAll('.sheet-overlay').forEach(overlay => {
        overlay.addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });
}

// 3. Password Visibility Toggle (A2, A2b, A3, A4, A5)
function togglePassword(inputId, btnEl) {
    const input = document.getElementById(inputId);
    if (!input) return;

    if (input.type === 'password') {
        input.type = 'text';
        btnEl.textContent = 'Sembunyi';
    } else {
        input.type = 'password';
        btnEl.textContent = 'Lihat';
    }
}

// 4. Form Transaksi Baru: Live Calculator & Token Reconciliation (K2, K2b, K2c, K2d, K2e, K2f)
function initTransactionCalculator() {
    const form = document.getElementById('formTransaksiBaru');
    if (!form) return;

    const serviceSelect = document.getElementById('serviceSelect');
    const qtyInput = document.getElementById('qtyInput');
    const coinWashInput = document.getElementById('coinWashInput');
    const coinDryInput = document.getElementById('coinDryInput');

    const tokenSection = document.getElementById('tokenInputSection');
    const tunaiAlertBox = document.getElementById('tunaiAlertBox');
    
    const totalBillEl = document.getElementById('totalBillDisplay');
    const totalPaidEl = document.getElementById('totalPaidDisplay');
    const statusBadgeEl = document.getElementById('statusBadgeDisplay');
    const kuantitasErrorEl = document.getElementById('kuantitasErrorAlert');

    function calculate() {
        const option = serviceSelect.options[serviceSelect.selectedIndex];
        if (!option) return;

        const rate = parseInt(option.getAttribute('data-price') || 0);
        const category = option.getAttribute('data-category') || 'SELF_SERVICE';
        const qty = parseInt(qtyInput.value) || 0;

        // Validation: Kuantitas > 0
        if (qty <= 0) {
            if (kuantitasErrorEl) kuantitasErrorEl.style.display = 'block';
        } else {
            if (kuantitasErrorEl) kuantitasErrorEl.style.display = 'none';
        }

        const totalBill = rate * qty;
        if (totalBillEl) totalBillEl.textContent = 'Rp ' + totalBill.toLocaleString('id-ID');

        // Check if Self Service (Token) or Drop Off/Additional (Tunai)
        if (category === 'SELF_SERVICE') {
            if (tokenSection) tokenSection.style.display = 'block';
            if (tunaiAlertBox) tunaiAlertBox.style.display = 'none';

            const qtyWash = parseInt(coinWashInput ? coinWashInput.value : 0) || 0;
            const qtyDry = parseInt(coinDryInput ? coinDryInput.value : 0) || 0;

            const valWash = qtyWash * 7000;
            const valDry = qtyDry * 10000;

            document.getElementById('subtotalWashDisplay').textContent = 'Rp ' + valWash.toLocaleString('id-ID');
            document.getElementById('subtotalDryDisplay').textContent = 'Rp ' + valDry.toLocaleString('id-ID');

            const totalPaid = valWash + valDry;
            if (totalPaidEl) totalPaidEl.textContent = 'Rp ' + totalPaid.toLocaleString('id-ID');

            const diff = totalPaid - totalBill;
            if (statusBadgeEl) {
                if (totalBill === 0 && totalPaid === 0) {
                    statusBadgeEl.className = 'badge';
                    statusBadgeEl.style.background = '#e2e8f0';
                    statusBadgeEl.style.color = '#475569';
                    statusBadgeEl.textContent = 'Menunggu input';
                } else if (diff < 0) {
                    statusBadgeEl.className = 'badge badge-kurang';
                    statusBadgeEl.textContent = '⚠ Kurang Rp ' + Math.abs(diff).toLocaleString('id-ID');
                } else if (diff === 0) {
                    statusBadgeEl.className = 'badge badge-lunas';
                    statusBadgeEl.textContent = '✔ Lunas (pas)';
                } else {
                    statusBadgeEl.className = 'badge badge-lunas';
                    statusBadgeEl.textContent = '✔ Lunas (lebih Rp ' + diff.toLocaleString('id-ID') + ')';
                }
            }
        } else {
            // Drop Off or Additional -> Tunai
            if (tokenSection) tokenSection.style.display = 'none';
            if (tunaiAlertBox) tunaiAlertBox.style.display = 'block';

            if (totalPaidEl) totalPaidEl.textContent = 'Rp ' + totalBill.toLocaleString('id-ID');

            if (statusBadgeEl) {
                statusBadgeEl.className = 'badge badge-lunas';
                statusBadgeEl.textContent = '✔ Lunas (Tunai)';
            }
        }
    }

    serviceSelect.addEventListener('change', calculate);
    qtyInput.addEventListener('input', calculate);
    if (coinWashInput) coinWashInput.addEventListener('input', calculate);
    if (coinDryInput) coinDryInput.addEventListener('input', calculate);

    // Initial calculation
    calculate();

    // Form submit guard
    form.addEventListener('submit', function (e) {
        const qty = parseInt(qtyInput.value) || 0;
        const name = document.getElementById('inputPelangganNama').value.trim();
        const wa = document.getElementById('inputPelangganWA').value.trim();

        if (qty <= 0 || !name || !wa) {
            e.preventDefault();
            if (qty <= 0 && kuantitasErrorEl) kuantitasErrorEl.style.display = 'block';
            showToast('Lengkapi nama, WA, dan kuantitas', 'error');
        }
    });
}

// 5. Autocomplete Pelanggan & Member Selection (K2b, K2c)
function initCustomerAutocomplete() {
    const input = document.getElementById('inputPelangganNama');
    const waInput = document.getElementById('inputPelangganWA');
    const suggestBox = document.getElementById('customerSuggestBox');
    const selectedBadge = document.getElementById('memberSelectedBadge');

    if (!input || !suggestBox) return;

    input.addEventListener('input', async function () {
        const val = this.value.trim();
        if (selectedBadge) selectedBadge.style.display = 'none';

        if (val.length === 0) {
            suggestBox.style.display = 'none';
            return;
        }

        const matches = await window.KinoAPI.searchMember(val);
        if (matches.length > 0) {
            suggestBox.innerHTML = '';
            matches.forEach(m => {
                const item = document.createElement('div');
                item.style.padding = '10px 14px';
                item.style.cursor = 'pointer';
                item.style.borderBottom = '1px solid #f1f5f9';
                item.innerHTML = `
                    <div style="font-weight:700; font-size:13px; color:#0f172a;">${m.nama} <span style="font-size:11px; color:#2563eb;">(${m.kode_kartu})</span></div>
                    <div style="font-size:11px; color:#64748b;">${m.wa} • ${m.is_member ? 'Member' : 'Non-Member'}</div>
                `;
                item.addEventListener('click', function () {
                    input.value = m.nama;
                    if (waInput) waInput.value = m.wa;
                    suggestBox.style.display = 'none';
                    if (selectedBadge) {
                        selectedBadge.style.display = 'block';
                        selectedBadge.innerHTML = `✓ Pelanggan Terpilih: <strong>${m.nama}</strong> (${m.kode_kartu})`;
                    }
                });
                suggestBox.appendChild(item);
            });
            suggestBox.style.display = 'block';
        } else {
            suggestBox.style.display = 'none';
        }
    });

    document.addEventListener('click', function (e) {
        if (!suggestBox.contains(e.target) && e.target !== input) {
            suggestBox.style.display = 'none';
        }
    });
}

// Quick service category select helper
function selectServiceByCategory(categoryKeyword) {
    const select = document.getElementById('serviceSelect');
    if (!select) return;

    for (let i = 0; i < select.options.length; i++) {
        const opt = select.options[i];
        if (opt.text.toLowerCase().includes(categoryKeyword.toLowerCase())) {
            select.selectedIndex = i;
            break;
        }
    }
    select.dispatchEvent(new Event('change'));
}

// Quantity helper buttons
function changeQty(delta) {
    const input = document.getElementById('qtyInput');
    if (!input) return;
    let val = (parseInt(input.value) || 0) + delta;
    if (val < 0) val = 0;
    input.value = val;
    input.dispatchEvent(new Event('input'));
}

// 6. QR Scanner Simulation (M1a, M1b, M1c)
function simulateQrScanSuccess(sampleCode = 'KC-P-0001') {
    const viewfinder = document.getElementById('qrViewfinder');
    const statusText = document.getElementById('qrStatusText');
    const manualInput = document.getElementById('qrManualInput');

    if (viewfinder) {
        viewfinder.style.borderColor = '#16a34a';
        viewfinder.style.background = 'rgba(22, 163, 74, 0.1)';
    }
    if (statusText) {
        statusText.style.color = '#16a34a';
        statusText.innerHTML = `✓ Terbaca: <strong>${sampleCode}</strong>`;
    }
    if (manualInput) {
        manualInput.value = sampleCode;
    }
}

function applyScannedCode() {
    const manualInput = document.getElementById('qrManualInput');
    if (!manualInput || !manualInput.value.trim()) {
        showToast('Belum ada kode barcode/QR', 'error');
        return;
    }
    const code = manualInput.value.trim();
    
    // Find customer by code
    const members = window.__MOCK_MEMBERS || [];
    const matched = members.find(m => m.kode_kartu.toLowerCase() === code.toLowerCase());

    if (matched) {
        const inputNama = document.getElementById('inputPelangganNama');
        const inputWA = document.getElementById('inputPelangganWA');
        const selectedBadge = document.getElementById('memberSelectedBadge');

        if (inputNama) inputNama.value = matched.nama;
        if (inputWA) inputWA.value = matched.wa;
        if (selectedBadge) {
            selectedBadge.style.display = 'block';
            selectedBadge.innerHTML = `✓ Member Terpilih: <strong>${matched.nama}</strong> (${matched.kode_kartu})`;
        }
        closeModal('modalScanQr');
        showToast(`QR ${code} berhasil digunakan`, 'success');
    } else {
        showToast(`Kode ${code} tidak ditemukan`, 'error');
    }
}

// 7. Customer QR Modal Handler (C1c / C2)
function handleOpenCustomerQR() {
    const isMember = true; // In mock state, Budi is a member
    if (isMember) {
        openModal('modalCustomerQrCard');
    } else {
        showToast('Kartu member belum ada. Minta kasir untuk mendaftarkan Anda', 'dark', 3500);
    }
}
