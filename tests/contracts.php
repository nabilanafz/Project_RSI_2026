<?php
declare(strict_types=1);
// Kontrak nama method dari tabel kelas Modul 2; tidak mengubah database.
require dirname(__DIR__).'/app/bootstrap.php';
$contracts=[
 'DaftarLowonganPage'=>['bukaHalamanLowongan','klikCari','masukkanKataKunci','pilihTambahLowongan','tampilkanDaftar'],
 'FormPembuatanLowonganPage'=>['batalkan','isiFormulir','kembaliMengedit','pilihYaBatalkan','publikasikan','tampilkanErrorKolom','tampilkanForm','tampilkanKonfirmasiBatal'],
 'DetailLowonganPage'=>['bukaDetailLowongan','pilihDaftar','tampilkanDetail'],
 'RiwayatLamaranPage'=>['bukaRiwayatLamaran','pilihDetailLamaran','tampilkanDaftarLamaran'],
 'FormPendaftaranLowonganPage'=>['batalkan','kirimLamaran','tampilkanFormPendaftaran','tampilkanPesan','unggahDokumen'],
 'LowonganController'=>['batalkanPembuatan','buatLowongan','bukaFormLowongan','cariLowongan','getDetailLowongan','validasiData'],
 'LamaranController'=>['bukaFormLamaran','cekStatusMelamar','getRiwayatLamaran','mendaftarLowongan','validasiLamaran'],
 'Lowongan'=>['cariLowongan','getDaftarLowongan','getLowonganById','getSemuaLowongan','simpan'],
 'Lamaran'=>['cekSudahMelamar','getLamaranByMahasiswa','simpanLamaran'],
 'TahapSeleksi'=>['getTahapByLowongan','simpanRoadmap'],
 'Perusahaan'=>['getPerusahaan'], 'Mahasiswa'=>['getMahasiswa'],
];
$pass=0;$fail=0;
foreach($contracts as $class=>$methods){
 foreach($methods as $method){
  $ok=method_exists($class,$method);
  if($ok&&in_array($method,['validasiData','validasiLamaran'],true))$ok=(new ReflectionMethod($class,$method))->isPrivate();
  if($ok)$pass++;else{$fail++;echo "FAIL $class::$method\n";}
 }
}
echo "Kontrak Modul 2: $pass lulus, $fail gagal.\n";
exit($fail?1:0);
