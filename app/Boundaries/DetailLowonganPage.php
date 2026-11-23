<?php
declare(strict_types=1);
final class DetailLowonganPage  {
    public static function bukaDetailLowongan(int $idLowongan):void {
        (new LowonganController())->getDetailLowongan($idLowongan);
    }
    public static function pilihDaftar():void {
        (new LamaranController())->bukaFormLamaran(number('id'));
    }
    public static function tampilkanDetail(Lowongan $lowongan,string $pesan):void {
        View::render('lowongan/detail',['title'=>$lowongan['namaLowongan'],'lowongan'=>$lowongan,'pesan'=>$pesan,'lamaranSaya'=>Auth::user()['role']==='Mahasiswa'?Database::one("SELECT idLamaran FROM LAMARAN WHERE idMahasiswa=? AND idLowongan=? AND statusLamaran<>'Draf'",[Auth::mahasiswaId(),$lowongan['idLowongan']]):null,'tahapan'=>TahapSeleksi::getTahapByLowongan((int)$lowongan['idLowongan'])]);
    }
}
