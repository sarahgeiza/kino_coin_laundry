// ==========================================================================
// Kino Coin Laundry - Frontend Interactivity & Business Logic
// Aligned 100% with Figma Page 2 Design Overhaul + Dynamic QR Code Generation
// ==========================================================================

document.addEventListener('DOMContentLoaded', function () {
    initModals();
    initTransactionCalculator();
    initShiftCloseForm();
    initCustomerAutocomplete();
    initUrlToasts();
    initCustomerPageQR();
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
    
    let icon = '';
    if (type === 'error') icon = '';
    if (type === 'dark') icon = '';

    const iconElement = document.createElement('span');
    iconElement.innerHTML = icon;
    const messageElement = document.createElement('span');
    messageElement.textContent = String(message);
    toast.append(iconElement, messageElement);
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

function escapeHtmlText(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

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
    if (params.get('toast') === 'registered') {
        showToast('Akun pelanggan berhasil didaftarkan', 'success');
    }
    if (params.get('toast') === 'need_checkin') {
        showToast('Wajib mulai shift & presensi terlebih dahulu sebelum mencatat transaksi', 'error', 4000);
    }
    if (params.get('toast') === 'need_schedule') {
        showToast('Belum ada jadwal kerja Anda untuk shift yang sedang berjalan. Hubungi pemilik.', 'error', 4500);
    }
}

// 2. Modal Management (Bottom Sheet Overlay / Centered on PC)
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Auto trigger camera scan when opening scan modal
        if (modalId === 'modalScanQr') {
            startLiveCameraScanner();
        }
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';

        // Stop camera if scanner modal closed
        if (modalId === 'modalScanQr') {
            stopLiveCameraScanner();
        }
    }
}

function initModals() {
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

    const detailContainer = document.getElementById('serviceDetails');
    const amountInput = document.getElementById('amountPaid');
    const paymentType = document.getElementById('paymentType');
    const totalBillEl = document.getElementById('totalBillDisplay');
    const amountPaidDisplay = document.getElementById('amountPaidDisplay');
    const remainingAmountDisplay = document.getElementById('remainingAmountDisplay');
    const changeAmountDisplay = document.getElementById('changeAmountDisplay');
    const statusBadgeEl = document.getElementById('statusBadgeDisplay');
    const totalCoinsEl = document.getElementById('totalMachineCoinsDisplay');

    function calculate() {
        let totalBill = 0;
        let totalCoins = 0;

        const baseRow = detailContainer.querySelector('.service-detail-row');
        const select = baseRow.querySelector('.service-select');
        const quantityInput = baseRow.querySelector('.service-quantity');
        const label = baseRow.querySelector('.detail-quantity-label');
        const error = baseRow.querySelector('.kuantitas-error-alert');
        const option = select.options[select.selectedIndex];
        const unit = option?.dataset.unit || (option?.dataset.machine === 'true' ? 'process' : 'pcs');
        const baseValue = Number(quantityInput.value);
        const baseValid = Number.isFinite(baseValue) && baseValue > 0;

        label.textContent = unit === 'pcs' ? 'Jumlah (pcs) *' : 'Berat (kg) *';
        quantityInput.min = unit === 'pcs' ? '1' : '0.01';
        quantityInput.step = unit === 'pcs' ? '1' : '0.01';
        error.style.display = baseValid ? 'none' : 'block';

        const addLineTotal = (lineUnit, value, price, coins) => {
            if (lineUnit === 'process') {
                const processes = Math.ceil(value / 7.5);
                totalBill += processes * price;
                totalCoins += processes * coins;
            } else if (lineUnit === 'kg') {
                totalBill += Math.round(value * price);
            } else {
                totalBill += Math.trunc(value) * price;
            }
        };

        if (option && select.value && baseValid) {
            if (unit === 'pcs' && !Number.isInteger(baseValue)) {
                quantityInput.value = String(Math.ceil(baseValue));
            }
            addLineTotal(unit, baseValue, Number(option.dataset.price), Number(option.dataset.coins));
        }

        document.querySelectorAll('.quick-add-service:checked').forEach(addon => {
            const addonUnit = addon.dataset.unit;
            const addonValue = addonUnit === 'process'
                ? (unit === 'process' ? baseValue : 7.5)
                : (addonUnit === 'kg' ? (unit === 'process' || unit === 'kg' ? baseValue : 1) : 1);
            addLineTotal(addonUnit, addonValue, Number(addon.dataset.price), Number(addon.dataset.coins));
        });

        const amountPaid = Math.max(0, Number(amountInput.value) || 0);
        const difference = amountPaid - totalBill;
        const qrisOverpayment = paymentType.value === 'qris' && difference > 0;
        const remainingAmount = Math.max(0, totalBill - amountPaid);
        const changeAmount = paymentType.value === 'cash' ? Math.max(0, difference) : 0;

        totalBillEl.textContent = 'Rp ' + totalBill.toLocaleString('id-ID');
        totalCoinsEl.textContent = totalCoins.toLocaleString('id-ID');
        amountPaidDisplay.textContent = 'Rp ' + amountPaid.toLocaleString('id-ID');
        remainingAmountDisplay.textContent = 'Rp ' + remainingAmount.toLocaleString('id-ID');
        changeAmountDisplay.textContent = 'Rp ' + changeAmount.toLocaleString('id-ID');
        if (qrisOverpayment) {
            statusBadgeEl.className = 'badge badge-kurang';
            statusBadgeEl.textContent = 'QRIS tidak boleh melebihi tagihan';
        } else if (difference < 0) {
            statusBadgeEl.className = 'badge badge-kurang';
            statusBadgeEl.textContent = amountPaid > 0 ? 'Pembayaran sebagian' : 'Belum lunas';
        } else {
            statusBadgeEl.className = 'badge badge-lunas';
            statusBadgeEl.textContent = paymentType.value === 'cash' && difference > 0
                ? 'Kembalian'
                : 'Lunas';
        }
    }

    detailContainer.addEventListener('change', calculate);
    detailContainer.addEventListener('input', calculate);
    document.querySelectorAll('.quick-add-service').forEach(option => {
        option.addEventListener('change', calculate);
    });
    amountInput.addEventListener('input', calculate);
    paymentType.addEventListener('change', calculate);
    calculate();

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const name = document.getElementById('inputPelangganNama').value.trim();
        const wa = document.getElementById('inputPelangganWA').value.trim();
        const baseRow = detailContainer.querySelector('.service-detail-row');
        const select = baseRow.querySelector('.service-select');
        const selectedOption = select.options[select.selectedIndex];
        let quantity = Number(baseRow.querySelector('.service-quantity').value);
        const selectedUnit = selectedOption?.dataset.unit || (selectedOption?.dataset.machine === 'true' ? 'process' : 'pcs');

        if (!name || !/^\+?[0-9]{9,15}$/.test(wa)) {
            showToast('Lengkapi nama dan nomor WhatsApp pelanggan', 'error');
            return;
        }
        if (!select.value || !Number.isFinite(quantity) || quantity <= 0) {
            showToast('Berat atau jumlah layanan harus lebih dari 0', 'error');
            return;
        }
        if (selectedUnit === 'pcs' && !Number.isInteger(quantity)) {
            quantity = Math.ceil(quantity);
        }

        const details = [];
        const mainDetail = { id_layanan: Number(select.value) };
        if (selectedUnit === 'process' || selectedUnit === 'kg') mainDetail.berat = quantity;
        else mainDetail.jumlah = Math.trunc(quantity);
        details.push(mainDetail);

        document.querySelectorAll('.quick-add-service:checked').forEach(addon => {
            const addonUnit = addon.dataset.unit;
            const addonValue = addonUnit === 'process'
                ? (selectedUnit === 'process' ? quantity : 7.5)
                : (addonUnit === 'kg' ? (selectedUnit === 'process' || selectedUnit === 'kg' ? quantity : 1) : 1);
            const detail = { id_layanan: Number(addon.value) };
            if (addonUnit === 'process' || addonUnit === 'kg') detail.berat = addonValue;
            else detail.jumlah = addonValue;
            details.push(detail);
        });

        if (details.length > 10) {
            showToast('Maksimal 10 detail layanan per transaksi', 'error');
            return;
        }
        const amount = Number(amountInput.value);
        if (!Number.isSafeInteger(amount) || amount < 0) {
            showToast('Jumlah pembayaran tidak valid', 'error');
            return;
        }

        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        try {
            const result = await window.KinoAPI.createTransaction({
                nama_pelanggan: name,
                no_hp: wa,
                jenis_pembayaran: paymentType.value,
                jumlah_dibayar: amount,
                detail_transaksi: details
            }, true);
            showToast(`Transaksi ${result.data.no_invoice} berhasil disimpan`, 'success');
            window.location.href = 'index.php?toast=saved';
        } catch (error) {
            showToast(error.message || 'Transaksi gagal disimpan', 'error', 4500);
        } finally {
            submitButton.disabled = false;
        }
    });
}

function initShiftCloseForm() {
    const form = document.getElementById('formTutupShift');
    if (!form) return;

    const totalInput = document.getElementById('totalFisikInput');
    const noteInput = document.getElementById('catatanShiftInput');
    const physicalDisplay = document.getElementById('physicalCashDisplay');
    const differenceDisplay = document.getElementById('cashDifferenceDisplay');
    const systemTotal = Number(form.dataset.totalSistem);

    function updateDifference() {
        if (totalInput.value.trim() === '') {
            physicalDisplay.textContent = 'Belum diinput';
            differenceDisplay.textContent = 'Belum dihitung';
            return;
        }
        const physical = Number(totalInput.value);
        if (!Number.isSafeInteger(physical) || physical < 0) {
            physicalDisplay.textContent = 'Input tidak valid';
            differenceDisplay.textContent = 'Belum dihitung';
            return;
        }

        const difference = physical - systemTotal;
        physicalDisplay.textContent = 'Rp ' + physical.toLocaleString('id-ID');
        differenceDisplay.textContent = difference === 0
            ? 'Rp 0 (Sesuai)'
            : `${difference > 0 ? 'Lebih ' : 'Kurang '}Rp ${Math.abs(difference).toLocaleString('id-ID')}`;
    }

    totalInput.addEventListener('input', updateDifference);
    updateDifference();

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const totalFisik = Number(totalInput.value);
        if (!Number.isSafeInteger(totalFisik) || totalFisik < 0) {
            showToast('Total uang fisik tidak valid', 'error');
            return;
        }

        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
            await window.KinoAPI.closeShift({
                total_fisik: totalFisik,
                catatan: noteInput.value.trim()
            });
            window.location.href = 'index.php?shift_closed=1';
        } catch (error) {
            showToast(error.message || 'Shift gagal ditutup', 'error', 4500);
        } finally {
            button.disabled = false;
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

        let matches;
        try {
            matches = await window.KinoAPI.searchMember(val, true);
        } catch (error) {
            showToast(error.message || 'Pencarian pelanggan gagal', 'error');
            return;
        }
        if (matches.length > 0) {
            suggestBox.innerHTML = '';
            matches.forEach(m => {
                const item = document.createElement('div');
                item.style.padding = '10px 14px';
                item.style.cursor = 'pointer';
                item.style.borderBottom = '1px solid #f1f5f9';
                const title = document.createElement('div');
                title.style.cssText = 'font-weight:700; font-size:13px; color:#0f172a;';
                title.textContent = `${m.nama} (${m.kode_kartu})`;
                const detail = document.createElement('div');
                detail.style.cssText = 'font-size:11px; color:#64748b;';
                const isMember = [true, 1, '1', 't', 'true'].includes(m.is_member);
                detail.textContent = `${m.wa} • ${isMember ? 'Member' : 'Non-Member'}`;
                item.append(title, detail);
                item.addEventListener('click', function () {
                    input.value = m.nama;
                    if (waInput) waInput.value = m.wa;
                    suggestBox.style.display = 'none';
                    if (selectedBadge) {
                        selectedBadge.style.display = 'block';
                        selectedBadge.textContent = `Pelanggan Terpilih: ${m.nama} (${m.kode_kartu})`;
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
    select.dispatchEvent(new Event('change', { bubbles: true }));
}

function changeQty(delta, button) {
    const input = button
        ? button.closest('.service-detail-row').querySelector('.service-quantity')
        : document.getElementById('qtyInput');
    if (!input) return;
    const step = Number(input.step) || 1;
    let val = (Number(input.value) || 0) + (delta * step);
    if (val < Number(input.min || 0)) val = Number(input.min || 0);
    input.value = String(Number(val.toFixed(2)));
    input.dispatchEvent(new Event('input', { bubbles: true }));
}

// ==========================================================================
// 6. DYNAMIC QR CODE GENERATOR (Real SVG / Canvas Generation)
// ==========================================================================
function generateQRCode(containerId, textToEncode) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = ''; // Clear previous

    if (typeof QRCode !== 'undefined') {
        new QRCode(container, {
            text: textToEncode,
            width: 130,
            height: 130,
            colorDark: "#1e3a8a",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    } else {
        // Fallback display
        container.innerHTML = `<div style="font-family:monospace; font-weight:800; font-size:16px; padding:20px; color:#1e3a8a;">[QR: ${textToEncode}]</div>`;
    }
}

// In Kasir Pelanggan list: View member's dynamic QR card (M7)
function viewMemberCard(m) {
    const nameEl = document.getElementById('cardMemberName');
    const idEl = document.getElementById('cardMemberId');
    if (nameEl) nameEl.textContent = m.nama;
    if (idEl) idEl.textContent = m.kode_kartu;

    // Dynamically generate the real QR code for this specific member!
    generateQRCode('modalMemberQrContainer', m.kode_kartu);
    openModal('modalMemberCard');
}

// In Pelanggan Home: View user's own dynamic QR card (C2)
function handleOpenCustomerQR(customCode) {
    const code = customCode || window.__CURRENT_MEMBER_CODE || 'KC-P-0001';
    const name = window.__CURRENT_MEMBER_NAME || 'Budi Santoso';

    const nameEl = document.getElementById('custModalName');
    const idEl = document.getElementById('custModalId');
    if (nameEl) nameEl.textContent = name;
    if (idEl) idEl.textContent = code;

    generateQRCode('customerQrContainer', code);
    openModal('modalCustomerQrCard');
}

function initCustomerPageQR() {
    const container = document.getElementById('customerQrContainer');
    if (container) {
        const code = window.__CURRENT_MEMBER_CODE || 'KC-P-0001';
        const name = window.__CURRENT_MEMBER_NAME || 'Budi Santoso';

        const nameEl = document.getElementById('custModalName');
        const idEl = document.getElementById('custModalId');
        if (nameEl) nameEl.textContent = name;
        if (idEl) idEl.textContent = code;

        generateQRCode('customerQrContainer', code);
    }
}

// ==========================================================================
// 7. REAL QR CODE SCANNER (Webcam Camera + Fallback M1c + Simulation)
// ==========================================================================
let html5QrScannerInstance = null;

function startLiveCameraScanner() {
    const readerDiv = document.getElementById('cameraReader');
    const statusText = document.getElementById('qrStatusText');
    const manualInput = document.getElementById('qrManualInput');

    if (!readerDiv) return;

    // Reset status
    if (statusText) {
        statusText.style.color = '#ea580c';
        statusText.innerHTML = 'Arahkan QR ke kamera...';
    }

    if (typeof Html5Qrcode !== 'undefined') {
        if (!html5QrScannerInstance) {
            html5QrScannerInstance = new Html5Qrcode("cameraReader");
        }

        html5QrScannerInstance.start(
            { facingMode: "environment" },
            {
                fps: 10,
                qrbox: { width: 170, height: 170 }
            },
            (decodedText) => {
                // SUCCESS: Scanned a real QR Code!
                console.log("Scanned QR:", decodedText);
                onQrScanSuccess(decodedText);
            },
            (error) => {
                // scanning frame (silent)
            }
        ).catch(err => {
            console.log("Camera access notice:", err);
            // Fallback (Figma M1c: Kamera gagal / izin ditolak)
            if (statusText) {
                statusText.style.color = '#dc2626';
                statusText.innerHTML = 'Tidak bisa akses kamera (Permission denied / Tanpa webcam). Gunakan input manual atau tombol simulasi di bawah:';
            }
        });
    }
}

function stopLiveCameraScanner() {
    if (html5QrScannerInstance && html5QrScannerInstance.isScanning) {
        html5QrScannerInstance.stop().then(() => {
            console.log("Camera scanner stopped.");
        }).catch(err => console.log(err));
    }
}

function onQrScanSuccess(code) {
    stopLiveCameraScanner();

    const viewfinder = document.getElementById('qrViewfinder');
    const statusText = document.getElementById('qrStatusText');
    const manualInput = document.getElementById('qrManualInput');

    if (viewfinder) {
        viewfinder.style.borderColor = '#16a34a';
        viewfinder.style.background = 'rgba(22, 163, 74, 0.1)';
    }
    if (statusText) {
        statusText.style.color = '#16a34a';
        statusText.textContent = `Terbaca: ${code}`;
    }
    if (manualInput) {
        manualInput.value = code;
    }

    // Auto apply if matched
    setTimeout(() => {
        applyScannedCode();
    }, 400);
}

async function applyScannedCode() {
    const manualInput = document.getElementById('qrManualInput');
    if (!manualInput || !manualInput.value.trim()) {
        showToast('Belum ada kode barcode/QR', 'error');
        return;
    }
    const code = manualInput.value.trim();
    
    let matched;
    try {
        const members = await window.KinoAPI.searchMember(code, true);
        matched = members.find(m => (m.kode_kartu || '').toLowerCase() === code.toLowerCase());
    } catch (error) {
        showToast(error.message || 'Pencarian pelanggan gagal', 'error', 4500);
        return;
    }

    if (matched) {
        const inputNama = document.getElementById('inputPelangganNama');
        const inputWA = document.getElementById('inputPelangganWA');
        const selectedBadge = document.getElementById('memberSelectedBadge');

        if (inputNama && inputWA) {
            inputNama.value = matched.nama || matched.nama_pelanggan;
            inputWA.value = matched.wa || matched.no_hp;
            if (selectedBadge) {
                selectedBadge.style.display = 'block';
                selectedBadge.textContent = `Member Terpilih: ${matched.nama || matched.nama_pelanggan} (${matched.kode_kartu})`;
            }
            closeModal('modalScanQr');
            showToast(`QR ${code} (${matched.nama || matched.nama_pelanggan}) berhasil digunakan!`, 'success');
        } else {
            // Jika discan langsung dari Dashboard Kasir, langsung arahkan ke transaksi baru dengan data member terisi
            closeModal('modalScanQr');
            showToast(`Member terdeteksi: ${matched.nama || matched.nama_pelanggan}. Mengalihkan ke transaksi...`, 'success');
            setTimeout(() => {
                window.location.href = `transaksi-baru.php?scan_member=${encodeURIComponent(matched.kode_kartu)}`;
            }, 600);
        }
    } else {
        showToast(`Kode ${code} tidak terdaftar di sistem`, 'error');
    }
}

// ==========================================================================
// 8. REAL DATA CSV EXPORTER (Audit Feature for Owner Laporan & Presensi)
// ==========================================================================
function downloadCSV(filename, rows) {
    const processRow = function (row) {
        let finalVal = '';
        for (let j = 0; j < row.length; j++) {
            let innerValue = row[j] === null ? '' : row[j].toString();
            if (row[j] instanceof Date) {
                innerValue = row[j].toLocaleString();
            }
            let result = innerValue.replace(/"/g, '""');
            if (result.search(/("|,|\n)/g) >= 0)
                result = '"' + result + '"';
            if (j > 0)
                finalVal += ',';
            finalVal += result;
        }
        return finalVal + '\r\n';
    };

    let csvFile = '';
    for (let i = 0; i < rows.length; i++) {
        csvFile += processRow(rows[i]);
    }

    const blob = new Blob([csvFile], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        showToast('File ' + filename + ' berhasil diunduh', 'success');
    }
}

function exportLaporanTransactionsCSV() {
    const txs = window.__CURRENT_LAPORAN_TXS || [];
    const rows = [
        ['ID Invoice', 'Pelanggan', 'WhatsApp', 'Layanan', 'Kuantitas', 'Total Biaya', 'Total Bayar', 'Metode', 'Status', 'Kasir', 'Shift', 'Tanggal', 'Waktu']
    ];
    txs.forEach(t => {
        rows.push([
            t.id, t.pelanggan, t.wa || '', t.layanan, t.kuantitas, t.total_biaya, t.total_bayar, t.metode, t.status, t.kasir, t.shift, t.tanggal, t.waktu
        ]);
    });
    downloadCSV('Laporan_Transaksi_Kino_' + new Date().toISOString().slice(0,10) + '.csv', rows);
}


function exportPresensiCSV() {
    const logs = window.__PRESENSI_LOGS || [];
    const rows = [
        ['ID Presensi', 'Nama Kasir', 'Shift', 'Tanggal', 'Jam Masuk', 'Jam Keluar', 'Status']
    ];
    logs.forEach(l => {
        rows.push([
            l.id, l.kasir, l.shift, l.tanggal, l.jam_masuk, l.jam_keluar || '-', l.status
        ]);
    });
    downloadCSV('Presensi_Kasir_Kino_' + new Date().toISOString().slice(0,10) + '.csv', rows);
    showToast('Log presensi berhasil diexport ke CSV', 'success');
}
