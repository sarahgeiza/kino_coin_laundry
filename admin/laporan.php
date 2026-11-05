<?php
$page_title = 'Laporan Transaksi - Kino Coin Laundry';
$app_role = 'pemilik';
$active_tab = 'laporan';

require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/header.php';

$periode = in_array($_GET['periode'] ?? '', ['Hari Ini', 'Kemarin', '7 Hari', 'Custom', 'Kosong'], true)
    ? $_GET['periode']
    : 'Hari Ini';
$today = new DateTimeImmutable('today');
$startDate = $today;
$endDate = $today;
if ($periode === 'Kemarin') {
    $startDate = $today->modify('-1 day');
    $endDate = $startDate;
} elseif ($periode === '7 Hari') {
    $startDate = $today->modify('-6 days');
} elseif ($periode === 'Custom') {
    $requestedStart = (string)($_GET['start'] ?? $today->modify('-6 days')->format('Y-m-d'));
    $requestedEnd = (string)($_GET['end'] ?? $today->format('Y-m-d'));
    $parsedStart = DateTimeImmutable::createFromFormat('!Y-m-d', $requestedStart);
    $parsedEnd = DateTimeImmutable::createFromFormat('!Y-m-d', $requestedEnd);
    if (
        $parsedStart && $parsedEnd
        && $parsedStart->format('Y-m-d') === $requestedStart
        && $parsedEnd->format('Y-m-d') === $requestedEnd
        && $parsedStart <= $parsedEnd
    ) {
        $startDate = $parsedStart;
        $endDate = $parsedEnd;
    }
}
$startKey = $startDate->format('Y-m-d');
$endKey = $endDate->format('Y-m-d');
$reportTransactions = rows(
    "SELECT COALESCE(t.no_invoice, 'TRX-' || t.id_transaksi::text) AS id,
            p.nama_pelanggan AS pelanggan,
            p.no_hp AS wa,
            STRING_AGG(DISTINCT l.nama_layanan, ', ' ORDER BY l.nama_layanan) AS layanan,
            SUM(COALESCE(d.berat, d.jumlah::numeric))::numeric AS kuantitas,
            u.nama AS kasir,
            s.nama_shift AS shift,
            INITCAP(REPLACE(t.status_pembayaran, '_', ' ')) AS status,
            t.total_bayar,
            t.total_bayar AS total_biaya,
            t.jenis_pembayaran AS metode,
            TO_CHAR(t.tanggal_transaksi, 'DD Mon YYYY') AS tanggal,
            TO_CHAR(t.tanggal_transaksi, 'HH24:MI') AS waktu,
            TO_CHAR(t.tanggal_transaksi, 'YYYY-MM-DD') AS date_key
     FROM transaksi t
     JOIN pelanggan p USING (id_pelanggan)
     JOIN pengguna u ON u.id_pengguna = t.id_kasir
     JOIN shift s USING (id_shift)
     JOIN detail_transaksi d USING (id_transaksi)
     JOIN layanan l USING (id_layanan)
     WHERE t.tanggal_transaksi >= ?::date
       AND t.tanggal_transaksi < (?::date + INTERVAL '1 day')
     GROUP BY t.id_transaksi, p.id_pelanggan, u.id_pengguna, s.id_shift
     ORDER BY t.tanggal_transaksi DESC",
    [$startKey, $endKey]
);
$reportTotal = array_sum(array_map(static fn(array $t): int => (int)$t['total_bayar'], $reportTransactions));
$chartStart = $periode === 'Custom' ? $startDate : $endDate->modify('-6 days');
$dailyRows = rows(
    "SELECT TO_CHAR(tanggal_transaksi, 'YYYY-MM-DD') AS tanggal,
            COUNT(*)::int AS jumlah,
            COALESCE(SUM(total_bayar), 0)::bigint AS pendapatan
     FROM transaksi
     WHERE tanggal_transaksi >= ?::date
       AND tanggal_transaksi < (?::date + INTERVAL '1 day')
     GROUP BY TO_CHAR(tanggal_transaksi, 'YYYY-MM-DD')",
    [$chartStart->format('Y-m-d'), $endKey]
);
$dailyByDate = [];
foreach ($dailyRows as $dailyRow) {
    $dailyByDate[$dailyRow['tanggal']] = $dailyRow;
}
$reportDays = [];
$dayLabels = ['Sun' => 'Min', 'Mon' => 'Sen', 'Tue' => 'Sel', 'Wed' => 'Rab', 'Thu' => 'Kam', 'Fri' => 'Jum', 'Sat' => 'Sab'];
for ($day = $chartStart; $day <= $endDate; $day = $day->modify('+1 day')) {
    $key = $day->format('Y-m-d');
    $reportDays[] = [
        'dateKey' => $key,
        'd' => $day->format('d'),
        'name' => $dayLabels[$day->format('D')],
        'omset' => (int)($dailyByDate[$key]['pendapatan'] ?? 0),
        'count' => (int)($dailyByDate[$key]['jumlah'] ?? 0),
        'is_active' => $key === $today->format('Y-m-d')
    ];
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
            <input type="date" id="customStartDate" class="form-control" value="<?= htmlspecialchars($startKey) ?>" style="font-size: 12px; padding: 8px; flex: 1; min-width: 120px;">
            <span style="font-size: 12px; font-weight: 700; color: #64748b;">s/d</span>
            <input type="date" id="customEndDate" class="form-control" value="<?= htmlspecialchars($endKey) ?>" style="font-size: 12px; padding: 8px; flex: 1; min-width: 120px;">
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
        <span id="laporanTotalOmset" style="transition: color 0.3s;"><?= format_rupiah($reportTotal) ?></span>
        <span id="laporanTxCount" style="font-size: 12px; font-weight: normal; color: var(--text-muted);">(<?= count($reportTransactions) ?> transaksi)</span>
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
window.__REPORT_TXS = <?= json_encode($reportTransactions, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.__REPORT_DAYS = <?= json_encode($reportDays, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.__REPORT_TOTAL = <?= (int)$reportTotal ?>;
let currentActiveFilter = <?= json_encode($periode) ?>;

function formatRupiahJS(num) {
    return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
}

function shortRupiah(num) {
    if (num <= 0) return '0';
    if (num >= 1000000) return (num / 1000000).toFixed(1).replace('.0','') + 'jt';
    return Math.round(num / 1000) + 'k';
}

function switchLaporanPeriode(mode) {
    const customBox = document.getElementById('customDateBox');
    if (mode === 'Custom') {
        customBox.classList.add('open');
    }

    if (mode !== currentActiveFilter) {
        if (mode === 'Custom') return;
        window.location.href = window.location.pathname + '?periode=' + encodeURIComponent(mode);
        return;
    }

    if (mode !== 'Custom') customBox.classList.remove('open');
    if (mode === 'Kosong') {
        renderLaporanView([], [], 'TOTAL OMSET (KOSONG)', 0, 'Grafik Tren Omset (Kosong)');
        return;
    }

    const titles = {
        'Hari Ini': 'TOTAL OMSET (HARI INI)',
        'Kemarin': 'TOTAL OMSET (KEMARIN)',
        '7 Hari': 'TOTAL OMSET (7 HARI)',
        'Custom': 'TOTAL OMSET (CUSTOM)'
    };
    const subtitles = {
        'Hari Ini': 'Grafik Tren Omset 7 Hari',
        'Kemarin': 'Grafik Tren Omset (Kemarin)',
        '7 Hari': 'Grafik Tren Omset 7 Hari',
        'Custom': 'Grafik Tren Omset Custom'
    };
    renderLaporanView(
        window.__REPORT_DAYS,
        window.__REPORT_TXS,
        mode === 'Custom'
            ? `TOTAL OMSET (CUSTOM: ${document.getElementById('customStartDate').value} s/d ${document.getElementById('customEndDate').value})`
            : (titles[mode] || 'TOTAL OMSET'),
        window.__REPORT_TOTAL,
        subtitles[mode] || 'Grafik Tren Omset'
    );
}

function applyCustomDateFilter() {
    const startVal = document.getElementById('customStartDate').value;
    const endVal = document.getElementById('customEndDate').value;

    if (!startVal || !endVal) return;
    if (startVal > endVal) {
        showToast('Tanggal awal tidak boleh melebihi tanggal akhir', 'error');
        return;
    }

    const params = new URLSearchParams({ periode: 'Custom', start: startVal, end: endVal });
    window.location.href = window.location.pathname + '?' + params.toString();
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
