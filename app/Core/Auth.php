<?php
declare(strict_types=1);
final class Auth  {
    private static array|false|null $cached=null;
    public static function user():?array {
        if(self::$cached===null) {
            self::$cached=isset($_SESSION['idAkun'])?Akun::getAkun((int)$_SESSION['idAkun']):false;
            if(self::$cached && (!self::$cached['aktif'] || (self::$cached['dihapusPada']??null))) self::$cached=false;
        }
        return self::$cached?:null;
    }
    public static function require(array $roles=[]):array {
        $u=self::user();
        if(!$u||time()-($_SESSION['aktivitasTerakhir']??0)>APP['session_timeout'])  {
            self::logout();
            flash('Silakan masuk untuk melanjutkan.','info');
            go('login');
        }
        $_SESSION['aktivitasTerakhir']=time();
        if($roles&&!in_array($u['role'],$roles,true))fail(403,'Anda tidak memiliki akses ke halaman ini.');
        return $u;
    }
    public static function login(array $u):void {
        session_regenerate_id(true);
        $_SESSION=['idAkun'=>(int)$u['idAkun'],'csrf'=>bin2hex(random_bytes(32)),'aktivitasTerakhir'=>time()];
        self::$cached=null;
    }
    public static function logout():void {
        $_SESSION=[];
        session_regenerate_id(true);
        $_SESSION['csrf']=bin2hex(random_bytes(32));
        self::$cached=false;
    }
    public static function mahasiswaId():int {
        return (int)(self::require(['Mahasiswa'])['idMahasiswa']);
    }
    public static function perusahaanId():int {
        return (int)(self::require(['Rekruter'])['idPerusahaan']);
    }
    public static function verified():int {
        $u=self::require(['Rekruter']);
        if($u['statusVerifikasi']!=='Terverifikasi')fail(403,'Perusahaan harus disetujui admin sebelum mempublikasikan lowongan atau memproses seleksi.');
        return (int)$u['idPerusahaan'];
    }
}
