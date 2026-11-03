<?php
$page_title = 'Laporan Transaksi - Kino Coin Laundry';
$app_role = 'pemilik';
$active_tab = 'laporan';

require_once __DIR__ . '/../includes/header.php';

$periode = $_GET['periode'] ?? 'Hari Ini';
$all_session_txs = $_SESSION['transactions'] ?? [];

// Hitung omset hari ini dari transaksi riil sesi kasir
$today_real_omset = 0;
foreach ($all_session_txs as $t) {
    $today_real_omset += ($t['total_bayar'] ?? $t['total_biaya'] ?? 0);
}
?>

<style>
    @keyframes barRise {
        from {
            transform: scaleY(0);
            opacity: 0.2;
        }
        to {
            transform: scaleY(1);
            opacity: 1;
        }
    }
    .chart-col {
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        align-items: center;
        flex: 1;
        padding: 0 3px;
        cursor: pointer;
        position: relative;
    }
    .chart-bar-el {
        width: 100%;
        max-width: 24px;
        transform-origin: bottom;
        border-radius: 6px 6px 0 0;
        transition: height 0.6s cubic-bezier(0.34, 1.56, 0.64, 1), background 0.3s ease, box-shadow 0.3s ease;
        animation: barRise 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    .chart-bar-el:hover {
        transform: scaleY(1.05);
        filter: brightness(1.12);
    }
    .filter-chip-btn {
        background: #ffffff;
        color: #1e3a8a;
        border: 1.5px solid var(--border);
        padding: 7px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.2s ease;
        outline: none;
        flex-shrink: 0;
    }
    .filter-chip-btn:hover {
        border-color: #2563eb;
    }
    .filter-chip-btn.active {
        background: #1e3a8a;
        color: #ffffff;
        border-color: #1e3a8a;
        box-shadow: 0 2px 8px rgba(30, 58, 138, 0.32);
    }
    .custom-date-box {
        max-height: 0px;
        opacity: 0;
        overflow: hidden;
        transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease, margin 0.3s ease;
        margin-bottom: 0px;
    }
    .custom-date-box.open {
        max-height: 140px;
        opacity: 1;
        margin-bottom: 14px;
    }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
    <div>
        <h2 style="font-size: 16px; font-weight: 800;">Laporan Transaksi</h2>
        <span style="font-size: 11px; color: var(--text-muted);">Audit keuangan &amp; volume shift</span>
    </div>
    <button type="button" class="btn btn-outline" style="width: auto; padding: 6px 12px; font-size: 12px;" onclick="exportLaporanTransactionsCSV()">
        <?= get_icon('export', 14) ?> Export
    </button>
</div>

<!-- Filter Periode (Client-side instant switch, ZERO page jump) -->
<div class="filter-scroll" style="margin-bottom: 10px; display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px;">
    <button type="button" id="btnFilterHariIni" class="filter-chip-btn <?= $periode === 'Hari Ini' ? 'active' : '' ?>" onclick="switchLaporanPeriode('Hari Ini')">Hari Ini</button>
    <button type="button" id="btnFilterKemarin" class="filter-chip-btn <?= $periode === 'Kemarin' ? 'active' : '' ?>" onclick="switchLaporanPeriode('Kemarin')">Kemarin</button>
    <button type="button" id="btnFilter7Hari" class="filter-chip-btn <?= $periode === '7 Hari' ? 'active' : '' ?>" onclick="switchLaporanPeriode('7 Hari')">7 Hari</button>
    <button type="button" id="btnFilterCustom" class="filter-chip-btn <?= $periode === 'Custom' ? 'active' : '' ?>" onclick="switchLaporanPeriode('Custom')">Custom</button>
    <button type="button" id="btnFilterKosong" class="filter-chip-btn <?= $periode === 'Kosong' ? 'active' : '' ?>" onclick="switchLaporanPeriode('Kosong')">Kosong (Test)</button>
</div>

<!-- Custom Date Selector: Smooth Accordion (Tidak membuat layout meloncat) -->
<div id="customDateBox" class="custom-date-box <?= $periode === 'Custom' ? 'open' : '' ?>">
    <div class="card" style="padding: 12px; background: #eff6ff; border-color: #bfdbfe; margin-bottom: 0;">
        <span style="font-size: 11px; font-weight: 800; color: #1e3a8a; display: block; margin-bottom: 8px;">Pilih Rentang Tanggal:</span>
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <input type="date" id="customStartDate" class="form-control" value="2026-10-25" style="font-size: 12px; padding: 8px; flex: 1; min-width: 120px;" onchange="applyCustomDateFilter()">
            <span style="font-size: 12px; font-weight: 700; color: #64748b;">s/d</span>
            <input type="date" id="customEndDate" class="form-control" value="2026-10-31" style="font-size: 12px; padding: 8px; flex: 1; min-width: 120px;" onchange="applyCustomDateFilter()">
            <button type="button" class="btn btn-primary" style="width: auto; padding: 8px 14px; font-size: 12px;" onclick="applyCustomDateFilter()">Terapkan</button>
        </div>
    </div>
</div>

<!-- Empty State Container (Hidden by default, shown when Kosong) -->
<div id="laporanEmptyCard" class="card" style="display: none; text-align: center; padding: 40px 20px; color: var(--text-muted); margin-bottom: 16px;">
    <div style="margin-bottom: 8px;"><?= get_icon('rekap', 36, '#94a3b8') ?></div>
    <strong style="font-size: 14px; color: var(--text-main);">Tidak ada transaksi di periode ini</strong>
    <p style="font-size: 12px; margin-top: 4px;">Pilih periode lain atau atur rentang tanggal.</p>
</div>

<!-- Main Omset Summary & Dynamic Animated Chart -->
<div id="laporanMainCard" class="card">
    <div id="laporanPeriodeTitle" style="font-size: 11px; color: var(--text-muted); font-weight: 700; letter-spacing: 0.5px;">TOTAL OMSET (HARI INI)</div>
    <div style="font-size: 24px; font-weight: 900; color: var(--primary-dark); margin: 4px 0 14px 0; display: flex; align-items: baseline; gap: 8px;">
        <span id="laporanTotalOmset" style="transition: color 0.3s;"><?= format_rupiah($today_real_omset) ?></span>
        <span id="laporanTxCount" style="font-size: 12px; font-weight: normal; color: var(--text-muted);">(<?= count($all_session_txs) ?> transaksi)</span>
    </div>

    <!-- Dynamic 7-Day / Custom Bar Chart -->
    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 14px 12px 10px 12px; margin-bottom: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <span id="chartSubTitle" style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Grafik Tren Omset 7 Hari</span>
            <span id="chartLiveBadge" style="font-size: 10px; font-weight: 700; color: #2563eb; background: #dbeafe; padding: 2px 8px; border-radius: 12px;">Dinamis Real-Time</span>
        </div>

        <!-- Chart Bars Track -->
        <div id="chartBarsContainer" style="display: flex; align-items: flex-end; justify-content: space-between; height: 115px; border-bottom: 2px solid #cbd5e1; padding-bottom: 4px;">
            <!-- Bars rendered dynamically by JS with smooth physics transition -->
        </div>

        <!-- X-Axis Labels -->
        <div id="chartLabelsContainer" style="display: flex; justify-content: space-between; padding-top: 6px;">
            <!-- Dates rendered dynamically by JS -->
        </div>
    </div>

    <!-- Detail Invoice List -->
    <div class="card-title">Detail Transaksi</div>
    <div id="laporanDetailList">
        <!-- Transaction cards rendered dynamically by JS -->
    </div>
</div>

<script>
// Data Store Sinkronisasi Real-Time
window.__LIVE_SESSION_TXS = <?= json_encode($all_session_txs) ?>;
window.__TODAY_REAL_OMSET = <?= intval($today_real_omset) ?>;

window.__HISTORICAL_DAYS = {
    '2026-10-25': { d: '25', name: 'Min', omset: 380000, count: 15, txs: [
        { id: 'INV-2026-H01', pelanggan: 'Aris Munandar', layanan: 'Cuci Kering (Self)', kasir: 'Daniel', status: 'Lunas', total_bayar: 17000 },
        { id: 'INV-2026-H02', pelanggan: 'Ratih Kumala', layanan: 'Setrika Kilat', kasir: 'Daniel', status: 'Lunas', total_bayar: 24000 }
    ]},
    '2026-10-26': { d: '26', name: 'Sen', omset: 420000, count: 18, txs: [
        { id: 'INV-2026-H03', pelanggan: 'Bagus Santika', layanan: 'Cuci Kering Lengkap', kasir: 'Sarah', status: 'Lunas', total_bayar: 32000 },
        { id: 'INV-2026-H04', pelanggan: 'Maya Safitri', layanan: 'Kering Koin (Self)', kasir: 'Sarah', status: 'Lunas', total_bayar: 10000 }
    ]},
    '2026-10-27': { d: '27', name: 'Sel', omset: 510000, count: 22, txs: [
        { id: 'INV-2026-H05', pelanggan: 'Citra Lestari', layanan: 'Cuci Kering (Self)', kasir: 'Sarah', status: 'Lunas', total_bayar: 17000 },
        { id: 'INV-2026-H06', pelanggan: 'Denny Setiawan', layanan: 'Cuci Kering Lengkap', kasir: 'Sarah', status: 'Lunas', total_bayar: 32000 }
    ]},
    '2026-10-28': { d: '28', name: 'Rab', omset: 390000, count: 17, txs: [
        { id: 'INV-2026-H07', pelanggan: 'Dodi Darmawan', layanan: 'Drop Off Regular', kasir: 'Nizar', status: 'Lunas', total_bayar: 28000 },
        { id: 'INV-2026-H08', pelanggan: 'Gita Gutawa', layanan: 'Cuci Koin (Self)', kasir: 'Nizar', status: 'Lunas', total_bayar: 7000 }
    ]},
    '2026-10-29': { d: '29', name: 'Kam', omset: 480000, count: 20, txs: [
        { id: 'INV-2026-H09', pelanggan: 'Eka Putri', layanan: 'Cuci Kering (Self)', kasir: 'Nizar', status: 'Lunas', total_bayar: 17000 },
        { id: 'INV-2026-H10', pelanggan: 'Hari Purnomo', layanan: 'Setrika Kilat', kasir: 'Nizar', status: 'Lunas', total_bayar: 18000 }
    ]},
    '2026-10-30': { d: '30', name: 'Jum', omset: 520000, count: 22, txs: [
        { id: 'INV-2026-K01', pelanggan: 'Hendro Wijaya', layanan: 'Cuci Kering (Self)', kasir: 'Sarah', status: 'Lunas', total_bayar: 17000 },
        { id: 'INV-2026-K02', pelanggan: 'Dewi Anggraini', layanan: 'Cuci Kering Lengkap', kasir: 'Sarah', status: 'Lunas', total_bayar: 32000 },
        { id: 'INV-2026-K03', pelanggan: 'Fajar Nugraha', layanan: 'Drop Off Regular', kasir: 'Nizar', status: 'Lunas', total_bayar: 25000 }
    ]},
    '2026-10-31': { d: '31', name: 'Sab', omset: window.__TODAY_REAL_OMSET, count: window.__LIVE_SESSION_TXS.length, txs: window.__LIVE_SESSION_TXS }
};

let currentActiveFilter = '<?= htmlspecialchars($periode) ?>';

function formatRupiahJS(num) {
    return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
}

function shortRupiah(num) {
    if (num <= 0) return '0';
    if (num >= 1000000) return (num / 1000000).toFixed(1).replace('.0','') + 'jt';
    return Math.round(num / 1000) + 'k';
}

function switchLaporanPeriode(mode) {
    currentActiveFilter = mode;

    // Update active button state
    const btnIds = {
        'Hari Ini': 'btnFilterHariIni',
        'Kemarin': 'btnFilterKemarin',
        '7 Hari': 'btnFilter7Hari',
        'Custom': 'btnFilterCustom',
        'Kosong': 'btnFilterKosong'
    };
    Object.keys(btnIds).forEach(k => {
        const el = document.getElementById(btnIds[k]);
        if (el) {
            if (k === mode) el.classList.add('active');
            else el.classList.remove('active');
        }
    });

    // Update URL without reload to avoid page jump
    const newUrl = window.location.pathname + '?periode=' + encodeURIComponent(mode);
    window.history.replaceState({ periode: mode }, '', newUrl);

    // Accordion custom box
    const customBox = document.getElementById('customDateBox');
    if (mode === 'Custom') {
        customBox.classList.add('open');
        applyCustomDateFilter();
        return;
    } else {
        customBox.classList.remove('open');
    }

    if (mode === 'Kosong') {
        renderLaporanView([], [], 'TOTAL OMSET (KOSONG)', 0, 'Grafik Tren Omset (Kosong)');
        return;
    }

    if (mode === 'Kemarin') {
        const kemData = window.__HISTORICAL_DAYS['2026-10-30'];
        const days = build7DaysArray('2026-10-30');
        renderLaporanView(days, kemData.txs, 'TOTAL OMSET (KEMARIN)', kemData.omset, 'Grafik Tren Omset (Kemarin)');
        return;
    }

    if (mode === '7 Hari') {
        const days = build7DaysArray(null, true);
        const totalAll = days.reduce((acc, cur) => acc + cur.omset, 0);
        let allTxs = [];
        days.forEach(d => { if (d.txs) allTxs = allTxs.concat(d.txs); });
        renderLaporanView(days, allTxs, 'TOTAL OMSET (7 HARI)', totalAll, 'Grafik Tren Omset 7 Hari');
        return;
    }

    // Default: Hari Ini
    const days = build7DaysArray('2026-10-31');
    renderLaporanView(days, window.__LIVE_SESSION_TXS, 'TOTAL OMSET (HARI INI)', window.__TODAY_REAL_OMSET, 'Grafik Tren Omset 7 Hari');
}

function build7DaysArray(activeKey = null, allActive = false) {
    const keys = ['2026-10-25', '2026-10-26', '2026-10-27', '2026-10-28', '2026-10-29', '2026-10-30', '2026-10-31'];
    return keys.map(k => {
        const h = window.__HISTORICAL_DAYS[k];
        return {
            dateKey: k,
            d: h.d,
            name: h.name,
            omset: h.omset,
            count: h.count,
            txs: h.txs,
            is_active: allActive ? true : (k === activeKey)
        };
    });
}

// Fitur Custom Range: Dinamis menghitung rentang hari apapun yang dipilih
function applyCustomDateFilter() {
    const startVal = document.getElementById('customStartDate').value;
    const endVal = document.getElementById('customEndDate').value;

    if (!startVal || !endVal) return;
    if (startVal > endVal) {
        showToast('Tanggal awal tidak boleh melebihi tanggal akhir', 'error');
        return;
    }

    const startDate = new Date(startVal + 'T00:00:00');
    const endDate = new Date(endVal + 'T00:00:00');
    const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

    const customDays = [];
    let cur = new Date(startDate);
    let totalCustomOmset = 0;
    let customTxs = [];

    while (cur <= endDate) {
        const dateKey = cur.toISOString().slice(0, 10);
        const dayNum = String(cur.getDate()).padStart(2, '0');
        const dayName = dayNames[cur.getDay()];

        let dayOmset = 0;
        let dayCount = 0;
        let dayTxs = [];

        if (window.__HISTORICAL_DAYS[dateKey]) {
            dayOmset = window.__HISTORICAL_DAYS[dateKey].omset;
            dayCount = window.__HISTORICAL_DAYS[dateKey].count;
            dayTxs = window.__HISTORICAL_DAYS[dateKey].txs || [];
        } else {
            // Estimasi proporsional jika tanggal di luar baseline
            dayOmset = 350000 + (cur.getDate() * 15000) % 250000;
            dayCount = Math.max(5, Math.round(dayOmset / 25000));
        }

        totalCustomOmset += dayOmset;
        customTxs = customTxs.concat(dayTxs);

        customDays.push({
            dateKey: dateKey,
            d: dayNum,
            name: dayName,
            omset: dayOmset,
            count: dayCount,
            is_active: true
        });

        cur.setDate(cur.getDate() + 1);
        if (customDays.length >= 14) break; // Limit tampilan chart max 14 bar agar tetap rapi
    }

    renderLaporanView(
        customDays, 
        customTxs.length ? customTxs : window.__LIVE_SESSION_TXS, 
        `TOTAL OMSET (CUSTOM: ${startVal.slice(5)} s/d ${endVal.slice(5)})`, 
        totalCustomOmset, 
        `Grafik Tren Omset Custom (${customDays.length} Hari)`
    );
}

function renderLaporanView(days, txList, titleText, totalOmset, subtitleText) {
    const mainCard = document.getElementById('laporanMainCard');
    const emptyCard = document.getElementById('laporanEmptyCard');

    if (!days.length || (!txList.length && currentActiveFilter === 'Kosong')) {
        mainCard.style.display = 'none';
        emptyCard.style.display = 'block';
        return;
    }

    mainCard.style.display = 'block';
    emptyCard.style.display = 'none';

    // 1. Update Header Title & Total Omset
    document.getElementById('laporanPeriodeTitle').textContent = titleText;
    document.getElementById('laporanTotalOmset').textContent = formatRupiahJS(totalOmset);
    document.getElementById('laporanTxCount').textContent = `(${txList.length} transaksi)`;
    document.getElementById('chartSubTitle').textContent = subtitleText;

    // Simpan data transaksi aktif untuk fungsi export CSV
    window.__CURRENT_LAPORAN_TXS = txList;

    // 2. Render Animated Bars
    const barsContainer = document.getElementById('chartBarsContainer');
    const labelsContainer = document.getElementById('chartLabelsContainer');
    barsContainer.innerHTML = '';
    labelsContainer.innerHTML = '';

    const maxOmset = Math.max(...days.map(d => d.omset), 100000);

    days.forEach(d => {
        const heightPx = d.omset <= 0 ? 8 : Math.max(12, Math.round((d.omset / maxOmset) * 80));
        const bgGrad = d.is_active 
            ? 'linear-gradient(180deg, #3b82f6 0%, #1d4ed8 100%)' 
            : '#94a3b8';
        const shadow = d.is_active ? '0 3px 8px rgba(37,99,235,0.35)' : 'none';
        const colorLabel = d.is_active ? '#1d4ed8' : '#64748b';

        // Kolom Bar
        const col = document.createElement('div');
        col.className = 'chart-col';
        col.title = `Tgl ${d.d} (${d.name}): ${formatRupiahJS(d.omset)} (${d.count} trx)`;
        col.onclick = () => {
            showToast(`Tgl ${d.d} ${d.name}: ${formatRupiahJS(d.omset)}`, 'info');
        };

        col.innerHTML = `
            <span style="font-size: 9px; font-weight: 800; color: ${colorLabel}; margin-bottom: 4px; white-space: nowrap;">
                ${shortRupiah(d.omset)}
            </span>
            <div class="chart-bar-el" style="height: ${heightPx}px; background: ${bgGrad}; box-shadow: ${shadow};"></div>
        `;
        barsContainer.appendChild(col);

        // Label Bawah
        const lbl = document.createElement('div');
        lbl.style.flex = '1';
        lbl.style.textAlign = 'center';
        lbl.innerHTML = `
            <span style="font-size: 10px; font-weight: ${d.is_active ? '900' : '700'}; color: ${d.is_active ? '#1e3a8a' : '#64748b'}; display: block;">
                ${d.d}
            </span>
            <span style="font-size: 9px; color: #94a3b8; display: block; margin-top: -2px;">
                ${d.name}
            </span>
        `;
        labelsContainer.appendChild(lbl);
    });

    // 3. Render Transaction List
    const listEl = document.getElementById('laporanDetailList');
    listEl.innerHTML = '';
    txList.forEach(t => {
        const item = document.createElement('div');
        item.style.cssText = 'background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 10px 12px; margin-bottom: 8px; transition: transform 0.2s;';
        item.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px;">
                <strong style="font-size: 13px; color: var(--text-main);">${escapeHtml(t.id || 'INV')} • ${escapeHtml(t.pelanggan || 'Pelanggan')}</strong>
                <span class="badge badge-lunas">${escapeHtml(t.status || 'Lunas')}</span>
            </div>
            <div style="font-size: 11px; color: var(--text-muted); display: flex; justify-content: space-between;">
                <span>${escapeHtml(t.layanan || '-')} (Kasir ${escapeHtml(t.kasir || 'Daniel')})</span>
                <strong style="color: var(--primary);">${formatRupiahJS(t.total_bayar || t.total_biaya || 0)}</strong>
            </div>
        `;
        listEl.appendChild(item);
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Inisialisasi awal saat halaman dimuat
document.addEventListener('DOMContentLoaded', () => {
    switchLaporanPeriode(currentActiveFilter);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
