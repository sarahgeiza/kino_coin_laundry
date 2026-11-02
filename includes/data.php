
<?php
// Data view-model dari PostgreSQL. Tidak ada seed, fallback, atau penyimpanan operasional di session.
require_once __DIR__ . '/web_bootstrap.php';

$services = array_map(static function (array $row): array {
    return [
        'id' => (int)$row['id_layanan'],
        'nama' => $row['nama_layanan'],
        'tarif' => (int)$row['harga'],
        'durasi' => $row['durasi'] ?? 'Belum ditentukan',
        'status' => !empty($row['aktif']) && !in_array($row['aktif'], ['f','0',0], true) ? 'Aktif' : 'Nonaktif',
        'icon' => 'baju'
    ];
}, web_rows('SELECT id_layanan,nama_layanan,harga,durasi,aktif FROM layanan ORDER BY id_layanan'));

$dataRole = !empty($_SESSION['customer_id']) ? 'pelanggan' : (web_one('SELECT role FROM pengguna WHERE id_pengguna = ?', [(int)$_SESSION['user_id']])['role'] ?? '');
$activeOperationalShift = null;

if ($dataRole === 'kasir') {
    $activeOperationalShift = web_one(
        'SELECT so.id_shift_operasional,so.id_shift,so.mulai_pada,s.nama_shift,s.jam_mulai,s.jam_selesai
         FROM shift_operasional so
         JOIN shift s USING(id_shift)
         WHERE so.id_kasir = ? AND so.status = ?',
        [(int)$_SESSION['user_id'], 'aktif']
    );
}

$transactionWhere = '';
$transactionParams = [];

if ($dataRole === 'kasir') {
    $transactionWhere = ' WHERE t.id_shift_operasional = ?';
    $transactionParams[] = (int)($activeOperationalShift['id_shift_operasional'] ?? 0);
} elseif ($dataRole === 'pelanggan') {
    $transactionWhere = ' WHERE t.id_pelanggan = ?';
    $transactionParams[] = (int)$_SESSION['customer_id'];
}

$transactions = array_map(static function (array $row): array {
    return [
        'id' => $row['no_invoice'] ?: ('TRX-' . $row['id_transaksi']),
        'id_transaksi' => (int)$row['id_transaksi'],
        'pelanggan' => $row['nama_pelanggan'],
        'telepon' => $row['no_hp'],
        'layanan' => $row['layanan'] ?: '-',
        'berat' => (float)$row['berat'],
        'total_biaya' => (int)$row['total_bayar'],
        'jenis_pembayaran' => $row['jenis_pembayaran'],
        'sisa_bayar' => (int)$row['sisa_bayar'],
        'status_pengerjaan' => $row['status_pengerjaan'],
        'status' => ['lunas'=>'Lunas','sebagian'=>'Sebagian','belum_lunas'=>'Belum Lunas'][$row['status_pembayaran']] ?? ucfirst((string)$row['status_pembayaran']),
        'waktu' => date('H:i', strtotime($row['tanggal_transaksi'])),
        'tanggal' => date('d M Y', strtotime($row['tanggal_transaksi'])),
        'kasir' => $row['nama_kasir'],
        'shift' => $row['nama_shift']
    ];
}, web_rows(
    'SELECT t.id_transaksi,t.no_invoice,t.tanggal_transaksi,t.total_bayar,
            t.jenis_pembayaran,t.sisa_bayar,t.status_pembayaran,t.status_pengerjaan,
            p.nama_pelanggan,p.no_hp,u.nama AS nama_kasir,s.nama_shift,
            COALESCE(d.berat,0) AS berat,COALESCE(d.layanan,\'\') AS layanan
     FROM transaksi t
     JOIN pelanggan p USING(id_pelanggan)
     JOIN pengguna u ON u.id_pengguna=t.id_kasir
     JOIN shift s USING(id_shift)
     LEFT JOIN (
        SELECT dt.id_transaksi,SUM(COALESCE(dt.berat,0)) AS berat,
               STRING_AGG(DISTINCT l.nama_layanan,\', \') AS layanan
        FROM detail_transaksi dt
        JOIN layanan l USING(id_layanan)
        GROUP BY dt.id_transaksi
     ) d USING(id_transaksi)' . $transactionWhere . '
     ORDER BY t.id_transaksi DESC LIMIT 200',
    $transactionParams
));

$shifts_recap = $dataRole === 'pemilik' ? array_map(static function (array $row): array {
    return [
        'id' => (int)$row['id_shift_operasional'],
        'tanggal' => date('d M Y', strtotime($row['mulai_pada'])),
        'shift' => $row['nama_shift'],
        'kasir' => $row['nama_kasir'],
        'trx_count' => (int)$row['trx_count'],
        'total_kg' => (float)$row['total_kg'],
        'pendapatan' => (int)$row['pendapatan_kotor'],
        'selisih' => (int)($row['selisih'] ?? 0),
        'status' => $row['status'] === 'ditutup' && (int)($row['selisih'] ?? 0) === 0 ? 'Valid' : ($row['status'] === 'aktif' ? 'Aktif' : 'Review')
    ];
}, web_rows(
    'SELECT so.id_shift_operasional,so.mulai_pada,so.status,so.total_sistem,so.selisih,
            s.nama_shift,u.nama AS nama_kasir,
            COUNT(DISTINCT t.id_transaksi)::int AS trx_count,
            COALESCE(SUM(t.total_bayar),0)::bigint AS pendapatan_kotor,
            COALESCE(SUM(d.berat),0) AS total_kg
     FROM shift_operasional so
     JOIN shift s USING(id_shift)
     JOIN pengguna u ON u.id_pengguna=so.id_kasir
     LEFT JOIN transaksi t USING(id_shift_operasional)
     LEFT JOIN (
        SELECT id_transaksi,SUM(COALESCE(berat,0)) AS berat
        FROM detail_transaksi
        GROUP BY id_transaksi
     ) d USING(id_transaksi)
     GROUP BY so.id_shift_operasional,s.nama_shift,u.nama
     ORDER BY so.mulai_pada DESC LIMIT 30'
)) : [];

$queueWhere = $dataRole === 'pelanggan' ? ' AND t.id_pelanggan = ?' : '';
$queueParams = $dataRole === 'pelanggan' ? [(int)$_SESSION['customer_id']] : [];

$antrian = array_map(static function (array $row): array {
    $labels = ['antri'=>'Antri','proses'=>'Proses','siap_diambil'=>'Siap Diambil'];
    $status = $labels[$row['status_pengerjaan']] ?? ucfirst(str_replace('_',' ',$row['status_pengerjaan']));

    return [
        'nomor' => $row['nomor'],
        'pelanggan' => $row['nama_pelanggan'],
        'estimasi' => $row['estimasi'] ?: 'Menunggu estimasi',
        'status' => $status,
        'status_color' => $status === 'Siap Diambil' ? 'green' : ($status === 'Antri' ? 'yellow' : 'orange'),
        'layanan' => ($row['layanan'] ?: 'Laundry') . ' • ' . number_format((float)$row['berat'],1,',','.') . ' kg'
    ];
}, web_rows(
    'SELECT t.id_transaksi AS nomor,p.nama_pelanggan,t.status_pengerjaan,
            STRING_AGG(DISTINCT l.durasi, \', \') AS estimasi,
            COALESCE(SUM(d.berat),0) AS berat,
            STRING_AGG(DISTINCT l.nama_layanan,\', \') AS layanan
     FROM transaksi t
     JOIN pelanggan p USING(id_pelanggan)
     LEFT JOIN detail_transaksi d USING(id_transaksi)
     LEFT JOIN layanan l USING(id_layanan)
     WHERE t.status_pengerjaan <> \'diambil\'' . $queueWhere . '
     GROUP BY t.id_transaksi,p.nama_pelanggan
     ORDER BY t.tanggal_transaksi LIMIT 30',
    $queueParams
));
