# Menggunakan akun yang telah tersedia

Aplikasi ini tidak menyediakan register. Akun dikelola di database oleh pengelola sistem. Karena file database asli belum tersedia, paket menyertakan skema dan akun simulasi pada `02_seed.sql`.

## Kolom akun

| Kolom `AKUN` | Isi |
| --- | --- |
| `username` | Username unik, misalnya username yang sudah digunakan |
| `email` | Email unik yang juga dapat dipakai untuk masuk |
| `passwordHash` | Hash dari `password_hash()`, bukan password teks biasa |
| `namaPengguna` | Nama yang ditampilkan di header |
| `role` | Persis `Mahasiswa`, `Rekruter`, atau `Admin` |
| `aktif` | `1` untuk aktif; `0` untuk menolak login dan sesi berikutnya |
| `idMahasiswa` | FK ke `MAHASISWA`, wajib hanya untuk role Mahasiswa |
| `idPerusahaan` | FK ke `PERUSAHAAN`, wajib hanya untuk role Rekruter |

Admin memiliki kedua FK bernilai NULL. Rekruter memiliki `idMahasiswa=NULL`. Mahasiswa memiliki `idPerusahaan=NULL`. Constraint database memeriksa kecocokan tersebut.

Jika database lama sudah memakai bcrypt/Argon2 yang didukung PHP, hash dapat dipindahkan tanpa mengetahui password aslinya. Jika masih plaintext, MD5, atau SHA-1, buat hash PHP untuk password yang ditetapkan pengelola; jangan membandingkan plaintext atau menambahkan login fallback tidak aman. Script `php bin/hash_password.php` membantu menghasilkan hash untuk diisikan ke `passwordHash` melalui phpMyAdmin. Input script terminal tersebut terlihat, sehingga jalankan di terminal pribadi. Script tidak membuat akun, mengubah role, ataupun mengubah database.

Jika nama tabel/kolom database lama berbeda, sesuaikan query `app/Models/Akun.php` dan pemetaan entitas yang terkait, atau migrasikan data ke skema paket pada salinan database. Selalu pertahankan relasi `idMahasiswa`/`idPerusahaan` dan validasi password. File SQL paket bukan migrasi untuk menimpa database lama.

Role tidak diambil dari POST/GET. `Auth::login()` hanya menyimpan ID akun; `Auth::user()` memuat data akun dari database pada setiap permintaan. Karena itu menambahkan `role=Admin` pada form atau URL tidak mengubah kewenangan.
