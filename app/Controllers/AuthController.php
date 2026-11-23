<?php
declare(strict_types=1);
final class AuthController  {
    public function tampilkanLogin():void {
        if(Auth::user())go('dashboard');
        View::render('auth/login',['title'=>'Masuk','error'=>'']);
    }
    public function login():void {
        $username=post('username');
        $password=isset($_POST['password'])&&is_string($_POST['password'])?$_POST['password']:'';
        $key=hash('sha256',mb_strtolower($username).'|'.($_SERVER['REMOTE_ADDR']??''));
        $attempt=Database::one('SELECT * FROM LOGIN_PERCOBAAN WHERE kunci=?',[$key]);
        if($attempt && strtotime($attempt['mulai'])<time()-900) {
            Database::query('DELETE FROM LOGIN_PERCOBAAN WHERE kunci=?',[$key]);
            $attempt=null;
        }
        $error='';
        if(($attempt['jumlah']??0)>=10)$error='Terlalu banyak percobaan. Coba kembali dalam 15 menit.';
        if(!$error) {
            $u=Akun::cariAkun($username);
            $dummy='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
            $valid=password_verify($password,$u['passwordHash']??$dummy);
            if(!$u||!$valid||!$u['aktif']) {
                $error='Username/email atau kata sandi salah, atau akun tidak aktif.';
                Database::query('INSERT INTO LOGIN_PERCOBAAN(kunci,jumlah,mulai) VALUES (?,1,NOW()) ON DUPLICATE KEY UPDATE jumlah=jumlah+1',[$key]);
            }
            elseif($u['idPerusahaan']&&Perusahaan::getPerusahaan((int)$u['idPerusahaan'])['dihapusPada'])$error='Akun perusahaan tidak aktif.';
            else {
                Database::query('DELETE FROM LOGIN_PERCOBAAN WHERE kunci=?',[$key]);
                Auth::login($u);
                go('dashboard');
            }
        }
        http_response_code(422);
        View::render('auth/login',['title'=>'Masuk','error'=>$error,'username'=>$username]);
    }
    public function logout():void {
        Auth::logout();
        flash('Anda telah keluar.','info');
        go('login');
    }
}
