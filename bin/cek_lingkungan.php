<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') {
    http_response_code(404);
    exit;
}
$root=dirname(__DIR__);
require $root.'/app/bootstrap.php';
$ok=version_compare(PHP_VERSION,'8.2','>=');
echo 'PHP '.PHP_VERSION.': '.($ok?'OK':'perlu 8.2+').PHP_EOL;
foreach(['pdo_mysql','mbstring','fileinfo','session'] as $ext) {
    $pass=extension_loaded($ext);
    $ok=$ok&&$pass;
    echo "$ext: ".($pass?'OK':'BELUM AKTIF').PHP_EOL;
}
foreach(['uploads','logs','sessions'] as $dir) {
    $pass=is_writable($root.'/storage/'.$dir);
    $ok=$ok&&$pass;
    echo "storage/$dir: ".($pass?'writable':'PERIKSA IZIN TULIS').PHP_EOL;
}
try {
    echo 'Database: '.Database::value('SELECT VERSION()').PHP_EOL;
    echo 'Akun aktif: '.Database::value('SELECT COUNT(*) FROM AKUN WHERE aktif=1').PHP_EOL;
}
catch(Throwable $e) {
    $ok=false;
    fwrite(STDERR,"Database belum siap; periksa konfigurasi dan impor SQL.\n");
}
exit($ok?0:1);
