<?php
declare(strict_types=1);
final class FormPembuatanLowonganPage  {
    private static function terkunci():bool {
        return (bool)Database::value("SELECT 1 FROM LAMARAN WHERE idLowongan=? AND statusLamaran<>'Draf' LIMIT 1",[(int)($_SESSION['lowongan_form']['idLowongan']??0)]);
    }
    public static function tampilkanForm(Perusahaan $dataPerusahaan):void {
        View::render('lowongan/form',['title'=>'Buat Lowongan Baru','data'=>$_SESSION['lowongan_form']??[],'errors'=>[],'roadmapTerkunci'=>self::terkunci(),'perusahaan'=>$dataPerusahaan]);
    }
    public static function tampilkanFormPerusahaan():void {
        self::tampilkanForm(Perusahaan::getPerusahaan(Auth::perusahaanId()));
    }
    public static function tampilkanErrorKolom(array $daftarError):void {
        View::render('lowongan/form',['title'=>'Periksa Data Lowongan','data'=>$_SESSION['lowongan_form']??[],'errors'=>$daftarError,'roadmapTerkunci'=>self::terkunci(),'perusahaan'=>Perusahaan::getPerusahaan(Auth::perusahaanId())]);
    }
    public static function tampilkanKonfirmasiBatal():void {
        View::render('lowongan/form',['title'=>'Batalkan Pembuatan Lowongan?','data'=>$_SESSION['lowongan_form']??[],'errors'=>[],'roadmapTerkunci'=>self::terkunci(),'perusahaan'=>Perusahaan::getPerusahaan(Auth::perusahaanId()),'confirmCancel'=>true]);
    }
    public static function isiFormulir(Lowongan $dataLowongan,array $daftarTahap):void {
        $_SESSION['lowongan_form']=$dataLowongan->toArray()+['daftarTahap'=>array_map(fn($t)=>$t->toArray(),$daftarTahap)];
    }
    public static function publikasikan():void {
        (new LowonganController())->prosesForm();
    }
    public static function batalkan():void {
        self::tampilkanKonfirmasiBatal();
    }
    public static function pilihYaBatalkan():void {
        (new LowonganController())->batalkanPembuatan();
    }
    public static function kembaliMengedit():void {
        self::tampilkanForm(Perusahaan::getPerusahaan(Auth::perusahaanId()));
    }
}
