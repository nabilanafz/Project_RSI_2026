<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__).'/app/bootstrap.php';
try  {
    $jumlah=Kalender::kirimPengingatJatuhTempo();
    echo date('Y-m-d H:i:s')." WIB: $jumlah pengingat dikirim.\n";
}
catch(Throwable $e)  {
    error_log((string)$e);
    fwrite(STDERR,"Pengiriman pengingat gagal. Periksa log.\n");
    exit(1);
}
