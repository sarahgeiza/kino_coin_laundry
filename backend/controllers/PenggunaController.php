<?php
// Controller modul pengguna
    if ($module==='pengguna') {
        if ($action==='list') {method('GET');owner();respond(['data'=>rows('SELECT id_pengguna,nama,username,role FROM pengguna ORDER BY id_pengguna')]);}
        if ($action==='detail') {method('GET');owner();respond(['data'=>one('SELECT id_pengguna,nama,username,role FROM pengguna WHERE id_pengguna=?',[idparam()])]);}
        if ($action==='create') {method('POST');csrf();$b=body();need($b,['nama','username','role','password']);if (!in_array($b['role'],['pemilik','kasir'],true)) respond(['error'=>'Role tidak valid'],422);if(strlen((string)$b['password'])<8)respond(['error'=>'Password minimal 8 karakter'],422);
            $r=one('INSERT INTO pengguna(nama,username,role,password) VALUES(?,?,?,?) RETURNING id_pengguna',[strval_required($b['nama']),strval_required($b['username'],50),$b['role'],password_hash((string)$b['password'],PASSWORD_DEFAULT)]);respond(['data'=>$r],201);}
        if ($action==='update') {method('POST');csrf();$b=body();need($b,['id_pengguna','nama','username','role']);if(!in_array($b['role'],['pemilik','kasir'],true))respond(['error'=>'Role tidak valid'],422);execute_sql('UPDATE pengguna SET nama=?,username=?,role=? WHERE id_pengguna=?',[strval_required($b['nama']),strval_required($b['username'],50),$b['role'],integer($b['id_pengguna'])]);respond(['message'=>'Pengguna diperbarui']);}
        if ($action==='password') {method('POST');csrf();$b=body();need($b,['id_pengguna','password']);if(strlen((string)$b['password'])<8)respond(['error'=>'Password minimal 8 karakter'],422);execute_sql('UPDATE pengguna SET password=? WHERE id_pengguna=?',[password_hash((string)$b['password'],PASSWORD_DEFAULT),integer($b['id_pengguna'])]);respond(['message'=>'Password diperbarui']);}
    }
