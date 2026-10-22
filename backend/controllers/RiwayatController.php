<?php
// Akses pelanggan: kasir/pemilik membuat kode acak, hanya hash yang disimpan.
if ($module==='riwayat') {
    if ($action==='buat-akses') {
        method('POST'); actor(); csrf(); $b=body();need($b,['id_pelanggan']);
        $id=integer($b['id_pelanggan']);
        if (!Pelanggan::find($id)) respond(['error'=>'Pelanggan tidak ditemukan'],404);
        $code=strtoupper(bin2hex(random_bytes(6)));
        execute_sql('INSERT INTO akses_pelanggan(id_pelanggan,kode_hash,berlaku_sampai) VALUES(?,?,CURRENT_TIMESTAMP + INTERVAL \'30 days\') ON CONFLICT(id_pelanggan) DO UPDATE SET kode_hash=EXCLUDED.kode_hash,berlaku_sampai=EXCLUDED.berlaku_sampai',[$id,password_hash($code,PASSWORD_DEFAULT)]);
        respond(['message'=>'Kode akses baru dibuat. Sampaikan secara pribadi kepada pelanggan','kode_akses'=>$code]);
    }
    if ($action==='login') {
        method('POST'); $b=body();need($b,['no_hp','kode_akses']);
        $attempt=$_SESSION['customer_attempt']??['count'=>0,'until'=>0];
        if ($attempt['until']>time()) respond(['error'=>'Terlalu banyak percobaan'],429);
        $records=rows('SELECT p.id_pelanggan,a.kode_hash FROM pelanggan p JOIN akses_pelanggan a USING(id_pelanggan) WHERE p.no_hp=? AND a.berlaku_sampai>CURRENT_TIMESTAMP',[(string)$b['no_hp']]);
        $matched=null;
        foreach($records as $r) if(password_verify((string)$b['kode_akses'],$r['kode_hash'])) {$matched=$r;break;}
        if(!$matched){$count=(int)$attempt['count']+1;$_SESSION['customer_attempt']=['count'=>$count,'until'=>$count>=5?time()+300:0];respond(['error'=>'Nomor HP atau kode akses tidak valid'],401);}
        session_regenerate_id(true);$_SESSION=[];$_SESSION['customer_id']=(int)$matched['id_pelanggan'];$_SESSION['csrf']=bin2hex(random_bytes(32));
        respond(['message'=>'Login pelanggan berhasil','csrf_token'=>$_SESSION['csrf']]);
    }
    if ($action==='logout') {method('POST');customer_actor();csrf();$_SESSION=[];session_destroy();respond(['message'=>'Logout berhasil']);}
    if ($action==='list') {method('GET');$id=customer_actor();respond(['data'=>rows('SELECT id_transaksi,tanggal_transaksi,jenis_pembayaran,total_bayar FROM transaksi WHERE id_pelanggan=? ORDER BY id_transaksi DESC',[$id])]);}
    if ($action==='detail') {method('GET');$cid=customer_actor();$id=idparam();$t=one('SELECT id_transaksi,tanggal_transaksi,jenis_pembayaran,total_bayar FROM transaksi WHERE id_pelanggan=? AND id_transaksi=?',[$cid,$id]);if(!$t)respond(['error'=>'Transaksi tidak ditemukan'],404);$t['detail_transaksi']=rows('SELECT l.nama_layanan,d.berat,d.jumlah_koin,d.harga_satuan,d.subtotal FROM detail_transaksi d JOIN layanan l USING(id_layanan) WHERE d.id_transaksi=?',[$id]);respond(['data'=>$t]);}
    respond(['error'=>'Action tidak ditemukan'],404);
}
