<?php
declare(strict_types=1);
final class Lamaran extends Entity  {
    public static function cekSudahMelamar(int $idMahasiswa,int $idLowongan):bool {
        return (bool)Database::value("SELECT 1 FROM LAMARAN WHERE idMahasiswa=? AND idLowongan=? AND statusLamaran<>'Draf'",[$idMahasiswa,$idLowongan]);
    }
    public static function getLamaranByMahasiswa(int $idMahasiswa):array {
        return self::ambil("a.idMahasiswa=? AND a.statusLamaran<>'Draf'",[$idMahasiswa]);
    }
    public static function getLamaranById(int $id):?self {
        return self::ambil('a.idLamaran=?',[$id])[0]??null;
    }
    public static function daftarPendaftar(int $idPerusahaan,int $idLowongan=0):array {
        $w="l.idPerusahaan=? AND a.statusLamaran<>'Draf'";
        $p=[$idPerusahaan];
        if($idLowongan) {
            $w.=' AND l.idLowongan=?';
            $p[]=$idLowongan;
        }
        return self::ambil($w,$p);
    }
    private static function ambil(string $w,array $p=[]):array {
        return array_map(fn($r)=>new self($r),Database::all('SELECT a.*,l.namaLowongan,l.idPerusahaan,l.lokasiPenempatan,l.jenisPekerjaan,l.modeKerja,l.statusLowongan,l.dihapusPada lowonganDihapus,p.namaPerusahaan,p.dihapusPada perusahaanDihapus,m.namaMahasiswa,m.NIM,m.jurusan,m.semester,m.IPK,m.email,m.telepon FROM LAMARAN a JOIN LOWONGAN l ON l.idLowongan=a.idLowongan JOIN PERUSAHAAN p ON p.idPerusahaan=l.idPerusahaan JOIN MAHASISWA m ON m.idMahasiswa=a.idMahasiswa WHERE '.$w.' ORDER BY COALESCE(a.tanggalMelamar,a.dibuatPada) DESC,a.idLamaran DESC',$p));
    }
    public static function simpanLamaran(Lamaran $lamaran):bool {
        $d=$lamaran->toArray();
        $v=[json($d['dokumenPersyaratan']),$d['statusLamaran'],$d['tanggalMelamar']??null,$d['idTahapSaatIni']??null,$d['portofolio']??'',$d['suratPengantar']??''];
        if(!empty($d['idLamaran']))Database::query('UPDATE LAMARAN SET dokumenPersyaratan=?,statusLamaran=?,tanggalMelamar=?,idTahapSaatIni=?,portofolio=?,suratPengantar=? WHERE idLamaran=? AND idMahasiswa=?',[...$v,$d['idLamaran'],$d['idMahasiswa']]);
        else {
            Database::query('INSERT INTO LAMARAN(dokumenPersyaratan,statusLamaran,tanggalMelamar,idTahapSaatIni,portofolio,suratPengantar,idLowongan,idMahasiswa) VALUES (?,?,?,?,?,?,?,?)',[...$v,$d['idLowongan'],$d['idMahasiswa']]);
            $lamaran->data['idLamaran']=(int)Database::get()->lastInsertId();
        }
        return true;
    }
    public static function hasil(int $id):array {
        $r=Database::all('SELECT h.*,t.namaTahap,t.urutan FROM HASIL_SELEKSI h JOIN `TAHAP SELEKSI` t ON t.idTahap=h.idTahap WHERE h.idLamaran=? ORDER BY t.urutan',[$id]);
        $out=[];
        foreach($r as $x)$out[$x['idTahap']]=$x;
        return $out;
    }
    public static function izin(int $id):self {
        $u=Auth::require();
        $a=self::getLamaranById($id);
        if(!$a||$a['statusLamaran']==='Draf')fail(404,'Lamaran tidak ditemukan.');
        if($u['role']==='Mahasiswa'&&(int)$u['idMahasiswa']===(int)$a['idMahasiswa'])return $a;
        if($u['role']==='Rekruter'&&(int)$u['idPerusahaan']===(int)$a['idPerusahaan']&&$a['statusLamaran']!=='Draf')return $a;
        if($u['role']==='Admin'&&$a['statusLamaran']!=='Draf')return $a;
        fail(403,'Lamaran ini bukan milik akun atau perusahaan Anda.');
    }
}
