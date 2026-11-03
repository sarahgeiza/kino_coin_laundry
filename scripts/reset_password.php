<?php
// CLI: php scripts/reset_password.php pemilik
if (PHP_SAPI!=='cli') {http_response_code(403);exit;}
require __DIR__.'/../backend/config/database.php';
$username=$argv[1]??'';
if ($username==='') {fwrite(STDERR,"Usage: php scripts/reset_password.php USERNAME\n");exit(1);}
echo "Password baru untuk $username: ";
$pass=trim(fgets(STDIN));
if(strlen($pass)<8){fwrite(STDERR,"Minimal 8 karakter\n");exit(1);}
$s=db()->prepare('UPDATE pengguna SET password=? WHERE username=?');
$s->execute([password_hash($pass,PASSWORD_DEFAULT),$username]);
echo $s->rowCount()?"Password berhasil diubah\n":"Username tidak ditemukan\n";
