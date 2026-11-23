# Pemetaan Modul 1, Modul 2, wireframe, dan implementasi

Sumber: laporan Kelompok 5 Modul 1, laporan Kelompok 5 Modul 2 (65 halaman), serta 18 gambar pada `Wireframe RSI.zip`. Modul 3 adalah contoh susunan laporan; implementasi menggunakan kebutuhan proyek pada Modul 1–2. Daftar use case utama Modul 2 halaman 2–3 menjadi acuan nomor UC.

Revisi 23 November 2026 mengikuti daftar revisi kelompok terbaru. Penghapusan draf, profil mahasiswa statis, dan penggabungan proses verifikasi menggantikan perilaku lama; nama method utama tetap dipertahankan.

## Cakupan 24 use case

| UC | Nama pada Modul 2 | Halaman / route | Penanggung jawab utama | Integrasi hasil |
| --- | --- | --- | --- | --- |
| 01 | Login | `login`, `login/proses`, `logout` | `AuthController`, `Akun`, `Auth` | Identitas dan peran dibaca dari AKUN; sesi mengatur seluruh akses |
| 02 | Mengelola Profil | `profil`, `profil/simpan` | `ProfilController`, `Mahasiswa`, `Perusahaan` | Profil mahasiswa statis enam data; profil/legalitas rekruter disimpan bersama; CV diunggah langsung dalam lamaran |
| 03 | Menggunakan fitur chat | `chat`, `chat/kirim` | `ChatController` | Percakapan terkait satu lamaran; aktif setelah administrasi lolos; penerima mendapat notifikasi |
| 04 | Mengirim notifikasi | `notifikasi`, `notifikasi/baca` | `Notifikasi::kirimNotifikasi()` | Dipanggil saat lamaran, penilaian, jadwal, pesan, legalitas, keputusan admin dan pengingat |
| 05 | Mencari lowongan | `lowongan` | `LowonganController::cariLowongan()`, `Lowongan::cariLowongan()` | Kata kunci pada posisi/perusahaan/deskripsi; kata kosong menampilkan semua yang tersedia |
| 06 | Menyaring lowongan | `lowongan` | `Lowongan::saring()` | Filter jenis, semester, jurusan, lokasi, mode kerja dikombinasikan |
| 07 | Melihat detail lowongan | `lowongan/detail` | `getDetailLowongan()`, `cekStatusMelamar()`, `DetailLowonganPage` | Detail persyaratan/roadmap, tombol daftar atau lihat progres jika sudah melamar |
| 08 | Mendaftar pada lowongan | `lamaran/form`, `lamaran/kirim` | `LamaranController::mendaftarLowongan()`, `validasiLamaran()` | Unggahan sementara sesi menjadi lamaran final setelah validasi; muncul di pendaftar rekruter dan notifikasi kedua pihak |
| 09 | Mengunggah dokumen persyaratan | `lamaran/kirim`, `dokumen/lihat` | `FormPendaftaranLowonganPage`, `Upload`, `DokumenController` | PDF privat dihubungkan ke lamaran; akses hanya akun yang berwenang |
| 10 | Melihat riwayat lamaran | `lamaran` | `getRiwayatLamaran()`, `getLamaranByMahasiswa()` | Daftar lamaran terkirim dan filter status berdasarkan mahasiswa dalam sesi |
| 11 | Melihat detail lamaran | `lamaran/detail` | `LamaranController::getDetailLamaran()` | Profil, lampiran, catatan hasil, timeline, jadwal, dan akses chat |
| 12 | Melihat status/progres seleksi | `lamaran`, `lamaran/detail` | `Lamaran::hasil()`, view timeline | Membaca hasil rekruter dan tahap aktif; halaman daftar diperbarui 30 detik |
| 13 | Mengelola kalender seleksi | `kalender`, `kalender/simpan`, `kalender/hapus` | `KalenderController`, `Kalender` | Jadwal seleksi dari rekruter ditambah pengingat pribadi; tambah/ubah/hapus pengingat |
| 14 | Membuat lowongan baru | `lowongan/form`, `lowongan/simpan` | `bukaFormLowongan()`, `buatLowongan()`, `validasiData()` | Lowongan dan roadmap disimpan dalam satu transaksi; publikasi hanya perusahaan terverifikasi |
| 15 | Mengisi deskripsi, persyaratan, dan roadmap | `lowongan/form`, `lowongan/simpan` | `FormPembuatanLowonganPage`, `TahapSeleksi::simpanRoadmap()` | Persyaratan dipakai saat validasi lamaran; roadmap dipakai timeline dan penilaian |
| 16 | Melihat daftar lowongan | `lowongan` untuk rekruter | `Lowongan::getDaftarLowongan()`, `DaftarLowonganPage` | Hanya milik perusahaan sesi; jumlah pendaftar diambil dari lamaran nyata |
| 17 | Melihat daftar pendaftar | `pendaftar` | `SeleksiController::getDaftarPendaftar()` | Filter lowongan/status/mahasiswa; hanya lamaran yang telah dikirim |
| 18 | Melihat profil dan dokumen pendaftar | `seleksi/detail`, `dokumen/lihat` | `getProfilPendaftar()`, `Lamaran::izin()` | Hanya kandidat lowongan milik perusahaan; unduh PDF menggunakan otorisasi server |
| 19 | Memperbarui status seleksi mahasiswa | `seleksi/status`, `seleksi/jadwal` | `SeleksiController` | Memperbarui HASIL_SELEKSI dan LAMARAN; notifikasi, chat, timeline, kalender ikut berubah |
| 20 | Melihat daftar perusahaan pendaftar | `perusahaan`, `perusahaan/detail` | `PerusahaanController`, `Perusahaan` | Pencarian nama/alamat dan filter status verifikasi |
| 21 | Menyetujui/menolak perusahaan | `perusahaan/keputusan` | `putuskanPerusahaan()` | Mengatur publikasi rekruter dan visibilitas lowongan; mengirim notifikasi hasil |
| 22 | Memverifikasi kelayakan dan legalitas perusahaan | `perusahaan/keputusan` | `putuskanPerusahaan()` | Kesimpulan Layak/Tidak Layak dicatat dalam transaksi Terima/Tolak yang sama |
| 23 | Memeriksa dokumen | `perusahaan/keputusan`, `dokumen/lihat` | `putuskanPerusahaan()`, `DokumenController` | Pemeriksaan per dokumen diisi pada card peninjauan dan disimpan bersama keputusan |
| 24 | Menghapus perusahaan yang melanggar | `perusahaan/hapus` | `hapusPerusahaan()` | Konfirmasi + alasan; soft delete perusahaan/lowongan, akun nonaktif, riwayat lamaran utuh |

Tambahan pendukung kebutuhan Modul 1: rekapitulasi per lowongan/mahasiswa, ekspor CSV, edit/tutup/hapus lowongan, unggah legalitas profil, dan jadwal wawancara. Tambahan tersebut tidak mengubah nomor 24 use case Modul 2.

## Kelas dan method utama yang dipertahankan

Diagram kelas dan tabel method utama terdapat pada Modul 2 halaman 35–44, dengan pseudocode sekitar halaman 55–60. Nama pada kode mempertahankan ejaan diagram; tipe `List`/`Map` diterjemahkan menjadi `array` PHP.

| Kelas | Method pada implementasi sesuai nama Modul 2 |
| --- | --- |
| `DaftarLowonganPage` | `bukaHalamanLowongan`, `klikCari`, `masukkanKataKunci`, `pilihTambahLowongan`, `tampilkanDaftar` |
| `FormPembuatanLowonganPage` | `batalkan`, `isiFormulir`, `kembaliMengedit`, `pilihYaBatalkan`, `publikasikan`, `tampilkanErrorKolom`, `tampilkanForm`, `tampilkanKonfirmasiBatal` |
| `DetailLowonganPage` | `bukaDetailLowongan`, `pilihDaftar`, `tampilkanDetail` |
| `RiwayatLamaranPage` | `bukaRiwayatLamaran`, `pilihDetailLamaran`, `tampilkanDaftarLamaran` |
| `FormPendaftaranLowonganPage` | `batalkan`, `kirimLamaran`, `tampilkanFormPendaftaran`, `tampilkanPesan`, `unggahDokumen` |
| `LowonganController` | `batalkanPembuatan`, `buatLowongan`, `bukaFormLowongan`, `cariLowongan`, `getDetailLowongan`, `validasiData` (private) |
| `LamaranController` | `bukaFormLamaran`, `cekStatusMelamar`, `getRiwayatLamaran`, `mendaftarLowongan`, `validasiLamaran` (private) |
| `Lowongan` | `cariLowongan`, `getDaftarLowongan`, `getLowonganById`, `getSemuaLowongan`, `simpan` |
| `Lamaran` | `cekSudahMelamar`, `getLamaranByMahasiswa`, `simpanLamaran` |
| `TahapSeleksi` | `getTahapByLowongan`, `simpanRoadmap` |
| `Perusahaan` | `getPerusahaan` |
| `Mahasiswa` | `getMahasiswa` |

Parameter seperti `idMahasiswa`, `idPerusahaan`, `idLowongan`, `dataLowongan`, `daftarTahap`, `dokumen`, `keyword`, `daftarLamaran`, `pesan`, dan `daftarError` dipertahankan. `idPerusahaan`/`idMahasiswa` pada controller bersifat private; nilai identitas otorisasi diambil dari sesi, bukan dipercaya dari input pengguna.

Adaptasi PHP: entitas mewarisi `Entity` dengan atribut bernama sama yang disimpan dalam array terlindungi dan diakses melalui `ArrayAccess` read-only. Boundary adalah adapter presentasi stateless: data halaman menjadi parameter view atau state formulir sesi, bukan seluruh atribut UI dideklarasikan ulang sebagai field objek desktop. Berkas HTML berada di `Views`, sehingga boundary/controller tidak berisi markup halaman. Helper seperti `prosesForm`, `getDetailLamaran`, `saring`, `izin` dan controller fitur tambahan merupakan pengembangan implementasi, bukan klaim bahwa seluruh nama tersebut tercantum di diagram awal.

## Alur hubungan antarfitur

1. Rekruter menyimpan `LOWONGAN` dan `TAHAP SELEKSI`; keduanya tampil di pencarian dan detail mahasiswa setelah perusahaan terverifikasi.
2. Mahasiswa menyimpan dokumen privat ke `DOKUMEN`, menyimpan ID unggahan sementara di sesi, kemudian menyimpan lamaran final ke `LAMARAN` saat dikirim. Validasi membaca persyaratan lowongan, profil, dokumen, dan duplikasi yang sama dengan model pencarian.
3. Rekruter membaca `LAMARAN` milik perusahaan; keputusan menulis `HASIL_SELEKSI`, memperbarui status/tahap pada `LAMARAN`, dan menambah `NOTIFIKASI` dalam transaksi.
4. Jadwal terikat ke pasangan lamaran/tahap yang sama. Kalender dan detail mahasiswa membaca `JADWAL_SELEKSI`. Chat terkait `idLamaran` dan syarat `administrasiLolos`.
5. Keputusan admin memperbarui `PERUSAHAAN`, lalu memengaruhi publikasi/pencarian dan sesi akun terkait. Rekap membaca data yang sama, tanpa data dummy terpisah di view.

## Penyesuaian sumber yang disengaja

| Temuan / permintaan | Keputusan implementasi |
| --- | --- |
| SIAM tercantum sebagai aktor UC-01; pengguna meminta tanpa API dan akun lokal | Gunakan `AKUN` + sesi PHP; tidak ada koneksi SIAM/SSO |
| Modul 2 memuat salah rujuk silang, misalnya UC-11 untuk daftar pada lowongan; pseudocode pencarian memakai label PC-04/UC-04 | Ikuti tabel utama: UC-05 pencarian, UC-08 pendaftaran, UC-10 riwayat, UC-14 pembuatan |
| Gambar wireframe menyediakan varian form pembuatan dua langkah dan satu halaman | Gunakan varian satu halaman: formulir/roadmap kiri dan panel kelengkapan/aksi kanan |
| Wireframe menyebut PDF 5 MB, analisis kebutuhan menyebut 10 MB | Terapkan batas wireframe 5 MB dan jelaskan di UI/README; dapat diubah di konfigurasi serta PHP upload limits |
| Wireframe menunjukkan indikator kelengkapan, sedangkan frontend dibatasi HTML/CSS | Revisi kelompok meminta otomatisasi: JavaScript native mengunggah langsung melalui POST PHP/HTML; checklist diperbarui sesudah validasi server. Tambah/hapus tahap tetap POST |
| Diagram inti hanya memiliki lima entitas | Pertahankan kelimanya, tambahkan tabel akun/berkas/seleksi/jadwal/chat/notifikasi/audit agar seluruh UC bekerja |
| Database akun asli tidak dilampirkan | Sediakan skema dan akun demo; panduan migrasi akun terpisah, tanpa menebak kredensial asli |
| Hapus perusahaan perlu tetap menjaga riwayat mahasiswa | Soft delete + nonaktif akun + hilangkan lowongan dari daftar aktif; audit dan lamaran tetap tersedia |

Method `periksaDokumen()` dan `verifikasiPerusahaan()` tetap tersedia untuk kompatibilitas nama, tetapi endpoint terpisah menolak perubahan dan mengarahkan ke keputusan gabungan. Method `unggahLegalitas()` mendelegasikan ke penyimpanan profil; tidak ada tombol/route unggah terpisah. Fitur pemeriksaan dokumen dan legalitas tetap berjalan dalam satu transaksi keputusan.

Tampilan ditingkatkan melalui palet biru, kartu putih, tipografi, indikator status, SVG lokal, dan layout responsif. Urutan struktur dasar wireframe tetap dipakai; halaman pendukung yang tidak memiliki wireframe rinci mengikuti header/sidebar dan komponen visual yang sama.
