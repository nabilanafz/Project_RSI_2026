<?php
declare(strict_types=1);
final class RiwayatLamaranPage  {
    public static function bukaRiwayatLamaran():void {
        (new LamaranController())->getRiwayatLamaran(Auth::mahasiswaId());
    }
    public static function pilihDetailLamaran(int $idLamaran):void {
        (new LamaranController())->getDetailLamaran($idLamaran);
    }
    public static function tampilkanDaftarLamaran(array $daftarLamaran,string $pesan):void  {
        $jadwal=[];
        $timeline=[];
        foreach($daftarLamaran as $a) {
            $id=(int)$a['idLamaran'];
            $jadwal[$id]=Kalender::jadwalLamaran($id);
            $timeline[$id]=['tahapan'=>TahapSeleksi::getTahapByLowongan((int)$a['idLowongan']),'hasil'=>Lamaran::hasil($id)];
        }
        View::render('lamaran/index',['title'=>'Lamaran Saya','daftarLamaran'=>$daftarLamaran,'pesan'=>$pesan, 'semuaLamaran'=>Lamaran::getLamaranByMahasiswa(Auth::mahasiswaId()),'jadwalLamaran'=>$jadwal,'timelineData'=>$timeline]);
    }
}
