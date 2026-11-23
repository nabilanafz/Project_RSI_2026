<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') {
    http_response_code(404);
    exit;
}
fwrite(STDERR,"Masukkan kata sandi untuk dibuat hash (input terminal terlihat): ");
$password=rtrim((string)fgets(STDIN),"\r\n");
if(strlen($password)<10) {
    fwrite(STDERR,"Gunakan minimal 10 karakter.\n");
    exit(1);
}
echo password_hash($password,PASSWORD_DEFAULT).PHP_EOL;
