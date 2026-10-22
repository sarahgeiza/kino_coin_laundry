<?php
// Controller modul transaksi
    if ($module==='transaksi') {
        if ($action==='list') {method('GET');$p=integer($_GET['page']??1);$limit=20;$offset=($p-1)*$limit;respond(['data'=>rows('SELECT t.*,p.nama_pelanggan,u.nama AS nama_kasir,s.nama_shift FROM transaksi t JOIN pelanggan p USING(id_pelanggan) JOIN pengguna u ON u.id_pengguna=t.id_kasir JOIN shift s USING(id_shift) ORDER BY t.id_transaksi DESC LIMIT 20 OFFSET ?',[$offset])]);}
        if ($action==='detail') {method('GET');$id=idparam();$t=one('SELECT t.*,p.nama_pelanggan,u.nama AS nama_kasir,s.nama_shift FROM transaksi t JOIN pelanggan p USING(id_pelanggan) JOIN pengguna u ON u.id_pengguna=t.id_kasir JOIN shift s USING(id_shift) WHERE t.id_transaksi=?',[$id]);if(!$t)respond(['error'=>'Transaksi tidak ditemukan'],404);$t['detail_transaksi']=rows('SELECT d.*,l.nama_layanan FROM detail_transaksi d JOIN layanan l USING(id_layanan) WHERE d.id_transaksi=? ORDER BY d.id_detail',[$id]);respond(['data'=>$t]);}
        if ($action==='create') {method('POST');csrf();if($u['role']!=='kasir')respond(['error'=>'Hanya kasir yang mencatat transaksi'],403);$b=body();need($b,['id_pelanggan','id_shift','jenis_pembayaran','detail_transaksi']);if(!in_array($b['jenis_pembayaran'],['cash','qris'],true))respond(['error'=>'Pembayaran tidak valid'],422);if(!is_array($b['detail_transaksi'])||count($b['detail_transaksi'])<1||count($b['detail_transaksi'])>30)respond(['error'=>'Detail harus 1–30 baris'],422);
            $pdo=db();$pdo->beginTransaction();try {
                $pel=integer($b['id_pelanggan']);$shift=integer($b['id_shift']);if(!one('SELECT 1 FROM pelanggan WHERE id_pelanggan=?',[$pel])||!one('SELECT 1 FROM shift WHERE id_shift=?',[$shift]))respond(['error'=>'Pelanggan atau shift tidak ditemukan'],422);
                $lines=[];$total=0;
                foreach($b['detail_transaksi'] as $d) {
                    if(!is_array($d))respond(['error'=>'Detail tidak valid'],422);need($d,['id_layanan','jumlah_koin']);$lid=integer($d['id_layanan']);$coin=integer($d['jumlah_koin'],0);$service=one('SELECT * FROM layanan WHERE id_layanan=?',[$lid]);if(!$service)respond(['error'=>'Layanan tidak ditemukan'],422);
                    $weight=isset($d['berat'])?positive_weight($d['berat']):null;
                    if($weight!==null && $service['berat_standar']!==null && (float)$weight>(float)$service['berat_standar']) respond(['error'=>'Berat per detail melebihi kapasitas layanan; pecah menjadi beberapa detail sesuai jumlah penggunaan mesin'],422);
                    // Satu baris = satu layanan. Kuantitas/kelipatan kapasitas harus disepakati dulu dengan mitra.
                    $price=(int)$service['harga'];$subtotal=$price;$total+=$subtotal;
                    if($total>2147483647)respond(['error'=>'Total melebihi batas'],422);
                    $lines[]=[$lid,$weight,$coin,$price,$subtotal];
                }
                $r=one('INSERT INTO transaksi(id_kasir,tanggal_transaksi,id_pelanggan,id_shift,jenis_pembayaran,total_bayar) VALUES(?,CURRENT_TIMESTAMP,?,?,?,?) RETURNING id_transaksi',[(int)$u['id_pengguna'],$pel,$shift,$b['jenis_pembayaran'],$total]);$id=(int)$r['id_transaksi'];
                foreach($lines as $line)execute_sql('INSERT INTO detail_transaksi(id_transaksi,id_layanan,berat,jumlah_koin,harga_satuan,subtotal) VALUES(?,?,?,?,?,?)',array_merge([$id],$line));
                $pdo->commit();respond(['message'=>'Transaksi tersimpan','id_transaksi'=>$id,'total_bayar'=>$total],201);
            } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
        }
    }
