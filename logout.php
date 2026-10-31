<?php
session_start();
$_SESSION['username'] = null;
$_SESSION['role'] = null;
header('Location: index.php?logout=1');
exit;
