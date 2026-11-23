# FILKOM Connect — Sistem Informasi Magang dan Paruh Waktu

Implementasi **HTML + CSS, PHP native dengan struktur MVC, dan MySQL** berdasarkan laporan Kelompok 5 Modul 1–2 dan `Wireframe RSI.zip`. Tidak menggunakan framework, JavaScript, Composer, atau API eksternal. Semua interaksi menggunakan halaman/form HTTP yang dirender PHP. phpMyAdmin digunakan untuk mengelola MySQL melalui browser; phpMyAdmin sendiri bukan mesin database.

## 1. Menjalankan dengan Docker

Paket ini memakai port berbeda dari stack LAMP yang menggunakan 8000/8080/3307: aplikasi **8082**, phpMyAdmin **8083**, dan MySQL host **3308**.

1. Ekstrak ZIP dan buka terminal di folder `RSI_MVC` yang berisi `compose.yaml`.
2. Pastikan Docker Desktop sudah berjalan.
3. Jalankan:

```sh
docker compose up --build -d
```

4. Buka aplikasi: **http://localhost:8082/**.
5. Buka phpMyAdmin: **http://localhost:8083/**. Masuk dengan username MySQL `rsi_user` dan password `RsiLocal2026!`. Database: `rsi_filkom`.

Pada volume database baru, `01_schema.sql` dan `02_seed.sql` diimpor otomatis. Jangan mengimpor kembali seed pada database yang sudah berisi data. Database, unggahan, dan sesi menggunakan volume persisten. `docker compose down` menghentikan container tanpa menghapus volume. Jika kode diubah, jalankan kembali `docker compose up --build -d`; tidak perlu me-reset database.

`compose.yaml` menyediakan konfigurasi lokal. Untuk mengganti password **MySQL** sebelum pemasangan pertama, salin `.env.example` menjadi `.env` lalu ubah nilainya. Password ini berbeda dari kata sandi akun aplikasi. Mengubah `.env` setelah volume MySQL sudah terinisialisasi tidak otomatis mengubah akun di database.

## 2. Alternatif: XAMPP/Laragon atau PHP + MySQL yang sudah ada

Kebutuhan: PHP **8.2+**, ekstensi `pdo_mysql`, `mbstring`, `fileinfo`, dan sesi; MySQL 8.0+ atau MariaDB 10.4+. Untuk hasil yang paling dekat dengan pengujian, gunakan PHP 8.3/8.4 dan MySQL 8.0 atau MariaDB 11.8.

1. Letakkan seluruh folder `RSI_MVC` di `htdocs`/direktori proyek.
2. Nyalakan Apache dan MySQL.
3. Di phpMyAdmin, impor berurutan `database/01_schema.sql` lalu `database/02_seed.sql` ke instalasi/database **baru**. Keduanya menggunakan nama `rsi_filkom`. Tidak ada perintah DROP pada berkas instalasi.
4. Salin `config/database.local.example.php` menjadi `config/database.local.php`, kemudian sesuaikan host, port, username, dan password MySQL.
5. Pastikan `storage/uploads`, `storage/logs`, dan `storage/sessions` dapat ditulis oleh proses PHP. Berkas contoh dalam `storage/uploads/demo_*.pdf` harus ikut disalin.
6. Buka **http://localhost/RSI_MVC/public/** jika memakai XAMPP/Laragon. Untuk virtual host, arahkan document root ke folder **`public`**.

Alternatif server pengembangan PHP, jalankan dari folder proyek:

```sh
php -c php.ini -S 127.0.0.1:8082 -t public
```

Jika memakai Apache, terapkan batas unggahan pada `php.ini` yang digunakan Apache: `upload_max_filesize=5M`, `post_max_size=32M`, lalu restart Apache. Berkas `php.ini` proyek sudah berisi pengaturan tersebut untuk Docker dan perintah CLI di atas.

Periksa lingkungan dan koneksi:

```sh
php bin/cek_lingkungan.php
```

### Menyambungkan ke stack Docker LAMP yang sudah ada

- PHP di komputer host → MySQL yang memetakan `3307:3306`: host `127.0.0.1`, port `3307`.
- PHP dan MySQL sama-sama di container pada jaringan Compose yang sama: host **nama service MySQL** dari `compose.yaml` Anda, port internal **3306**. Jangan memakai `localhost` untuk menghubungi container MySQL lain.
- Mapping `3307:3307` hanya benar jika server MySQL di dalam container memang diubah agar mendengarkan port 3307. Untuk konfigurasi MySQL standar, gunakan `3307:3306`.
- Alamat/port phpMyAdmin, misalnya `localhost:8080`, **bukan** host/port koneksi PDO.

## 3. Akun contoh

Semua data awal, perusahaan, mahasiswa, serta PDF dalam paket adalah **data fiktif untuk demonstrasi**. Karena database akun asli tidak disertakan, paket menyediakan akun contoh berikut; login membaca akun dan peran dari tabel `AKUN`.

| Username | Password | Peran | Keterangan |
| --- | --- | --- | --- |
| `mahasiswa` | `Filkom2026!` | Mahasiswa | Nadia Putri; memiliki beberapa contoh status lamaran |
| `mahasiswa2` | `Filkom2026!` | Mahasiswa | Raka Pratama; akun kedua untuk uji kepemilikan data |
| `rekruter` | `Filkom2026!` | Rekruter | Arunika Digital, terverifikasi |
| `rekruter2` | `Filkom2026!` | Rekruter | Ruang Karya Studio, menunggu verifikasi |
| `rekruter3` | `Filkom2026!` | Rekruter | Aksara Data, terverifikasi |
| `admin` | `Filkom2026!` | Admin | Verifikasi perusahaan dan rekapitulasi |

Tidak ada registrasi, pilihan peran pada login, atau kredensial yang dibandingkan langsung di controller. Login menerima username/email, memverifikasi `passwordHash` menggunakan `password_verify()`, lalu mengambil `role`, `idMahasiswa`, atau `idPerusahaan` dari database. Sesi berubah ketika login/logout, memiliki waktu kedaluwarsa, dan akun nonaktif ditolak pada permintaan berikutnya.

Untuk memakai akun yang sudah dimiliki, baca **[docs/AKUN_DATABASE.md](docs/AKUN_DATABASE.md)**. Impor skema/seed contoh bukan migrasi otomatis untuk database lama. Samakan struktur atau pindahkan akun secara terencana ke tabel `AKUN`; hash password bukan teks biasa.

## 4. Alur demo yang terintegrasi

1. Masuk `mahasiswa` → **Lowongan** → **Data Analyst Intern** → **Daftar**. Unggah CV dan transkrip PDF, atau gunakan CV profil lalu unggah transkrip. Centang persetujuan dan kirim.
2. Masuk `rekruter` pada browser/profil browser berbeda → **Pendaftar** → tinjau lamaran Nadia. Profil dan PDF yang dikirim mahasiswa tersedia di sini.
3. Beri hasil **Lolos** untuk tahap administrasi. Mahasiswa otomatis berpindah tahap, menerima notifikasi, dan memperoleh akses chat dengan rekruter.
4. Atur jadwal tes/wawancara. Jadwal muncul di detail lamaran dan kalender mahasiswa. Lanjutkan hasil tahap aktif hingga **Diterima**, atau pilih **Tidak Lolos** untuk menolak.
5. Halaman **Lamaran** mahasiswa menampilkan timeline dan memperbarui halaman setiap 30 detik. **Notifikasi** juga diperbarui setiap 30 detik. Chat memakai tombol **Muat ulang pesan**; tidak memakai polling API/WebSocket.
6. Masuk `admin` → **Perusahaan** → **Ruang Karya Studio**. Buka PDF → simpan pemeriksaan **Sesuai** → simpulkan **Layak** → **Setujui Perusahaan**. `rekruter2` menerima notifikasi dan dapat mempublikasikan lowongan.
7. **Rekapitulasi** menampilkan hasil tersimpan per lowongan dan mahasiswa; ekspor CSV tersedia bagi admin dan rekruter sesuai kewenangannya.

Pengingat pribadi muncul sebagai notifikasi ketika waktu yang dipilih telah tiba. Pemrosesan dilakukan pada setiap permintaan pengguna yang sudah login. Agar tetap diproses saat tidak ada pengunjung, jadwalkan perintah berikut setiap menit melalui cron/Windows Task Scheduler:

```sh
php /path/RSI_MVC/bin/pengingat.php
```

Docker: `docker compose exec -T web php bin/pengingat.php`. Notifikasi disimpan di aplikasi; tidak ada pengiriman email, WhatsApp, atau layanan eksternal.

## 5. Struktur kode

| Lokasi | Tanggung jawab |
| --- | --- |
| `public/index.php` | Front controller, daftar rute, aturan metode HTTP, role, dan CSRF |
| `public/assets/app.css` | Seluruh tampilan responsif, termasuk layar kecil dan gaya cetak |
| `app/Controllers` | Alur use case, validasi, otorisasi, transaksi, dan pengiriman data ke view |
| `app/Models` | Entitas, pengambilan/penyimpanan data dan relasi MySQL |
| `app/Views` | Template HTML dengan escaping PHP; tidak menjalankan kueri database |
| `app/Boundaries` | Adapter halaman yang mempertahankan nama boundary dan event pada Modul 2 |
| `app/Core` | PDO, sesi, autentikasi, upload, renderer, dan helper |
| `config` | Konfigurasi aplikasi dan database |
| `database` | Skema SQL dan seed fiktif |
| `storage/uploads` | PDF privat; akses melalui controller dengan pemeriksaan kepemilikan |
| `storage/sessions` | Berkas sesi PHP di luar public |
| `storage/logs` | Log kesalahan server di luar public |
| `bin` | Perintah pemeriksaan lingkungan, hash password, dan pengingat |
| `docs` | Pemetaan 24 use case, rancangan data, dan hasil pengujian |
| `tests` | Pengujian integrasi HTTP; gunakan hanya database uji yang boleh diubah |

Nama utama Modul 2 dipertahankan: `LowonganController`, `LamaranController`, `Lowongan`, `Lamaran`, `TahapSeleksi`, `Perusahaan`, `Mahasiswa`, serta kelima boundary. Method seperti `cariLowongan()`, `buatLowongan()`, `bukaFormLowongan()`, `validasiData()`, `mendaftarLowongan()`, `validasiLamaran()`, `getRiwayatLamaran()`, `cekSudahMelamar()`, `simpanLamaran()`, dan `simpanRoadmap()` tetap digunakan. Daftar lengkap ada di **[docs/PEMETAAN_MODUL.md](docs/PEMETAAN_MODUL.md)**.

## 6. Perilaku penting

- Semua 24 use case pada daftar Modul 2 tercakup; penomoran mengikuti daftar utama Modul 2, bukan rujukan silang yang tidak konsisten dalam beberapa paragraf skenario.
- SIAM pada dokumen diganti autentikasi lokal sesuai permintaan terbaru: tidak ada integrasi API SIAM.
- Struktur wireframe dipertahankan: header dan sidebar, daftar kartu lowongan, formulir di kiri/kelengkapan di kanan, serta timeline lamaran. Form lowongan memakai varian **satu halaman** dalam wireframe. Tombol tambah/hapus tahap dan periksa kelengkapan melakukan submit halaman agar tetap HTML/CSS tanpa JavaScript.
- Dokumen PDF maksimal **5 MB per berkas**, mengikuti wireframe. Ada perbedaan dengan batas 10 MB di dokumen analisis; pilihan implementasi dicatat pada pemetaan. Berkas wajib mengikuti persyaratan lowongan. Portofolio dapat berupa PDF atau URL http/https.
- Draf lamaran tidak masuk daftar pendaftar rekruter. Satu mahasiswa hanya memiliki satu lamaran per lowongan, dijaga indeks unik database. Berkas valid tetap tersimpan saat validasi pengiriman gagal.
- Tahap seleksi harus diproses berurutan. Tahap pertama adalah administrasi; kuota dicek ketika menerima kandidat di tahap terakhir. Roadmap dikunci setelah ada lamaran terkirim agar hasil yang sudah ada tetap konsisten.
- Menghapus perusahaan menyembunyikan perusahaan/lowongan aktif dan menonaktifkan akun rekruter. Riwayat lamaran mahasiswa dan jejak keputusan tetap tersimpan. Menghapus lowongan juga menggunakan soft delete.
- Password menggunakan hash; SQL memakai prepared statements; mutasi memakai POST + CSRF; tampilan meng-escape input; PDF disimpan di luar public dengan nama acak. Jangan menempatkan database/konfigurasi di dalam document root public.

## 7. Pengujian dan batas verifikasi

Lihat **[docs/PENGUJIAN.md](docs/PENGUJIAN.md)** untuk lingkungan dan hasil pengujian aktual. Jalankan suite HTTP hanya pada salinan database yang baru diisi kedua SQL dan boleh diubah, karena suite membuat lamaran, menilai kandidat, mengubah profil, serta menghapus perusahaan demo:

```sh
python tests/integration.py http://127.0.0.1:8082/
```

Tidak memerlukan pustaka Python tambahan. Parameter `--snapshots` hanya untuk pemeriksaan pengembang: menghasilkan HTML sementara `public/__qa_*.html`. Jangan gunakan opsi itu pada server yang berisi data nyata dan hapus berkas tersebut setelah pemeriksaan.

Paket SQL diuji pada MariaDB 11.8 dengan PHP 8.4. Konfigurasi Docker menyediakan MySQL 8.0/PHP 8.3; build container Docker dan instalasi pada komputer Anda belum dieksekusi di lingkungan ini.

## 8. Pemecahan masalah

| Gejala | Pemeriksaan |
| --- | --- |
| Koneksi database gagal | Periksa host/port internal vs host, nama database, kredensial, dan MySQL sudah aktif |
| `could not find driver` | Aktifkan ekstensi `pdo_mysql` pada PHP yang benar, lalu restart server |
| Login kembali ke form terus | Pastikan `storage/sessions` bisa ditulis; jangan menonaktifkan cookie browser |
| Error 419 saat upload | Muat ulang form untuk CSRF baru; periksa `post_max_size` dan total ukuran unggahan |
| PDF gagal diunggah | Gunakan PDF sungguhan ≤5 MB dan pastikan `storage/uploads` bisa ditulis |
| Perusahaan tidak dapat publikasi | Periksa status verifikasi; admin perlu memeriksa dokumen, simpulkan Layak, lalu setujui |
| Lowongan tidak muncul | Periksa status Aktif, batas lamaran, status perusahaan dan filter; draf/tutup/dihapus tidak ditampilkan |
| Database Docker belum siap | `docker compose logs db` dan `docker compose ps`; tunggu healthcheck database |
| Perubahan konfigurasi tidak berlaku | `database.local.php` mengalahkan environment; jangan menyalin file lokal XAMPP ke container |
| Pesan “Permintaan belum selesai” | Baca `storage/logs/php.log`; detail kesalahan tidak ditampilkan kepada pengguna |
