<?php
declare(strict_types=1);
final class TahapSeleksi extends Entity  {
    public static function getTahapByLowongan(int $idLowongan):array {
        return array_map(fn($r)=>new self($r),Database::all('SELECT * FROM `TAHAP SELEKSI` WHERE idLowongan=? ORDER BY urutan',[$idLowongan]));
    }
    public static function simpanRoadmap(int $idLowongan,array $daftarTahap):void {
        // Rekonstruksi hanya diizinkan sebelum ada lamaran terkirim. Draf tidak mempunyai FK tahap.
        if((int)Database::value("SELECT COUNT(*) FROM LAMARAN WHERE idLowongan=? AND statusLamaran<>'Draf'",[$idLowongan])>0)return;
        Database::query('DELETE FROM `TAHAP SELEKSI` WHERE idLowongan=?',[$idLowongan]);
        foreach(array_values($daftarTahap) as $i=>$t)Database::query('INSERT INTO `TAHAP SELEKSI` (urutan,namaTahap,keterangan,idLowongan) VALUES (?,?,?,?)',[$i+1,$t['namaTahap'],$t['keterangan']??'',$idLowongan]);
    }
}
