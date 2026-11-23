<?php
declare(strict_types=1);
final class ErrorHandler {
    public static function handle(int $status, string $message): never {
        $route=query('r','dashboard');
        $target=self::target($route);
        if($_SERVER['REQUEST_METHOD']==='POST' && $target && !isset($_SERVER['HTTP_X_RSI_FRAGMENT'])) {
            $values=$_POST;
            foreach(['password','csrf'] as $key)unset($values[$key]);
            $_SESSION['form_values'][$target[0].'|'.($target[1]['id']??0)]=$values;
            flash($message,'error');
            go($target[0],$target[1]);
        }
        http_response_code($status);
        // A previously authorized review remains readable even during a database failure.
        if($route==='perusahaan/detail') {
            $cache=$_SESSION['company_review'][number('id')]??null;
            if($cache && ($cache['user']['idAkun']??0)===($_SESSION['idAkun']??-1)) {
                $data=$cache['data'];
                $data['perusahaan']=new Perusahaan($data['perusahaan']);
                $data['lowongan']=array_map(fn($x)=>new Lowongan($x),$data['lowongan']);
                $data['_viewUser']=$cache['user'];$data['_skipDatabase']=true;
                $data['errors']=[$message];$data['offline']=true;
                View::render('perusahaan/detail',$data);exit;
            }
        }
        try {
            View::render('error',['title'=>'Permintaan tidak dapat diproses','error'=>$message,'message'=>$message]);
        } catch(Throwable $e) {
            error_log((string)$e);
            $errors=[$message];
            echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Permintaan belum selesai</title><link rel="stylesheet" href="assets/app.css"><script src="assets/app.js" defer></script></head><body><main class="standalone card"><h1>Permintaan belum selesai</h1><a class="button" href="index.php">Kembali ke aplikasi</a></main>';
            require ROOT.'/app/Views/components/error-modal.php';
            echo '</body></html>';
        }
        exit;
    }
    private static function target(string $route):?array {
        return match($route) {
            'perusahaan/keputusan','perusahaan/dokumen','perusahaan/verifikasi','perusahaan/hapus'=>['perusahaan/detail',['id'=>number('idPerusahaan')]],
            'seleksi/status','seleksi/jadwal'=>['seleksi/detail',['id'=>number('idLamaran')]],
            'lamaran/kirim'=>['lamaran/form',['id'=>number('idLowongan')]],
            'lowongan/simpan'=>['lowongan/form',['id'=>number('idLowongan')]],
            'lowongan/status'=>['lowongan',[]],
            'profil/simpan','profil/legalitas'=>['profil',[]],
            'chat/kirim'=>['chat',['id'=>number('idLamaran')]],
            'kalender/simpan','kalender/hapus'=>['kalender',[]],
            'notifikasi/baca'=>['notifikasi',[]],
            'login/proses'=>['login',[]],
            default=>null
        };
    }
}
