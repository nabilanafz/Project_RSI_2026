<?php
declare(strict_types=1);
final class Akun  {
    public static function getAkun(int $id):?array  {
        return Database::one('SELECT a.*,p.statusVerifikasi,p.dihapusPada,p.namaPerusahaan FROM AKUN a LEFT JOIN PERUSAHAAN p ON p.idPerusahaan=a.idPerusahaan WHERE a.idAkun=?',[$id]);
    }
    public static function cariAkun(string $username):?array {
        return Database::one('SELECT * FROM AKUN WHERE username=? OR email=? LIMIT 1',[$username,$username]);
    }
    public static function mahasiswa(int $id):int {
        return (int)Database::value('SELECT idAkun FROM AKUN WHERE idMahasiswa=?',[$id]);
    }
    public static function rekruter(int $id):array {
        return Database::all("SELECT idAkun FROM AKUN WHERE idPerusahaan=? AND aktif=1",[$id]);
    }
}
