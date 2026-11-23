# Hasil pengujian revisi — 23 November 2026

Pengujian ini menggunakan data demo pada database uji terpisah. Database Windows/Docker milik pengguna tidak diakses atau diubah.

## Hasil aktual

| Pemeriksaan | Lingkungan / hasil |
| --- | --- |
| Backend dan database yang dieksekusi | PHP 8.4.24 CLI development server, MariaDB 11.8.6 / InnoDB |
| Skema + seed | Berhasil diimpor ke database uji |
| Regresi integrasi HTTP | **117/117 lulus** |
| Revisi DOM + HTTP/database | **53/53 lulus**; JavaScript aplikasi dijalankan dengan jsdom 26.1.0, respons PHP nyata |
| Nama method Modul 2 | **47/47 lulus**, termasuk kedua validator private |
| Sintaks PHP | **66 file** diperiksa, tanpa kesalahan sintaks; file konfigurasi lokal pengujian tidak disertakan |
| Sintaks JavaScript | `node --check public/assets/app.js` lulus |
| Visual/browser native | **Belum terverifikasi untuk revisi ini** |

Hasil mesin: `docs/hasil_uji_http.json`, `tests/revision_dom_results.json`, dan `tests/lint_results.json`. Jumlah pemeriksaan adalah jumlah assertion/skenario pada masing-masing suite, bukan klaim persentase coverage seluruh kode.

## Cara dan cakupan pengujian

Suite HTTP menggunakan request nyata, cookie terpisah untuk setiap akun, multipart PDF, dan database persisten selama satu run. Suite DOM menjalankan **app.js yang sama** pada HTML respons aplikasi, mengirim unggahan nyata ke PHP, mengamati perubahan checkbox/checklist, dan menunggu polling chat. DOM tersebut tidak memiliki mesin layout/browser.

Teruji: role/otorisasi, pencarian/filter, login/CSRF, PDF palsu/ukuran, CV langsung, URL portofolio, persetujuan backend, tidak membuat draf, profil mahasiswa read-only, profil/legalitas gabungan, akses publikasi terverifikasi, hasil/jadwal gabungan, kalender/notifikasi, chat otomatis dua arah, rekap/CSV, penolakan akses lintas pemilik, dan penghapusan perusahaan tanpa kehilangan riwayat.

Pengujian kegagalan yang dijalankan:

- Trigger SQL sengaja menggagalkan pembaruan perusahaan. Hasil dokumen, audit, status dan notifikasi diperiksa tetap sama; halaman kembali ke ID perusahaan dengan isian dipertahankan.
- Data perusahaan diubah sesudah halaman ditampilkan. Keputusan dengan versi lama ditolak.
- Jadwal masa lalu diajukan bersama Lolos. Tidak ada perubahan hasil/jadwal sebagian.
- Koneksi chat disimulasikan gagal, modal muncul, lalu polling pulih tanpa menimpa textarea.
- Proses database uji dihentikan setelah halaman peninjauan dimuat. POST keputusan gagal dan kembali ke perusahaan yang sama menggunakan konteks sesi, dengan error modal dan tindakan disabled.

Exception dari trigger uji dan penghentian database sengaja dihasilkan; log tersebut bukan kegagalan tak terduga. Suite regresi normal terakhir tidak menghasilkan exception/warning PHP baru.

## Batas verifikasi yang harus diketahui

1. Chromium lokal tidak dapat dijalankan pada lingkungan eksekusi ini, dan browser terpisah tidak dapat menjangkau server localhost. Layout desktop/mobile, overflow, fokus keyboard, klik X dan Escape **native browser** belum diuji pada revisi ini. Struktur dialog/tombol X dan perubahan open-state script telah diperiksa di DOM; showModal/close dipolifil untuk jsdom, bukan bukti pengujian dialog native.
2. Pengujian database dijalankan pada MariaDB 11.8.6, bukan container MySQL 8.0/PHP 8.3 pengguna. Konfigurasi Docker lama dipertahankan dan panduan pembaruan disediakan, tetapi perlu smoke test setelah kode disalin ke laptop pengguna.
3. Belum dilakukan uji beban, transaksi simultan lintas proses, atau audit keamanan menyeluruh. Uji request kedaluwarsa, constraint dan rollback dilakukan secara berurutan.
4. Tidak ada revisi A–D yang sengaja ditunda pada implementasi. Batas di atas adalah bagian verifikasi yang belum dapat dituntaskan, bukan klaim seluruh lingkungan sudah bebas error.

## Menjalankan ulang

Gunakan **database uji baru** yang diisi `01_schema.sql` dan `02_seed.sql` untuk setiap suite. Keduanya mengubah data demo. Jangan jalankan pada database presentasi/produksi. Konfigurasi PHP server dan CLI fixture harus menunjuk database uji yang sama.

Regresi HTTP dan kontrak:

```sh
python tests/integration.py http://127.0.0.1:8082/
php tests/contracts.php
```

Suite DOM memerlukan Node.js 20+ dan dependensi khusus pengujian, bukan dependensi aplikasi:

```sh
npm ci --prefix tests
```

PowerShell (setelah database uji di-reset dan server PHP uji sudah berjalan):

```powershell
$env:RSI_TEST_ALLOW_MUTATIONS = "1"
$env:RSI_TEST_PHP = "php"
$env:RSI_TEST_URL = "http://127.0.0.1:8082/"
node tests/revisions_dom.cjs
```

CLI PHP fixture harus bisa mengakses database uji. Jika PHP server berjalan dalam Docker sementara Node ada di Windows, atur koneksi CLI ke host/port database uji yang sesuai. Jangan mengubah konfigurasi database presentasi untuk menjalankan suite.

Tes penghentian database hanya dijalankan bila `RSI_TEST_DB_PID` diberikan dan berisi PID **proses database uji yang dikelola sendiri**. Run yang menyertai paket menjalankan tes ini (53 pemeriksaan); run biasa tanpa variabel tersebut menjalankan 52 pemeriksaan. Jangan memberikan PID database lain.

## Checklist browser setelah pembaruan di Docker lama

- Login ketiga role pada profil browser terpisah; navigasi/tampilan warna dan tipografi tetap konsisten.
- Pilih CV PDF: lihat Mengunggah lalu Terunggah, tanpa tombol simpan. Unggah PDF palsu: pastikan modal tampil, X/Escape menutup, dan form tetap dapat dipakai.
- Centang/batal centang pernyataan: tombol kirim mengikuti status checkbox.
- Isi sebagian URL portofolio: jangan sampai popup muncul ketika masih mengetik; status lengkap muncul ketika URL valid.
- Pilih Lolos pada hasil seleksi: jadwal tahap berikutnya muncul dalam card yang sama; simpan dan cek kalender mahasiswa.
- Kirim pesan antarrole: tunggu sekitar 4 detik pada tab aktif, pastikan pesan tampil tanpa reload dan teks yang sedang ditulis tidak hilang.
- Admin buka perusahaan Menunggu: satu card, Tolak/Terima. Coba Terima sebelum pemeriksaan lengkap: tetap pada perusahaan sama, modal tampil. Setelah disetujui: hanya Hapus karena Pelanggaran.
- Uji lebar desktop dan sekitar 390 px: modal, dokumen, roadmap, sidebar, dan tabel dapat dibaca tanpa pemotongan konten penting.

## Rincian regresi HTTP

| No. | Pemeriksaan | Hasil |
| --- | --- | --- |
| 1 | Anonim diarahkan ke login | Lulus |
| 2 | Tidak ada registrasi | Lulus |
| 3 | POST tanpa CSRF ditolak | Lulus |
| 4 | Injeksi login tidak masuk | Lulus |
| 5 | Login mahasiswa dari database | Lulus |
| 6 | Login rekruter dari database | Lulus |
| 7 | Login admin dari database | Lulus |
| 8 | Mahasiswa halaman dashboard | Lulus |
| 9 | Mahasiswa halaman lowongan | Lulus |
| 10 | Mahasiswa halaman lamaran | Lulus |
| 11 | Mahasiswa halaman profil | Lulus |
| 12 | Mahasiswa halaman chat | Lulus |
| 13 | Mahasiswa halaman kalender | Lulus |
| 14 | Mahasiswa halaman notifikasi | Lulus |
| 15 | Rekruter halaman dashboard | Lulus |
| 16 | Rekruter halaman lowongan | Lulus |
| 17 | Rekruter halaman pendaftar | Lulus |
| 18 | Rekruter halaman profil | Lulus |
| 19 | Rekruter halaman chat | Lulus |
| 20 | Rekruter halaman kalender | Lulus |
| 21 | Rekruter halaman notifikasi | Lulus |
| 22 | Rekruter halaman rekapitulasi | Lulus |
| 23 | Admin halaman dashboard | Lulus |
| 24 | Admin halaman perusahaan | Lulus |
| 25 | Admin halaman profil | Lulus |
| 26 | Admin halaman notifikasi | Lulus |
| 27 | Admin halaman rekapitulasi | Lulus |
| 28 | Mahasiswa dilarang perusahaan | Lulus |
| 29 | Mahasiswa dilarang pendaftar | Lulus |
| 30 | Mahasiswa dilarang lowongan/form | Lulus |
| 31 | Mahasiswa dilarang rekapitulasi | Lulus |
| 32 | Mahasiswa tidak melihat lamaran orang lain | Lulus |
| 33 | Rekruter tidak melihat kandidat perusahaan lain | Lulus |
| 34 | Dokumen mahasiswa lain ditolak | Lulus |
| 35 | Dokumen kandidat perusahaan lain ditolak | Lulus |
| 36 | Dokumen pribadi diunduh sebagai PDF | Lulus |
| 37 | Pencarian tanpa peka kapital | Lulus |
| 38 | Pencarian kosong menampilkan pesan | Lulus |
| 39 | Kata kunci kosong menampilkan seluruh lowongan | Lulus |
| 40 | Semua filter bekerja bersamaan | Lulus |
| 41 | Draf perusahaan belum lolos tidak tampil | Lulus |
| 42 | Detail memuat persyaratan dan roadmap | Lulus |
| 43 | Form pendaftaran sesuai lowongan | Lulus |
| 44 | Berkas wajib divalidasi | Lulus |
| 45 | Chat bukan milik mahasiswa ditolak | Lulus |
| 46 | Chat sebelum lolos administrasi ditolak | Lulus |
| 47 | Unggahan non-PDF ditolak | Lulus |
| 48 | PDF palsu ditolak dari MIME | Lulus |
| 49 | PDF melebihi 5 MB ditolak | Lulus |
| 50 | Unggah otomatis tanpa membuat lamaran | Lulus |
| 51 | Lamaran terhubung dan terkirim | Lulus |
| 52 | Lamaran memiliki identitas tersimpan | Lulus |
| 53 | Pendaftaran ganda diblokir | Lulus |
| 54 | Pendaftar muncul pada rekruter | Lulus |
| 55 | Rekruter membuka profil dan berkas | Lulus |
| 56 | Chat terkunci sebelum administrasi | Lulus |
| 57 | Lolos administrasi memindahkan tahap | Lulus |
| 58 | Chat terbuka setelah administrasi | Lulus |
| 59 | Pesan mahasiswa tersimpan aman | Lulus |
| 60 | Pesan terlihat pada rekruter | Lulus |
| 61 | Balasan rekruter tersimpan | Lulus |
| 62 | Balasan terlihat pada mahasiswa | Lulus |
| 63 | Penilaian tahap kedaluwarsa ditolak | Lulus |
| 64 | Jadwal masa lalu ditolak | Lulus |
| 65 | Jadwal disimpan rekruter | Lulus |
| 66 | Jadwal terhubung ke kalender mahasiswa | Lulus |
| 67 | Notifikasi pembaruan jadwal terkirim | Lulus |
| 68 | Tandai semua notifikasi dibaca | Lulus |
| 69 | Jumlah notifikasi belum dibaca menjadi nol | Lulus |
| 70 | Pengingat pribadi tersimpan | Lulus |
| 71 | Pengingat jatuh tempo menjadi notifikasi | Lulus |
| 72 | Pengingat pengguna lain tidak dapat diubah | Lulus |
| 73 | Pengingat dapat diubah | Lulus |
| 74 | Hapus pengingat memerlukan konfirmasi | Lulus |
| 75 | Pengingat dihapus setelah konfirmasi | Lulus |
| 76 | Lolos tes memindahkan ke wawancara | Lulus |
| 77 | Tahap terakhir menjadi Diterima | Lulus |
| 78 | Hasil akhir tersinkron mahasiswa | Lulus |
| 79 | Status final tidak ditimpa | Lulus |
| 80 | Form lowongan rekruter | Lulus |
| 81 | Validasi kolom wajib lowongan | Lulus |
| 82 | Aksi periksa manual tidak tersedia | Lulus |
| 83 | Pembatalan menampilkan konfirmasi | Lulus |
| 84 | Kembali mengedit mempertahankan isian | Lulus |
| 85 | Konfirmasi pembatalan membuang formulir | Lulus |
| 86 | Buat lowongan dan roadmap terintegrasi | Lulus |
| 87 | Lowongan baru langsung muncul pada mahasiswa | Lulus |
| 88 | Edit lowongan perusahaan lain ditolak | Lulus |
| 89 | Edit mengisi field dari database | Lulus |
| 90 | Edit lowongan tersimpan | Lulus |
| 91 | Tutup lowongan menampilkan konfirmasi | Lulus |
| 92 | Tutup lowongan tersimpan | Lulus |
| 93 | Lowongan tutup tidak dapat dilamar | Lulus |
| 94 | Hapus lowongan hanya daftar aktif | Lulus |
| 95 | Perusahaan belum terverifikasi tidak dapat publikasi | Lulus |
| 96 | Perusahaan belum terverifikasi tidak dapat membuat draf | Lulus |
| 97 | Detail perusahaan admin | Lulus |
| 98 | Persetujuan tanpa pemeriksaan ditolak | Lulus |
| 99 | Endpoint pemeriksaan terpisah ditolak | Lulus |
| 100 | Endpoint kesimpulan terpisah ditolak | Lulus |
| 101 | Pemeriksaan dan persetujuan disimpan bersama | Lulus |
| 102 | Keputusan admin diterima rekruter | Lulus |
| 103 | Rekruter disetujui dapat mempublikasikan | Lulus |
| 104 | Rekap admin memuat hasil terhubung | Lulus |
| 105 | Ekspor CSV benar | Lulus |
| 106 | CSV rekruter hanya perusahaannya | Lulus |
| 107 | Keputusan ulang pada perusahaan terverifikasi ditolak | Lulus |
| 108 | Hapus perusahaan menampilkan konfirmasi | Lulus |
| 109 | Penghapusan perusahaan terintegrasi | Lulus |
| 110 | Sesi rekruter perusahaan dihapus dicabut | Lulus |
| 111 | Lowongan perusahaan dihapus tidak tampil | Lulus |
| 112 | Logout harus menggunakan POST | Lulus |
| 113 | Logout mengakhiri sesi | Lulus |
| 114 | Sesi lama tidak bisa membuka halaman privat | Lulus |
| 115 | Perubahan profil mahasiswa ditolak server | Lulus |
| 116 | Simpan profil dan legalitas memicu verifikasi ulang | Lulus |
| 117 | Admin menerima notifikasi legalitas | Lulus |

## Rincian revisi DOM + HTTP/database

| No. | Pemeriksaan | Hasil |
| --- | --- | --- |
| 1 | Login tiga role memakai sesi terpisah | Lulus |
| 2 | Profil mahasiswa hanya enam informasi statis | Lulus |
| 3 | POST manipulasi profil mahasiswa ditolak tanpa perubahan data | Lulus |
| 4 | Profil rekruter mempunyai satu form penyimpanan termasuk legalitas | Lulus |
| 5 | PDF legalitas palsu tidak menyimpan perubahan profil sebagian | Lulus |
| 6 | Satu submit menyimpan profil dan legalitas, status menjadi Menunggu | Lulus |
| 7 | Perusahaan menunggu tidak dapat mengakses form lowongan | Lulus |
| 8 | Permintaan draf/publikasi palsu perusahaan menunggu tidak menambah data | Lulus |
| 9 | Draf lama tidak ditampilkan atau dapat dibuka melalui URL langsung | Lulus |
| 10 | Rekruter terverifikasi juga tidak dapat menyimpan draf melalui POST | Lulus |
| 11 | Peninjauan admin satu card dengan dua aksi Tolak/Terima | Lulus |
| 12 | Persetujuan tidak lengkap kembali ke perusahaan sama, nilai form dipertahankan | Lulus |
| 13 | Error server dibuka sebagai dialog oleh JavaScript | Lulus |
| 14 | Penolakan tanpa alasan kembali dengan popup, status tetap Menunggu | Lulus |
| 15 | CSRF tidak valid tetap kembali ke konteks perusahaan | Lulus |
| 16 | Dokumen asing tidak dapat disisipkan dalam verifikasi | Lulus |
| 17 | Versi peninjauan kedaluwarsa tidak dapat menimpa perubahan perusahaan | Lulus |
| 18 | Kegagalan SQL membatalkan dokumen, keputusan, audit dan notifikasi bersama | Lulus |
| 19 | Terima menyimpan keputusan dan mengaktifkan akses pembuatan lowongan | Lulus |
| 20 | Keputusan ulang pada perusahaan terverifikasi ditolak | Lulus |
| 21 | Penolakan perusahaan menunggu mempertahankan pembatasan rekruter | Lulus |
| 22 | Form lowongan menggabungkan dua bagian dalam satu card utama | Lulus |
| 23 | Checklist lowongan diperbarui langsung ketika isian berubah | Lulus |
| 24 | Lowongan dan roadmap dipublikasikan dari form yang sama | Lulus |
| 25 | Publikasi terhubung ke pencarian mahasiswa | Lulus |
| 26 | Detail lowongan menggabungkan informasi dan roadmap | Lulus |
| 27 | Lamaran tanpa draf, tombol simpan/periksa, penggunaan CV profil dan upload portofolio | Lulus |
| 28 | Upload CV otomatis benar-benar dikirim ke server tanpa membuat lamaran | Lulus |
| 29 | URL portofolio memperbarui kelengkapan tanpa popup saat sedang mengetik | Lulus |
| 30 | Centang pernyataan mengaktifkan tombol, batal centang menonaktifkannya | Lulus |
| 31 | Backend tetap menolak kirim tanpa persetujuan meskipun dokumen lengkap | Lulus |
| 32 | Nilai persetujuan palsu selain 1 ditolak server | Lulus |
| 33 | Aksi draf dan penggunaan CV profil tidak dapat dipanggil kembali | Lulus |
| 34 | Upload portofolio ditolak dan URL berbahaya tidak dapat dikirim | Lulus |
| 35 | PDF palsu ditolak server, modal muncul dan unggahan valid sebelumnya bertahan | Lulus |
| 36 | File terlalu besar ditolak otomatis sebelum request atau simpan data | Lulus |
| 37 | Hapus unggahan memperbarui status langsung tanpa reload | Lulus |
| 38 | Tombol kirim disabled selama unggahan belum dikonfirmasi server | Lulus |
| 39 | Lamaran final menyimpan CV unggahan langsung dan URL portofolio | Lulus |
| 40 | Rekruter melihat kelengkapan otomatis dan form gabungan hasil/jadwal | Lulus |
| 41 | Pilihan Lolos membuka isian jadwal untuk tahap berikutnya | Lulus |
| 42 | Jadwal tidak valid membatalkan hasil sekaligus jadwal tanpa data sebagian | Lulus |
| 43 | Hasil dan jadwal valid tersimpan pada tahap aktif berikutnya | Lulus |
| 44 | Kalender dan notifikasi mahasiswa mengikuti jadwal | Lulus |
| 45 | Penilaian tahap lama ditolak tanpa menimpa progres | Lulus |
| 46 | Pembaruan jadwal saja tidak membuat hasil seleksi baru | Lulus |
| 47 | Pesan rekruter muncul otomatis dan tulisan mahasiswa tidak hilang | Lulus |
| 48 | Balasan mahasiswa muncul otomatis di sisi rekruter | Lulus |
| 49 | Koneksi chat gagal memunculkan modal dan pulih otomatis | Lulus |
| 50 | Tahap final menyembunyikan jadwal dan menghasilkan Diterima pada timeline | Lulus |
| 51 | Detail perusahaan disetujui lengkap, legalitas dan daftar lowongan tersedia | Lulus |
| 52 | Script berjalan tanpa exception pada seluruh DOM yang diuji | Lulus |
| 53 | Database mati: admin tetap melihat data perusahaan dan popup, aksi dinonaktifkan | Lulus |
