<?php
declare(strict_types=1);
final class DaftarLowonganPage  {
    public static function bukaHalamanLowongan():void {
        self::klikCari();
    }
    public static function masukkanKataKunci(string $keyword):void {
        (new LowonganController())->cariLowongan($keyword);
    }
    public static function klikCari():void {
        self::masukkanKataKunci(query('keyword'));
    }
    public static function pilihTambahLowongan():void {
        (new LowonganController())->bukaFormLowongan(Auth::perusahaanId());
    }
    public static function tampilkanDaftar(array $daftarLowongan,string $pesan):void {
        View::render('lowongan/index',['title'=>'Lowongan','daftarLowongan'=>$daftarLowongan,'pesan'=>$pesan,'semuaLowongan'=>Auth::user()['role']==='Rekruter'?Lowongan::getDaftarLowongan(Auth::perusahaanId()):[]]);
    }
}
