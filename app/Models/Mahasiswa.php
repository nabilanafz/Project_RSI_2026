<?php
declare(strict_types=1);
final class Mahasiswa extends Entity  {
    public static function getMahasiswa(int $idMahasiswa):?self {
        $r=Database::one('SELECT * FROM MAHASISWA WHERE idMahasiswa=?',[$idMahasiswa]);
        return $r?new self($r):null;
    }
    public static function simpanProfil(int $id,array $d):void {
        Database::query('UPDATE MAHASISWA SET namaMahasiswa=?,email=?,jurusan=?,semester=?,IPK=?,telepon=?,portofolio=?,bio=?,idCV=? WHERE idMahasiswa=?',[$d['namaMahasiswa'],$d['email'],$d['jurusan'],$d['semester'],$d['IPK'],$d['telepon'],$d['portofolio'],$d['bio'],$d['idCV'],$id]);
        Database::query('UPDATE AKUN SET namaPengguna=?,email=? WHERE idMahasiswa=?',[$d['namaMahasiswa'],$d['email'],$id]);
    }
}
