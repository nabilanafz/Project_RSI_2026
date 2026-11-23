# Rancangan database terintegrasi

Mesin: InnoDB, charset `utf8mb4`. Koneksi PDO memakai parameter terikat (native prepared statements). Zona waktu aplikasi dan sesi database adalah WIB (UTC+7).

Lima tabel inti mengikuti nama PDM Modul 2. Tabel dengan spasi ditulis menggunakan backtick dalam SQL: `TAHAP SELEKSI`. Atribut inti seperti `idLowongan`, `namaLowongan`, `jenisPekerjaan`, `modeKerja`, `lokasiPenempatan`, `tingkatSemesterMinimal`, `targetJurusan`, `deskripsi`, `dokumenPersyaratan`, `statusLowongan`, dan `tanggalDibuat` dipertahankan.

| Tabel | Peran dan relasi |
| --- | --- |
| `MAHASISWA` | Profil; 1 mahasiswa mempunyai banyak lamaran dan satu akun |
| `PERUSAHAAN` | Profil/legalitas/status verifikasi; 1 perusahaan mempunyai banyak lowongan dan dapat memiliki beberapa akun rekruter |
| `LOWONGAN` | Milik satu perusahaan; menyimpan target jurusan, syarat dokumen, batas lamaran dan kuota |
| `TAHAP SELEKSI` | Milik satu lowongan; urutan unik pada setiap lowongan |
| `LAMARAN` | Menghubungkan mahasiswa dan lowongan; satu pasangan mahasiswa–lowongan dijaga UNIQUE |
| `AKUN` | Username/email/hash/role/aktif; FK mahasiswa atau perusahaan sesuai role |
| `DOKUMEN` | Metadata berkas privat: pemilik akun, nama asli, nama acak, ukuran dan MIME |
| `DOKUMEN_PERUSAHAAN` | Legalitas perusahaan dan hasil pemeriksaan admin per berkas |
| `HASIL_SELEKSI` | Satu hasil per lamaran/tahap; catatan, penilai, waktu pembaruan |
| `JADWAL_SELEKSI` | Satu jadwal per lamaran/tahap; waktu, tempat dan tautan pertemuan opsional |
| `PESAN_CHAT` | Pesan berurutan untuk satu lamaran; pemilik pesan adalah akun pengirim |
| `NOTIFIKASI` | Penerima akun, judul, isi, tautan internal dan status dibaca |
| `PENGINGAT` | Pengingat pribadi pemilik akun; dapat terkait lamaran; penanda sudah dikirim mencegah pengiriman ulang |
| `RIWAYAT_VERIFIKASI` | Jejak kesimpulan/keputusan admin beserta alasan |
| `LOGIN_PERCOBAAN` | Pembatasan percobaan login per hash username+alamat IP, jendela 15 menit |

Kolom multi-nilai `targetJurusan` dan `dokumenPersyaratan` menggunakan teks JSON untuk menjaga penamaan PDM dan kompatibilitas MySQL/MariaDB. Pada `LOWONGAN`, syarat dokumen berupa daftar jenis berkas. Pada `LAMARAN`, dokumen berupa map jenis → `idDokumen`. Semua ID berkas hasil upload diperoleh dari server dan dicek kepemilikannya; pengguna tidak dapat memasukkan ID milik akun lain melalui form.

Status lamaran aktif: `Lamaran Terkirim` → `Diproses`/`Wawancara` → `Diterima` atau `Ditolak`. Roadmap dapat berisi 1–12 tahap; tahap pertama adalah administrasi. Chat terbuka ketika tahap pertama dinilai Lolos. Nama tahap yang mengandung “wawancara” membuat label tahap aktif menjadi Wawancara. Penilaian Tidak Lolos menyelesaikan lamaran sebagai Ditolak; Lolos pada tahap terakhir menjadi Diterima setelah cek kuota.

Foreign key gabungan menjaga hasil/jadwal/tahap aktif tetap berada pada lowongan yang sama. Indeks unik menjaga satu lamaran per mahasiswa–lowongan serta satu hasil/jadwal per lamaran–tahap. Transaksi pada pengiriman lamaran dan penilaian mengunci baris lowongan, sehingga pengecekan duplikasi, perpindahan tahap dan kuota tidak hanya mengandalkan tombol UI. Pengeditan roadmap ditolak secara efektif setelah lamaran terkirim melalui pemeriksaan ulang model di dalam transaksi.

Berkas PDF tidak disimpan sebagai BLOB; file fisik berada di `storage/uploads`. `dokumen/lihat` memeriksa hak akses sebelum mengirim data PDF. Karena itu mencadangkan aplikasi memerlukan **database dan folder/volume uploads** sekaligus. Sesuaikan pengelolaan/retensi berkas untuk penggunaan nyata; paket tidak menghapus otomatis berkas yang pernah diganti agar dokumen riwayat tidak hilang.

Penghapusan perusahaan/lowongan tidak menggunakan cascade penghancuran data. `dihapusPada` menyembunyikan data dari daftar aktif; `AKUN.aktif=0` mencabut akses rekruter, dan mahasiswa tetap dapat membaca riwayat lamarannya.

## Kompatibilitas pembaruan 23 November 2026

Tidak ada tabel atau kolom baru yang diwajibkan untuk pembaruan ini. Database instalasi sebelumnya tetap dapat dipakai tanpa impor ulang. Aplikasi selalu mengirim status eksplisit saat menyimpan sehingga perubahan nilai default pada skema instalasi baru tidak memerlukan migrasi database lama.

Nilai ENUM `Draf` dipertahankan hanya untuk kompatibilitas data historis. Tidak ada fitur yang membuat atau melanjutkan draf. Draf lowongan lama tidak tampil dan tidak dapat diedit/dibuka melalui URL. Draf lamaran lama tidak tampil; bila mahasiswa mengirim lamaran baru yang valid untuk pasangan mahasiswa–lowongan yang sama, baris lama digunakan kembali agar indeks unik tetap terjaga. Dokumen lama tidak otomatis dipakai; CV harus diunggah lagi di formulir.

Unggahan yang belum dikirim disimpan sebagai metadata `DOKUMEN` dan file privat; sesi hanya menyimpan ID yang telah divalidasi selama maksimal 2 jam. Setelah batal/kedaluwarsa, sesi tidak melanjutkan formulir tersebut. Berkas tanpa referensi tidak dihapus otomatis, mengikuti kebijakan retensi lama. Portofolio baru disimpan pada kolom `LAMARAN.portofolio` sebagai URL; dokumen PDF portofolio dari riwayat lama tetap dapat dibaca agar riwayat tidak rusak.

Profil mahasiswa tetap tersimpan di tabel lama; kolom tambahan lama tidak dihapus meski halaman profil kini hanya membaca enam data. Satu transaksi menggabungkan profil/legalitas rekruter. Verifikasi admin mengunci perusahaan, memeriksa versi data, lalu menyimpan hasil dokumen, audit, status dan notifikasi bersama. Bila gagal, transaksi dibatalkan. Hasil seleksi dan jadwal juga disimpan bersama; jadwal setelah Lolos terikat tahap berikutnya yang aktif.
