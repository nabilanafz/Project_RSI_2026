<?php
declare(strict_types=1);
final class Lowongan extends Entity  {
    private static function ambil(string $where,array $p=[]):array {
        return array_map(fn($r)=>new self($r),Database::all('SELECT l.*,p.namaPerusahaan,p.statusVerifikasi,p.bidangUsaha,p.alamat,p.website,p.deskripsi deskripsiPerusahaan,(SELECT COUNT(*) FROM LAMARAN a WHERE a.idLowongan=l.idLowongan AND a.statusLamaran<>\'Draf\') jumlahPendaftar FROM LOWONGAN l JOIN PERUSAHAAN p ON p.idPerusahaan=l.idPerusahaan WHERE '.$where.' ORDER BY l.tanggalDibuat DESC,l.idLowongan DESC',$p));
    }
    public static function getSemuaLowongan():array {
        return self::ambil("l.statusLowongan='Aktif' AND l.dihapusPada IS NULL AND p.dihapusPada IS NULL AND p.statusVerifikasi='Terverifikasi' AND (l.batasLamaran IS NULL OR l.batasLamaran>=CURRENT_DATE)");
    }
    public static function cariLowongan(string $keyword):array {
        return array_values(array_filter(self::getSemuaLowongan(),fn($l)=>mb_stripos($l['namaLowongan'].' '.$l['namaPerusahaan'].' '.$l['deskripsi'],$keyword)!==false));
    }
    public static function getDaftarLowongan(int $idPerusahaan):array {
        return self::ambil("l.idPerusahaan=? AND l.dihapusPada IS NULL AND l.statusLowongan<>'Draf'",[$idPerusahaan]);
    }
    public static function getLowonganById(int $idLowongan):?self {
        return self::ambil('l.idLowongan=?',[$idLowongan])[0]??null;
    }
    public function tersedia():bool {
        return $this['statusLowongan']==='Aktif'&&!$this['dihapusPada']&&$this['statusVerifikasi']==='Terverifikasi'&&(!$this['batasLamaran']||$this['batasLamaran']>=date('Y-m-d'));
    }
    public static function simpan(Lowongan $dataLowongan,int $idPerusahaan):int {
        $fields=['namaLowongan','jenisPekerjaan','modeKerja','lokasiPenempatan','tingkatSemesterMinimal','targetJurusan','deskripsi','dokumenPersyaratan','statusLowongan','batasLamaran','kuota','durasiBulan','uangSaku','konversiSKS','kualifikasi'];
        $v=[];
        foreach($fields as $f) {
            $value=$dataLowongan->get($f);
            $v[]=is_array($value)?json($value):$value;
        }
        $id=(int)$dataLowongan->get('idLowongan',0);
        if($id) {
            $set=implode(',',array_map(fn($f)=>"`$f`=?",$fields));
            Database::query("UPDATE LOWONGAN SET $set WHERE idLowongan=? AND idPerusahaan=?",[...$v,$id,$idPerusahaan]);
        }
        else {
            $cols=implode(',',$fields);
            $marks=implode(',',array_fill(0,count($fields)+1,'?'));
            Database::query("INSERT INTO LOWONGAN ($cols,idPerusahaan) VALUES ($marks)",[...$v,$idPerusahaan]);
            $id=(int)Database::get()->lastInsertId();
        }
        return $id;
    }
    public static function saring(array $rows,array $f):array {
        return array_values(array_filter($rows,function($l)use($f) {
            return (!$f['jenis']||$l['jenisPekerjaan']===$f['jenis'])&&(!$f['semester']||(int)$l['tingkatSemesterMinimal']<=(int)$f['semester'])&&(!$f['jurusan']||in_array($f['jurusan'],$l['targetJurusan'],true))&&(!$f['lokasi']||mb_stripos($l['lokasiPenempatan'],$f['lokasi'])!==false)&&(!$f['mode']||$l['modeKerja']===$f['mode'])&&(!$f['status']||$l['statusLowongan']===$f['status']);
        }
        ));
    }
}
