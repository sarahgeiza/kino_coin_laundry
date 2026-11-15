<?php
$page_title = 'Laporan Transaksi - Kino Coin Laundry';
$app_role = 'pemilik';
$active_tab = 'laporan';

require_once __DIR__ . '/../includes/header.php';

$periode = $_GET['periode'] ?? 'Hari Ini';
$all_session_txs = $_SESSION['transactions'] ?? [];
$today_key = date('Y-m-d');

// Omset hari ini HANYA dari transaksi bertanggal hari ini (bukan seluruh riwayat)
$today_real_omset = 0;
$today_tx_count = 0;
foreach ($all_session_txs as $t) {
    if (($t['filter_date'] ?? '') === $today_key) {
        $today_real_omset += ($t['total_bayar'] ?? $t['total_biaya'] ?? 0);
        $today_tx_count++;
    }
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
<div id="laporanEmptyCard" class="card" style="display: none; text-align: center; padding: 34px 20px; color: var(--text-muted); margin-bottom: 16px;">
    <div style="margin-bottom: 8px;"><?= get_icon('rekap', 36, '#94a3b8') ?></div>
    <strong id="laporanEmptyTitle" style="font-size: 14px; color: var(--text-main);">Tidak ada transaksi di periode ini</strong>
    <p id="laporanEmptyHint" style="font-size: 12px; margin-top: 4px; line-height: 1.6;">Pilih periode lain atau atur rentang tanggal.</p>
    <div style="display: flex; gap: 8px; justify-content: center; margin-top: 14px; flex-wrap: wrap;">
        <button type="button" class="btn btn-outline" style="width: auto; padding: 8px 14px; font-size: 12px;" onclick="switchLaporanPeriode('7 Hari')">Lihat 7 Hari Terakhir</button>
        <button type="button" class="btn btn-outline" style="width: auto; padding: 8px 14px; font-size: 12px;" onclick="jumpToLastTransaction()">Lihat Transaksi Terakhir</button>
    </div>
</div>

<!-- Main Omset Summary & Dynamic Animated Chart -->
<div id="laporanMainCard" class="card">
    <div id="laporanPeriodeTitle" style="font-size: 11px; color: var(--text-muted); font-weight: 700; letter-spacing: 0.5px;">TOTAL OMSET (HARI INI)</div>
    <div style="font-size: 24px; font-weight: 900; color: var(--primary-dark); margin: 4px 0 14px 0; display: flex; align-items: baseline; gap: 8px;">
        <span id="laporanTotalOmset" style="transition: color 0.3s;"><?= format_rupiah($today_real_omset) ?></span>
        <span id="laporanTxCount" style="font-size: 12px; font-weight: normal; color: var(--text-muted);">(<?= $today_tx_count ?> transaksi)</span>
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
/* =========================================================================
   Laporan Transaksi — 100% data riil Supabase (tanpa data dummy)
   Semua periode (Hari Ini / Kemarin / 7 Hari / Custom / Kosong) dihitung
   dari window.__LIVE_TX yang berasal dari tabel transaksi.
   ========================================================================= */
window.__LIVE_TX = <?= json_encode(array_values($all_session_txs)) ?>;
window.__TODAY_KEY = "<?= $today_key ?>";

const HARI = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
let currentActiveFilter = '<?= htmlspecialchars($periode) ?>';

function ymd(d) {
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}
function shiftDays(base, delta) {
    const d = new Date(base.getTime());
    d.setDate(d.getDate() + delta);
    return d;
}
function formatRupiahJS(num) {
    return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
}
function shortRupiah(num) {
    if (!num || num <= 0) return '0';
    if (num >= 1000000) return (num / 1000000).toFixed(1).replace('.0', '') + 'jt';
    return Math.round(num / 1000) + 'k';
}

/* Kelompokkan transaksi riil per tanggal */
function groupTxByDate(txs) {
    const map = {};
    txs.forEach(t => {
        const key = t.filter_date || '';
        if (!key) return;
        if (!map[key]) map[key] = { omset: 0, count: 0, txs: [] };
        map[key].omset += Number(t.total_bayar ?? t.total_biaya ?? 0);
        map[key].count += 1;
        map[key].txs.push(t);
    });
    return map;
}

/* Bangun deret hari (inklusif) dari startDate s/d endDate berdasarkan data riil */
function buildDays(startDate, endDate) {
    const map = groupTxByDate(window.__LIVE_TX || []);
    const days = [];
    let cur = new Date(startDate.getTime());
    let guard = 0;
    while (cur <= endDate && guard < 62) {
        const key = ymd(cur);
        const bucket = map[key] || { omset: 0, count: 0, txs: [] };
        days.push({
            dateKey: key,
            d: String(cur.getDate()).padStart(2, '0'),
            name: HARI[cur.getDay()],
            omset: bucket.omset,
            count: bucket.count,
            txs: bucket.txs,
            is_active: true
        });
        cur = shiftDays(cur, 1);
        guard++;
    }
    return days;
}

function setActiveChip(mode) {
    const map = {
        'Hari Ini': 'btnFilterHariIni',
        'Kemarin': 'btnFilterKemarin',
        '7 Hari': 'btnFilter7Hari',
        'Custom': 'btnFilterCustom',
        'Kosong': 'btnFilterKosong'
    };
    Object.keys(map).forEach(k => {
        const el = document.getElementById(map[k]);
        if (!el) return;
        el.classList.toggle('active', k === mode);
    });
}

function switchLaporanPeriode(mode) {
    currentActiveFilter = mode;
    setActiveChip(mode);

    const url = window.location.pathname + '?periode=' + encodeURIComponent(mode);
    window.history.replaceState({ periode: mode }, '', url);

    const box = document.getElementById('customDateBox');
    if (mode === 'Custom') {
        if (box) box.classList.add('open');
        applyCustomDateFilter();
        return;
    }
    if (box) box.classList.remove('open');

    const today = new Date(window.__TODAY_KEY + 'T00:00:00');

    if (mode === 'Kosong') {
        renderLaporanView([], [], 'TOTAL OMSET (KOSONG)', 0, 'Grafik Tren Omset (Kosong)', 'Kosong');
        return;
    }
    if (mode === 'Kemarin') {
        const y = shiftDays(today, -1);
        const days = buildDays(y, y);
        renderLaporanView(days, collectTx(days), 'TOTAL OMSET (KEMARIN)', sumOmset(days), 'Grafik Omset Kemarin', 'Kemarin');
        return;
    }
    if (mode === '7 Hari') {
        const days = buildDays(shiftDays(today, -6), today);
        renderLaporanView(days, collectTx(days), 'TOTAL OMSET (7 HARI)', sumOmset(days), 'Grafik Tren Omset 7 Hari', '7 Hari');
        return;
    }
    // Hari Ini
    const days = buildDays(today, today);
    renderLaporanView(days, collectTx(days), 'TOTAL OMSET (HARI INI)', sumOmset(days), 'Grafik Omset Hari Ini', 'Hari Ini');
}

function applyCustomDateFilter() {
    const s = document.getElementById('customStartDate');
    const e = document.getElementById('customEndDate');
    if (!s || !e || !s.value || !e.value) return;

    if (s.value > e.value) {
        showToast('Tanggal awal tidak boleh melebihi tanggal akhir', 'error');
        return;
    }
    const start = new Date(s.value + 'T00:00:00');
    const end = new Date(e.value + 'T00:00:00');

    const days = buildDays(start, end);
    const label = 'CUSTOM ' + s.value.slice(5) + ' s/d ' + e.value.slice(5);
    renderLaporanView(
        days,
        collectTx(days),
        'TOTAL OMSET (' + label + ')',
        sumOmset(days),
        'Grafik Tren Omset Custom (' + days.length + ' hari)',
        'Custom ' + s.value + ' s/d ' + e.value
    );
}

function collectTx(days) {
    let out = [];
    days.forEach(d => { out = out.concat(d.txs || []); });
    // urutkan terbaru dulu
    return out.slice().reverse();
}
function sumOmset(days) {
    return days.reduce((a, d) => a + d.omset, 0);
}

const BULAN = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
function bulanID(m) { return BULAN[m]; }

function lastTransactionDate() {
    const keys = (window.__LIVE_TX || []).map(t => t.filter_date).filter(Boolean).sort();
    return keys.length ? keys[keys.length - 1] : null;
}

function jumpToLastTransaction() {
    const key = lastTransactionDate();
    if (!key) { showToast('Belum ada transaksi di database', 'error'); return; }
    const d = new Date(key + 'T00:00:00');
    const s = document.getElementById('customStartDate');
    const e = document.getElementById('customEndDate');
    if (s) s.value = key;
    if (e) e.value = key;
    switchLaporanPeriode('Custom');
    showToast('Menampilkan transaksi ' + String(d.getDate()).padStart(2, '0') + ' ' + bulanID(d.getMonth()) + ' ' + d.getFullYear(), 'success');
}

function renderLaporanView(days, txList, titleText, totalOmset, subtitleText, periodeLabel) {
    const mainCard = document.getElementById('laporanMainCard');
    const emptyCard = document.getElementById('laporanEmptyCard');

    // Selalu simpan konteks ekspor, termasuk saat kosong (supaya tidak mengekspor data periode lain)
    window.__CURRENT_LAPORAN_TXS = txList;
    window.__CURRENT_LAPORAN_PERIODE = periodeLabel || currentActiveFilter;

    document.getElementById('laporanPeriodeTitle').textContent = titleText;
    document.getElementById('laporanTotalOmset').textContent = formatRupiahJS(totalOmset);
    document.getElementById('laporanTxCount').textContent = '(' + txList.length + ' transaksi)';
    document.getElementById('chartSubTitle').textContent = subtitleText;

    if (!txList.length) {
        if (mainCard) mainCard.style.display = 'none';
        if (emptyCard) emptyCard.style.display = 'block';

        const titleEl = document.getElementById('laporanEmptyTitle');
        const hintEl = document.getElementById('laporanEmptyHint');
        const lastKey = lastTransactionDate();

        if (titleEl) titleEl.textContent = 'Tidak ada transaksi pada periode ' + (periodeLabel || currentActiveFilter);
        if (hintEl) {
            if (lastKey) {
                const lk = new Date(lastKey + 'T00:00:00');
                hintEl.innerHTML = 'Transaksi terakhir tercatat pada <strong>' +
                    String(lk.getDate()).padStart(2, '0') + ' ' + bulanID(lk.getMonth()) + ' ' + lk.getFullYear() +
                    '</strong>.<br>Gunakan tombol di bawah untuk melihat data yang tersedia.';
            } else {
                hintEl.textContent = 'Belum ada transaksi sama sekali di database.';
            }
        }
        return;
    }
    if (mainCard) mainCard.style.display = 'block';
    if (emptyCard) emptyCard.style.display = 'none';

    // ---- Chart bars ----
    const bars = document.getElementById('chartBarsContainer');
    const labels = document.getElementById('chartLabelsContainer');
    bars.innerHTML = '';
    labels.innerHTML = '';

    const maxOmset = Math.max(...days.map(d => d.omset), 1);

    days.forEach(d => {
        const heightPx = d.omset <= 0 ? 6 : Math.max(12, Math.round((d.omset / maxOmset) * 80));
        const active = d.omset > 0;
        const bg = active
            ? 'linear-gradient(180deg, #3b82f6 0%, #1d4ed8 100%)'
            : '#cbd5e1';

        const col = document.createElement('div');
        col.className = 'chart-col';
        col.title = d.d + ' ' + d.name + ': ' + formatRupiahJS(d.omset) + ' (' + d.count + ' trx)';
        col.innerHTML =
            '<span style="font-size:9px;font-weight:800;color:' + (active ? '#1d4ed8' : '#94a3b8') + ';margin-bottom:4px;white-space:nowrap;">'
            + shortRupiah(d.omset) + '</span>' +
            '<div class="chart-bar-el" style="height:' + heightPx + 'px;background:' + bg + ';box-shadow:' +
            (active ? '0 3px 8px rgba(37,99,235,0.35)' : 'none') + ';"></div>';
        bars.appendChild(col);

        const lbl = document.createElement('div');
        lbl.style.flex = '1';
        lbl.style.textAlign = 'center';
        lbl.innerHTML =
            '<span style="font-size:10px;font-weight:' + (active ? '900' : '700') + ';color:' + (active ? '#1e3a8a' : '#94a3b8') + ';display:block;">'
            + d.d + '</span>' +
            '<span style="font-size:9px;color:#94a3b8;display:block;margin-top:-2px;">' + d.name + '</span>';
        labels.appendChild(lbl);
    });

    // ---- Detail list ----
    const list = document.getElementById('laporanDetailList');
    list.innerHTML = '';
    txList.forEach(t => {
        const item = document.createElement('div');
        item.style.cssText = 'background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius-md);padding:10px 12px;margin-bottom:8px;';
        item.innerHTML =
            '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2px;">' +
                '<strong style="font-size:13px;color:var(--text-main);">' + esc(t.id) + ' • ' + esc(t.pelanggan) + '</strong>' +
                '<span class="badge badge-lunas">' + esc(t.status || 'Lunas') + '</span>' +
            '</div>' +
            '<div style="font-size:11px;color:var(--text-muted);display:flex;justify-content:space-between;">' +
                '<span>' + esc(t.layanan) + ' (Kasir ' + esc(t.kasir) + ') • ' + esc(t.tanggal) + ' ' + esc(t.waktu) + '</span>' +
                '<strong style="color:var(--primary);">' + formatRupiahJS(t.total_bayar ?? t.total_biaya ?? 0) + '</strong>' +
            '</div>';
        list.appendChild(item);
    });
}

function esc(s) {
    if (s === null || s === undefined) return '';
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', () => {
    switchLaporanPeriode(currentActiveFilter);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
