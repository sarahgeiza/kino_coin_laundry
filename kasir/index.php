<?php
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
check_auth_guard('kasir');

$kasirId = (int)$_SESSION['user_id'];
$current_kasir_name = (string)$_SESSION['username'];

if (isset($_POST['action']) && $_POST['action'] === 'mulai_shift') {
    if (
        empty($_SESSION['csrf'])
        || empty($_POST['csrf_token'])
        || !hash_equals((string)$_SESSION['csrf'], (string)$_POST['csrf_token'])
    ) {
        http_response_code(403);
        exit('Token CSRF tidak valid');
    }

    $connection = getConnection();
    if (!pg_query($connection, 'BEGIN')) {
        throw new RuntimeException('Gagal memulai check-in shift');
    }

    try {
        $active = one(
            'SELECT id_shift_operasional
             FROM shift_operasional
             WHERE id_kasir = ? AND status = ?
             FOR UPDATE',
            [$kasirId, 'aktif']
        );

        if (!$active) {
            $scheduled = rows(
                "SELECT j.id_jadwal, j.id_shift
                 FROM jadwal_shift j
                 JOIN shift s USING (id_shift)
                 WHERE j.id_kasir = ?
                   AND j.tanggal = CURRENT_DATE
                   AND j.status = 'dijadwalkan'
                   AND ((s.jam_mulai < s.jam_selesai
                         AND CURRENT_TIME >= s.jam_mulai
                         AND CURRENT_TIME < s.jam_selesai)
                     OR (s.jam_mulai >= s.jam_selesai
                         AND (CURRENT_TIME >= s.jam_mulai
                              OR CURRENT_TIME < s.jam_selesai)))
                 ORDER BY j.id_jadwal",
                [$kasirId]
            );

            if (!$scheduled) {
                $scheduled = rows(
                    'SELECT NULL::bigint AS id_jadwal, id_shift
                     FROM shift
                     WHERE (jam_mulai < jam_selesai
                            AND CURRENT_TIME >= jam_mulai
                            AND CURRENT_TIME < jam_selesai)
                        OR (jam_mulai >= jam_selesai
                            AND (CURRENT_TIME >= jam_mulai
                                 OR CURRENT_TIME < jam_selesai))
                     ORDER BY id_shift'
                );
            }

            if (count($scheduled) !== 1) {
                pg_query($connection, 'ROLLBACK');
                header('Location: index.php?toast=need_schedule');
                exit;
            }

            one(
                'INSERT INTO shift_operasional
                    (id_kasir, id_shift, id_jadwal, status, mulai_pada)
                 VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
                 RETURNING id_shift_operasional',
                [
                    $kasirId,
                    (int)$scheduled[0]['id_shift'],
                    $scheduled[0]['id_jadwal'] !== null
                        ? (int)$scheduled[0]['id_jadwal']
                        : null,
                    'aktif'
                ]
            );
            if (!empty($scheduled[0]['id_jadwal'])) {
                execute_sql(
                    "UPDATE jadwal_shift SET status = 'dimulai' WHERE id_jadwal = ?",
                    [(int)$scheduled[0]['id_jadwal']]
                );
            }
        }

        if (!pg_query($connection, 'COMMIT')) {
            throw new RuntimeException('Gagal menyimpan check-in shift');
        }
    } catch (Throwable $e) {
        pg_query($connection, 'ROLLBACK');
        throw $e;
    }

    $_SESSION['kasir_checked_in'] = true;
    header('Location: index.php?shift_started=1');
    exit;
}

$activeShift = one(
    'SELECT so.id_shift_operasional, so.id_shift, s.nama_shift, s.jam_mulai, s.jam_selesai
     FROM shift_operasional so
     JOIN shift s USING (id_shift)
     WHERE so.id_kasir = ? AND so.status = ?
     ORDER BY so.id_shift_operasional DESC
     LIMIT 1',
    [$kasirId, 'aktif']
);

if ($activeShift) {
    $_SESSION['kasir_checked_in'] = true;
    $kasir_shift = [
        'nama' => $activeShift['nama_shift'],
        'jam_tampil' => substr((string)$activeShift['jam_mulai'], 0, 5)
            . ' — ' . substr((string)$activeShift['jam_selesai'], 0, 5)
    ];
} else {
    $_SESSION['kasir_checked_in'] = false;
    $scheduled = rows(
        "SELECT s.nama_shift, s.jam_mulai, s.jam_selesai
         FROM jadwal_shift j
         JOIN shift s USING (id_shift)
         WHERE j.id_kasir = ?
           AND j.tanggal = CURRENT_DATE
           AND j.status = 'dijadwalkan'
           AND ((s.jam_mulai < s.jam_selesai
                 AND CURRENT_TIME >= s.jam_mulai
                 AND CURRENT_TIME < s.jam_selesai)
             OR (s.jam_mulai >= s.jam_selesai
                 AND (CURRENT_TIME >= s.jam_mulai
                      OR CURRENT_TIME < s.jam_selesai)))
         ORDER BY j.id_jadwal",
        [$kasirId]
    );
    if (!$scheduled) {
        $scheduled = rows(
            'SELECT nama_shift, jam_mulai, jam_selesai
             FROM shift
             WHERE (jam_mulai < jam_selesai
                    AND CURRENT_TIME >= jam_mulai
                    AND CURRENT_TIME < jam_selesai)
                OR (jam_mulai >= jam_selesai
                    AND (CURRENT_TIME >= jam_mulai
                         OR CURRENT_TIME < jam_selesai))
             ORDER BY id_shift'
        );
    }
    $kasir_shift = count($scheduled) === 1
        ? [
            'nama' => $scheduled[0]['nama_shift'],
            'jam_tampil' => substr((string)$scheduled[0]['jam_mulai'], 0, 5)
                . ' — ' . substr((string)$scheduled[0]['jam_selesai'], 0, 5)
        ]
        : ['nama' => 'Jadwal tidak tersedia', 'jam_tampil' => '--:-- — --:--'];
}

$page_title = 'Dashboard Kasir - Kino Coin Laundry';
$app_role = 'kasir';
$active_tab = 'home';

require_once __DIR__ . '/../includes/header.php';

// Check if check-in needed (Modal M1)
$need_checkin = !$activeShift;

$summary = $activeShift ? one(
    "SELECT COUNT(DISTINCT t.id_transaksi)::int AS total_transaksi,
            COALESCE(SUM(t.total_bayar), 0)::bigint AS total_pendapatan,
            COALESCE(SUM(d.total_koin), 0)::int AS total_koin_mesin
     FROM transaksi t
     LEFT JOIN (
        SELECT d.id_transaksi,
               SUM(d.jumlah_koin) AS total_koin
        FROM detail_transaksi d
        GROUP BY d.id_transaksi
     ) d USING (id_transaksi)
     WHERE t.id_shift_operasional = ?",
    [(int)$activeShift['id_shift_operasional']]
) : [];
$total_live_income = (int)($summary['total_pendapatan'] ?? 0);
$total_live_tx = (int)($summary['total_transaksi'] ?? 0);
$total_live_coins = (int)($summary['total_koin_mesin'] ?? 0);

$disp_income = format_rupiah($total_live_income);
$disp_coins = number_format($total_live_coins, 0, ',', '.') . ' koin';
$recent_txs = $activeShift ? rows(
    "SELECT t.no_invoice AS id,
            p.nama_pelanggan AS pelanggan,
            p.no_hp AS wa,
            STRING_AGG(DISTINCT l.nama_layanan, ', ' ORDER BY l.nama_layanan) AS layanan,
            SUM(COALESCE(d.berat, d.jumlah::numeric))::numeric AS kuantitas,
            t.total_bayar,
            t.total_bayar AS total_biaya,
            t.jenis_pembayaran AS metode,
            INITCAP(REPLACE(t.status_pembayaran, '_', ' ')) AS status,
            u.nama AS kasir,
            s.nama_shift AS shift,
            TO_CHAR(t.tanggal_transaksi, 'DD Mon YYYY') AS tanggal,
            TO_CHAR(t.tanggal_transaksi, 'HH24:MI') AS waktu,
            COALESCE(SUM(d.jumlah_koin), 0)::int AS total_koin_mesin
     FROM transaksi t
     JOIN pelanggan p USING (id_pelanggan)
     JOIN pengguna u ON u.id_pengguna = t.id_kasir
     JOIN shift s USING (id_shift)
     JOIN detail_transaksi d USING (id_transaksi)
     JOIN layanan l USING (id_layanan)
     WHERE t.id_shift_operasional = ?
     GROUP BY t.id_transaksi, p.id_pelanggan, u.id_pengguna, s.id_shift
     ORDER BY t.tanggal_transaksi DESC
     LIMIT 4",
    [(int)$activeShift['id_shift_operasional']]
) : [];
?>

<!-- Tombol Aksi Cepat (Persis Figma K1) -->
<div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 10px; margin-bottom: 16px;">
    <a href="transaksi-baru.php" class="btn btn-primary" style="padding: 12px; font-size: 13px;">
        <span>+ Transaksi Baru</span>
    </a>
    <button type="button" class="btn btn-outline" style="padding: 12px; font-size: 13px;" onclick="openModal('modalScanQr')">
        <span><?= get_icon('camera', 16) ?> Scan QR</span>
    </button>
</div>

<!-- Ringkasan Shift (Metrik 3 Box) -->
<div class="card" style="padding: 16px; margin-bottom: 14px;">
    <div class="card-title" style="margin-bottom: 10px;">
        <span>Ringkasan Shift</span>
        <span style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($kasir_shift['nama']) ?></span>
    </div>
    <div class="metrics-row" style="margin-bottom: 0;">
        <div class="metric-box blue">
            <div class="label">Pendapatan</div>
            <div class="value"><?= $disp_income ?></div>
            <div class="sub">Cash dan QRIS tercatat</div>
        </div>
        <div class="metric-box orange">
            <div class="label">Jumlah Transaksi</div>
            <div class="value"><?= number_format($total_live_tx, 0, ',', '.') ?></div>
            <div class="sub">Transaksi pada shift ini</div>
        </div>
        <div class="metric-box amber">
            <div class="label">Koin Mesin</div>
            <div class="value"><?= $disp_coins ?></div>
            <div class="sub">Token operasional, bukan pembayaran</div>
        </div>
    </div>
</div>

<!-- Informasi metode pembayaran dan token mesin -->
<div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md); padding: 10px 14px; font-size: 12px; color: #1e40af; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
    <?= get_icon('info', 18, '#1e40af') ?>
    <span>Pembayaran menggunakan Cash atau QRIS. Koin hanya token untuk menjalankan mesin.</span>
</div>

<!-- Daftar Transaksi Terakhir -->
<div class="card">
    <div class="card-title">
        <span>Transaksi Terakhir</span>
        <a href="transaksi.php" style="font-size: 12px; color: var(--primary-light); text-decoration: none; font-weight: 700;">Lihat Semua →</a>
    </div>

    <?php foreach ($recent_txs as $trx):
        $coin_desc = (int)$trx['total_koin_mesin'] . ' koin mesin • ' . strtoupper($trx['metode']);
    ?>
    <div class="trx-item" onclick="showInvoiceDetail(<?= htmlspecialchars(json_encode($trx)) ?>)">
        <div class="trx-top">
            <div>
                <span class="trx-id"><?= htmlspecialchars($trx['id']) ?></span>
                <div class="trx-name"><?= htmlspecialchars($trx['pelanggan']) ?></div>
            </div>
            <span class="badge badge-lunas"><?= htmlspecialchars($trx['status']) ?></span>
        </div>
        <div class="trx-meta-line">
            <?= htmlspecialchars($trx['layanan']) ?> • <?= htmlspecialchars($trx['kuantitas']) ?>
        </div>
        <div class="trx-bottom">
            <div>
                <div class="trx-price"><?= format_rupiah($trx['total_bayar']) ?></div>
                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                    <?= htmlspecialchars($trx['waktu']) ?> | Kasir <?= htmlspecialchars($trx['kasir']) ?> | <?= $coin_desc ?>
                </div>
            </div>
            <button type="button" class="trx-details-btn">Detail</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Floating Action Button + (Persis Figma K1) -->
<a href="transaksi-baru.php" class="fab-btn" title="Transaksi Baru">+</a>

<!-- Modal M1: Mulai Shift -->
<div class="sheet-overlay <?= $need_checkin ? 'active' : '' ?>" id="modalMulaiShift">
    <div class="sheet-card">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Mulai Shift</h3>
            <?php if (!$need_checkin): ?>
                <button type="button" class="sheet-close" onclick="closeModal('modalMulaiShift')">&times;</button>
            <?php endif; ?>
        </div>
        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
            Selamat bekerja, <strong><?= htmlspecialchars($user_name) ?></strong>. Konfirmasi kehadiran untuk membuka akses transaksi kasir.
        </p>

        <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 14px; margin-bottom: 20px; font-size: 13px; line-height: 1.8;">
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Shift:</span>
                <strong><?= htmlspecialchars($kasir_shift['nama']) ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Jadwal Shift:</span>
                <strong><?= htmlspecialchars($kasir_shift['jam_tampil']) ?> WIB</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Jam Sekarang:</span>
                <strong style="color: var(--primary);"><?= date('H:i') ?> WIB</strong>
            </div>
        </div>

        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="mulai_shift">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
            <button type="submit" class="btn btn-primary" style="padding: 14px;">
                Mulai Shift &amp; Presensi
            </button>
        </form>
    </div>
</div>

<!-- Modal M2 / M3: Detail Transaksi -->
<div class="sheet-overlay" id="modalTrxDetail">
    <div class="sheet-card">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <div>
                <span style="font-size: 11px; font-weight: 800; color: var(--primary);" id="detailInvoiceId">Invoice</span>
                <h3 class="sheet-title" id="detailCustomerName">Budi Santoso</h3>
            </div>
            <button type="button" class="sheet-close" onclick="closeModal('modalTrxDetail')">&times;</button>
        </div>
        
        <div id="detailContentBody" style="font-size: 13px; line-height: 1.6;"></div>

        <button type="button" class="btn btn-outline" style="margin-top: 18px;" onclick="closeModal('modalTrxDetail')">
            Tutup
        </button>
    </div>
</div>

<!-- Modal M1a-c: Scan QR Kartu Pelanggan (Live Webcam + Fallback Input Manual M1c) -->
<div class="sheet-overlay" id="modalScanQr">
    <div class="sheet-card">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Scan QR Kartu Pelanggan</h3>
            <button type="button" class="sheet-close" onclick="closeModal('modalScanQr')">&times;</button>
        </div>

        <div id="qrViewfinder" style="width: 100%; border: 2.5px dashed #ea580c; border-radius: 16px;
            background: rgba(234, 88, 12, 0.05); display: flex; flex-direction: column;
            align-items: center; justify-content: center; margin-bottom: 12px;
            padding: 12px; position: relative; overflow: hidden; min-height: 180px;">
            <div id="cameraReader" style="width: 100%; max-width: 240px; margin: 0 auto;"></div>
            <div id="qrStatusText" style="font-size: 12px; font-weight: 700; color: #ea580c; text-align: center; margin-top: 8px;">
                Arahkan QR ke kamera...
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Atau ketik kode kartu manual:</label>
            <input type="text" id="qrManualInput" class="form-control" placeholder="Kode yang tertera pada kartu" value="">
        </div>

        <div style="display: flex; gap: 10px; margin-top: 14px;">
            <button type="button" class="btn btn-outline" onclick="closeModal('modalScanQr')">Tutup</button>
            <button type="button" class="btn btn-primary" onclick="applyScannedCode()">Gunakan Kode</button>
        </div>
    </div>
</div>

<script>
function showInvoiceDetail(trx) {
    document.getElementById('detailInvoiceId').textContent = trx.id;
    document.getElementById('detailCustomerName').textContent = trx.pelanggan;

    const paymentMethod = String(trx.metode || '').toLowerCase() === 'qris' ? 'QRIS' : 'Cash';
    const paymentDetail = `
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px; margin-bottom: 12px; color: #15803d;">
            <strong>Metode Pembayaran: ${paymentMethod}</strong>
            <p style="font-size: 11px; margin-top: 2px;">Koin mesin digunakan sebagai token operasional dan tidak dihitung sebagai pembayaran.</p>
            <p style="font-size: 11px; margin-top: 2px;">Koin mesin digunakan: ${Number(trx.total_koin_mesin || 0).toLocaleString('id-ID')}</p>
        </div>
    `;

    const html = `
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 12px;">
            <p><strong>Layanan:</strong> ${trx.layanan}</p>
            <p><strong>Kuantitas:</strong> ${trx.kuantitas}</p>
            <p><strong>WhatsApp:</strong> ${trx.wa || '-'}</p>
            <p><strong>Waktu:</strong> ${trx.tanggal} • ${trx.waktu}</p>
            <p><strong>Kasir:</strong> ${trx.kasir} (${trx.shift})</p>
        </div>
        ${paymentDetail}
        <div style="display: flex; justify-content: space-between; font-size: 15px; font-weight: 900; padding: 10px 0; border-top: 1px dashed #e2e8f0;">
            <span>Total Tagihan:</span>
            <span>Rp ${(trx.total_biaya || 0).toLocaleString('id-ID')}</span>
        </div>
    `;

    document.getElementById('detailContentBody').innerHTML = html;
    openModal('modalTrxDetail');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
