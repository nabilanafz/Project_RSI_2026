<?php
declare(strict_types=1);
define('ROOT',dirname(__DIR__));
define('APP',require ROOT.'/config/app.php');
date_default_timezone_set(APP['timezone']);
spl_autoload_register(function(string $class):void {
    if(!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/',$class))return;
    foreach(['Core','Models','Controllers','Boundaries'] as $dir) {
        $file=ROOT.'/app/'.$dir.'/'.$class.'.php';
        if(is_file($file)) {
            require $file;
            return;
        }
    }
}
);
require ROOT.'/app/Core/helpers.php';
ini_set('display_errors','0');
ini_set('log_errors','1');
ini_set('error_log',ROOT.'/storage/logs/php.log');
session_save_path(ROOT.'/storage/sessions');
ini_set('session.use_strict_mode','1');
session_name('FILKOM_SESSION');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);
session_start();
$_SESSION['csrf']??=bin2hex(random_bytes(32));
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; img-src 'self' data:; script-src 'self'; connect-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
header('Cache-Control: no-store, private');
set_exception_handler(function(Throwable $e):void {
    error_log((string)$e);
    ErrorHandler::handle($e instanceof DomainException?422:500,$e instanceof DomainException?$e->getMessage():'Proses belum berhasil. Silakan coba kembali.');
});
