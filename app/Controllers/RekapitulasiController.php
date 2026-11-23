<?php
declare(strict_types=1);
final class RekapitulasiController  {
    private function data():array {
        $u=Auth::require(['Rekruter','Admin']);
        $where=$u['role']==='Rekruter'?'l.idPerusahaan=?':'1=1';
        $p=$u['role']==='Rekruter'?[$u['idPerusahaan']]:[];
        return Database::all("SELECT l.idLowongan,l.namaLowongan,p.namaPerusahaan,l.statusLowongan,l.dihapusPada,COUNT(a.idLamaran) total,SUM(CASE WHEN a.statusLamaran='Diterima' THEN 1 ELSE 0 END) diterima,SUM(CASE WHEN a.statusLamaran='Ditolak' THEN 1 ELSE 0 END) ditolak,SUM(CASE WHEN a.statusLamaran='Wawancara' THEN 1 ELSE 0 END) wawancara FROM LOWONGAN l JOIN PERUSAHAAN p ON p.idPerusahaan=l.idPerusahaan LEFT JOIN LAMARAN a ON a.idLowongan=l.idLowongan AND a.statusLamaran<>'Draf' WHERE $where AND l.statusLowongan<>'Draf' GROUP BY l.idLowongan,l.namaLowongan,p.namaPerusahaan,l.statusLowongan,l.dihapusPada ORDER BY l.idLowongan DESC",$p);
    }
    private function mahasiswa():array  {
        $u=Auth::require(['Rekruter','Admin']);
        $where=$u['role']==='Rekruter'?' AND l.idPerusahaan=?':'';
        return Database::all("SELECT a.idLamaran,a.statusLamaran,m.namaMahasiswa,m.NIM,m.jurusan,l.namaLowongan,p.namaPerusahaan FROM LAMARAN a JOIN MAHASISWA m ON m.idMahasiswa=a.idMahasiswa JOIN LOWONGAN l ON l.idLowongan=a.idLowongan JOIN PERUSAHAAN p ON p.idPerusahaan=l.idPerusahaan WHERE a.statusLamaran<>'Draf'".$where.' ORDER BY m.namaMahasiswa,a.idLamaran',$where?[$u['idPerusahaan']]:[]);
    }
    public function tampilkanRekapitulasi():void {
        View::render('rekapitulasi',['title'=>'Rekapitulasi','rekap'=>$this->data(),'mahasiswa'=>$this->mahasiswa()]);
    }
    public function eksporCSV():void {
        $rows=$this->data();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="rekap-lamaran.csv"');
        $f=fopen('php://output','w');
        fputs($f,"\xEF\xBB\xBF");
        fputcsv($f,['ID','Lowongan','Perusahaan','Status','Total','Diterima','Ditolak','Wawancara'],',','"','');
        foreach($rows as $r) {
            $a=[$r['idLowongan'],$r['namaLowongan'],$r['namaPerusahaan'],$r['dihapusPada']?'Dihapus':$r['statusLowongan'],$r['total'],$r['diterima'],$r['ditolak'],$r['wawancara']];
            $a=array_map(fn($v)=>preg_match('/^[=+@\-\t\r\n]/',(string)$v)?"'".$v:$v,$a);
            fputcsv($f,$a,',','"','');
        }
        fclose($f);
        exit;
    }
}
