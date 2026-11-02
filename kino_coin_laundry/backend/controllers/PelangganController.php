<?php
// Controller modul pelanggan
    if ($module==='pelanggan') {
        if ($action==='list') {method('GET');$q=trim((string)($_GET['q']??''));respond(['data'=>rows('SELECT * FROM pelanggan WHERE nama_pelanggan ILIKE ? OR no_hp ILIKE ? ORDER BY id_pelanggan DESC LIMIT 100',['%'.$q.'%','%'.$q.'%'])]);}
        if ($action==='detail') {method('GET');respond(['data'=>Pelanggan::find(idparam())]);}
        if ($action==='create') {method('POST');csrf();$b=body();need($b,['nama_pelanggan','no_hp']);$r=one('INSERT INTO pelanggan(nama_pelanggan,no_hp) VALUES(?,?) RETURNING id_pelanggan',[strval_required($b['nama_pelanggan']),strval_required($b['no_hp'],20)]);respond(['data'=>$r],201);}
        if ($action==='update') {
            method('POST'); csrf(); $b=body(); need($b,['id_pelanggan','nama_pelanggan','no_hp']);
            $phone = preg_replace('/[\\s()-]+/', '', strval_required($b['no_hp'],20));
            if (!preg_match('/^\\+?[0-9]{9,15}$/',$phone)) respond(['error'=>'Nomor WhatsApp tidak valid'],422);
            execute_sql('UPDATE pelanggan SET nama_pelanggan=?,no_hp=? WHERE id_pelanggan=?',[strval_required($b['nama_pelanggan'],100),$phone,integer($b['id_pelanggan'])]);
            respond(['message'=>'Pelanggan diperbarui']);
        }
        if ($action==='password') {
            method('POST'); csrf(); $b=body(); need($b,['id_pelanggan','password']);
            if (strlen((string)$b['password']) < 8) respond(['error'=>'Password minimal 8 karakter'],422);
            execute_sql('UPDATE pelanggan SET password_hash=? WHERE id_pelanggan=?',[password_hash((string)$b['password'],PASSWORD_DEFAULT),integer($b['id_pelanggan'])]);
            respond(['message'=>'Password pelanggan diperbarui']);
        }
    }
