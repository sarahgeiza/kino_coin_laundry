<?php
// Controller modul shift
    if ($module==='shift') {
        if ($action==='list') {method('GET');respond(['data'=>rows('SELECT * FROM shift ORDER BY id_shift')]);}
        if ($action==='detail') {method('GET');respond(['data'=>Shift::find(idparam())]);}
        if ($action==='create'||$action==='update') {method('POST');csrf();$b=body();need($b,['nama_shift','jam_mulai','jam_selesai']);foreach(['jam_mulai','jam_selesai'] as $k)if(!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/',(string)$b[$k]))respond(['error'=>"Format $k harus HH:MM atau HH:MM:SS"],422);
            if($action==='create'){$r=one('INSERT INTO shift(nama_shift,jam_mulai,jam_selesai) VALUES(?,?,?) RETURNING id_shift',[strval_required($b['nama_shift'],20),$b['jam_mulai'],$b['jam_selesai']]);respond(['data'=>$r],201);}
            need($b,['id_shift']);execute_sql('UPDATE shift SET nama_shift=?,jam_mulai=?,jam_selesai=? WHERE id_shift=?',[strval_required($b['nama_shift'],20),$b['jam_mulai'],$b['jam_selesai'],integer($b['id_shift'])]);respond(['message'=>'Shift diperbarui']);}
    }
