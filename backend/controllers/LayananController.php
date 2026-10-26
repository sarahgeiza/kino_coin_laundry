<?php
// Controller modul layanan
    if ($module==='layanan') {
        if ($action==='list') {method('GET');respond(['data'=>rows('SELECT * FROM layanan ORDER BY id_layanan')]);}
        if ($action==='detail') {method('GET');respond(['data'=>Layanan::find(idparam())]);}
        if ($action==='create'||$action==='update') {method('POST');csrf();$b=body();need($b,['nama_layanan','harga']);$harga=integer($b['harga'],0);$berat=isset($b['berat_standar'])?positive_weight($b['berat_standar']):null;
            if ($action==='create'){$r=one('INSERT INTO layanan(nama_layanan,harga,berat_standar) VALUES(?,?,?) RETURNING id_layanan',[strval_required($b['nama_layanan'],50),$harga,$berat]);respond(['data'=>$r],201);}
            need($b,['id_layanan']);execute_sql('UPDATE layanan SET nama_layanan=?,harga=?,berat_standar=? WHERE id_layanan=?',[strval_required($b['nama_layanan'],50),$harga,$berat,integer($b['id_layanan'])]);respond(['message'=>'Layanan diperbarui']);}
        if ($action==='delete') {method('POST');csrf();$b=body();need($b,['id_layanan']);$id=integer($b['id_layanan']);if(one('SELECT 1 FROM detail_transaksi WHERE id_layanan=? LIMIT 1',[$id]))respond(['error'=>'Layanan sudah dipakai transaksi; tidak dapat dihapus'],409);execute_sql('DELETE FROM layanan WHERE id_layanan=?',[$id]);respond(['message'=>'Layanan dihapus']);}
    }
