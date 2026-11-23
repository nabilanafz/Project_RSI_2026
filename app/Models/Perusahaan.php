<?php
declare(strict_types=1);
final class Perusahaan extends Entity  {
    public static function getPerusahaan(int $idPerusahaan):?self {
        $r=Database::one('SELECT * FROM PERUSAHAAN WHERE idPerusahaan=?',[$idPerusahaan]);
        return $r?new self($r):null;
    }
    public static function getDaftarPerusahaan(string $status=''):array {
        $w='dihapusPada IS NULL';
        $p=[];
        if($status!=='') {
            $w.=' AND statusVerifikasi=?';
            $p[]=$status;
        }
        return Database::all("SELECT p.*,(SELECT COUNT(*) FROM LOWONGAN l WHERE l.idPerusahaan=p.idPerusahaan AND l.dihapusPada IS NULL) jumlahLowongan FROM PERUSAHAAN p WHERE $w ORDER BY FIELD(statusVerifikasi,'Menunggu','Ditolak','Terverifikasi'),dibuatPada DESC",$p);
    }
    public static function dokumen(int $id):array {
        return Database::all('SELECT dp.*,d.namaAsli,d.ukuran FROM DOKUMEN_PERUSAHAAN dp JOIN DOKUMEN d ON d.idDokumen=dp.idDokumen WHERE dp.idPerusahaan=? ORDER BY dp.id',[$id]);
    }
    public static function simpanProfil(int $id,array $d):void {
        Database::query('UPDATE PERUSAHAAN SET namaPerusahaan=?,emailPerusahaan=?,alamat=?,bidangUsaha=?,kontak=?,deskripsi=?,website=? WHERE idPerusahaan=?',[$d['namaPerusahaan'],$d['emailPerusahaan'],$d['alamat'],$d['bidangUsaha'],$d['kontak'],$d['deskripsi'],$d['website'],$id]);
    }
}
