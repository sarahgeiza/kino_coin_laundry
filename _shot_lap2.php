<?php
session_start();
$_SESSION['user_id']=1; $_SESSION['username']='Pemilik Kino'; $_SESSION['role']='pemilik'; $_SESSION['csrf']='tok';
$_GET['periode'] = $_GET['p'] ?? 'Hari Ini';
require 'C:/PBL/admin/laporan.php';
