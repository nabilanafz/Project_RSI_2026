<?php
declare(strict_types=1);
final class FormPendaftaranLowonganPage  {
    private static function render(Lowongan $lowongan,array $errors=[],string $pesan=''):void  {
        $state=LamaranController::unggahanSementara((int)$lowongan['idLowongan']);
        View::render('lamaran/form',['title'=>'Pendaftaran Lowongan','lowongan'=>$lowongan, 'mahasiswa'=>Mahasiswa::getMahasiswa(Auth::mahasiswaId()),'state'=>$state,'errors'=>$pesan?[$pesan,...$errors]:$errors,'pesan'=>$pesan, 'dokumenInfo'=>Dokumen::informasi($state['dokumenPersyaratan']), 'tahapan'=>TahapSeleksi::getTahapByLowongan((int)$lowongan['idLowongan'])]);
    }
    public static function tampilkanFormPendaftaran(Lowongan $lowongan):void {
        self::render($lowongan);
    }
    public static function tampilkanPesan(string $pesan,array $dokumenKurang):void  {
        $lowongan=Lowongan::getLowonganById(number('idLowongan'));
        if(!$lowongan)fail(404,'Lowongan tidak ditemukan.');
        self::render($lowongan,$dokumenKurang,$pesan);
    }
    public static function unggahDokumen(array $dokumen):void {
        (new LamaranController())->prosesForm();
    }
    public static function kirimLamaran():void {
        (new LamaranController())->prosesForm();
    }
    public static function batalkan():void {
        go('lowongan/detail',['id'=>number('idLowongan')]);
    }
}
