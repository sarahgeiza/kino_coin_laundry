<?php
// Controller modul dashboard_rekap
    if ($module==='dashboard'||$module==='rekap') {
        method('GET');if($module==='dashboard'&&$action==='pemilik')owner();
        $start=(string)($_GET['start']??date('Y-m-01'));$end=(string)($_GET['end']??date('Y-m-d'));
        foreach([$start,$end] as $date){$dt=DateTimeImmutable::createFromFormat('!Y-m-d',$date);if(!$dt||$dt->format('Y-m-d')!==$date)respond(['error'=>'Tanggal harus YYYY-MM-DD'],422);}
        if($start>$end)respond(['error'=>'Tanggal awal melebihi tanggal akhir'],422);
        $where=' WHERE t.tanggal_transaksi >= ?::date AND t.tanggal_transaksi < (?::date + INTERVAL \'1 day\')';$params=[$start,$end];
        $filters=['shift'=>'t.id_shift','kasir'=>'t.id_kasir','pembayaran'=>'t.jenis_pembayaran'];
        foreach($filters as $k=>$col)if(isset($_GET[$k])){$where.=" AND $col = ?";$params[]=$k==='pembayaran'?strval_required($_GET[$k],10):integer($_GET[$k]);}
        // Aggregasi detail terlebih dahulu agar total_bayar tidak terhitung berkali-kali saat satu transaksi punya banyak detail.
        $base=' FROM transaksi t LEFT JOIN (SELECT id_transaksi,SUM(COALESCE(berat,0)) berat_total,SUM(jumlah_koin) koin_total FROM detail_transaksi GROUP BY id_transaksi) d ON d.id_transaksi=t.id_transaksi ';
        if($module==='dashboard'||$action==='index'){
            $sql='SELECT COUNT(*) jumlah_transaksi,COALESCE(SUM(t.total_bayar),0) pendapatan,COALESCE(SUM(d.berat_total),0) total_berat,COALESCE(SUM(d.koin_total),0) total_koin'.$base.$where;
            respond(['data'=>one($sql,$params)]);
        }
        if($action==='layanan'){$sql='SELECT l.id_layanan,l.nama_layanan,COUNT(DISTINCT t.id_transaksi) jumlah_transaksi,COALESCE(SUM(d.berat),0) total_berat,COALESCE(SUM(d.jumlah_koin),0) total_koin,COALESCE(SUM(d.subtotal),0) pendapatan FROM transaksi t JOIN detail_transaksi d USING(id_transaksi) JOIN layanan l USING(id_layanan) '.$where.' GROUP BY l.id_layanan,l.nama_layanan ORDER BY l.id_layanan';respond(['data'=>rows($sql,$params)]);}
        if($action==='shift'||$action==='kasir'||$action==='periode'||$action==='pembayaran'){
            $group=match($action){'shift'=>['s.nama_shift',' JOIN shift s ON s.id_shift=t.id_shift '],'kasir'=>['u.nama',' JOIN pengguna u ON u.id_pengguna=t.id_kasir '],'periode'=>['t.tanggal_transaksi::date',''],'pembayaran'=>['t.jenis_pembayaran','']};
            $sql='SELECT '.$group[0].' AS kategori,COUNT(*) jumlah_transaksi,COALESCE(SUM(t.total_bayar),0) pendapatan,COALESCE(SUM(d.berat_total),0) total_berat,COALESCE(SUM(d.koin_total),0) total_koin'.$base.$group[1].$where.' GROUP BY '.$group[0].' ORDER BY '.$group[0];respond(['data'=>rows($sql,$params)]);
        }
        if($action==='koin'){respond(['data'=>one('SELECT COALESCE(SUM(d.koin_total),0) total_koin'.$base.$where,$params)]);}
    }
