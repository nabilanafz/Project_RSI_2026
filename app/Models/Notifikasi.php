<?php
declare(strict_types=1);
final class Notifikasi  {
    public static function kirimNotifikasi(int $idPenerima,string $judul,string $isi,string $tautan):void {
        if(!$idPenerima)return;
        Database::query('INSERT INTO NOTIFIKASI(idPenerima,judul,isi,tautan) VALUES (?,?,?,?)',[$idPenerima,$judul,$isi,$tautan]);
    }
    public static function perusahaan(int $id,string $judul,string $isi,string $tautan):void {
        foreach(Akun::rekruter($id) as $a)self::kirimNotifikasi((int)$a['idAkun'],$judul,$isi,$tautan);
    }
    public static function daftar(int $id):array {
        return Database::all('SELECT * FROM NOTIFIKASI WHERE idPenerima=? ORDER BY dibuatPada DESC,idNotifikasi DESC LIMIT 150',[$id]);
    }
    public static function belumDibaca(int $id):int {
        return (int)Database::value('SELECT COUNT(*) FROM NOTIFIKASI WHERE idPenerima=? AND sudahDibaca=0',[$id]);
    }
}
