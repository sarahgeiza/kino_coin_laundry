<?php
function validate_date(string $date): void {
    $d=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
    if (!$d || $d->format('Y-m-d')!==$date) respond(['error'=>'Format tanggal YYYY-MM-DD'],422);
}
