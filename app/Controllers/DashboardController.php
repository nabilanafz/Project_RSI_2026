<?php
declare(strict_types=1);
final class DashboardController  {
    public function index():void {
        $u=Auth::require();
        $stats=[];
        if($u['role']==='Mahasiswa') {
            $rows=Lamaran::getLamaranByMahasiswa((int)$u['idMahasiswa']);
            $stats=['Lamaran terkirim'=>count(array_filter($rows,fn($a)=>$a['statusLamaran']!=='Draf')),'Dalam seleksi'=>count(array_filter($rows,fn($a)=>in_array($a['statusLamaran'],['Diproses','Wawancara','Lamaran Terkirim'],true))),'Diterima'=>count(array_filter($rows,fn($a)=>$a['statusLamaran']==='Diterima')),'Lowongan tersedia'=>count(Lowongan::getSemuaLowongan())];
        }
        elseif($u['role']==='Rekruter') {
            $jobs=Lowongan::getDaftarLowongan((int)$u['idPerusahaan']);
            $rows=Lamaran::daftarPendaftar((int)$u['idPerusahaan']);
            $stats=['Lowongan aktif'=>count(array_filter($jobs,fn($l)=>$l['statusLowongan']==='Aktif')),'Total pendaftar'=>count($rows),'Wawancara'=>count(array_filter($rows,fn($a)=>$a['statusLamaran']==='Wawancara')),'Diterima'=>count(array_filter($rows,fn($a)=>$a['statusLamaran']==='Diterima'))];
        }
        else {
            $stats=['Perusahaan aktif'=>(int)Database::value('SELECT COUNT(*) FROM PERUSAHAAN WHERE dihapusPada IS NULL'),'Menunggu verifikasi'=>(int)Database::value("SELECT COUNT(*) FROM PERUSAHAAN WHERE statusVerifikasi='Menunggu' AND dihapusPada IS NULL"),'Lowongan aktif'=>count(Lowongan::getSemuaLowongan()),'Lamaran terkirim'=>(int)Database::value("SELECT COUNT(*) FROM LAMARAN WHERE statusLamaran<>'Draf'")];
        }
        View::render('dashboard',['title'=>'Beranda','stats'=>$stats,'notifikasi'=>array_slice(Notifikasi::daftar((int)$u['idAkun']),0,4),'jadwal'=>array_slice(Kalender::jadwalPengguna($u),0,3)]);
    }
}
