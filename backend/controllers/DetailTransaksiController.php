<?php
// Controller modul detail-transaksi
    if ($module==='detail-transaksi') {
        if($action==='list'){method('GET');$id=integer($_GET['id_transaksi']??null);respond(['data'=>rows('SELECT d.*,l.nama_layanan FROM detail_transaksi d JOIN layanan l USING(id_layanan) WHERE d.id_transaksi=?',[$id])]);}
        if($action==='detail'){method('GET');respond(['data'=>one('SELECT d.*,l.nama_layanan FROM detail_transaksi d JOIN layanan l USING(id_layanan) WHERE d.id_detail=?',[idparam()])]);}
    }
