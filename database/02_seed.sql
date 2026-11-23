-- DATA DEMO FIKTIF. Impor sekali setelah 01_schema.sql pada database baru.

-- Semua password demo: Filkom2026! (disimpan sebagai bcrypt).

USE rsi_filkom;
SET time_zone = '+07:00';

START TRANSACTION;

INSERT INTO PERUSAHAAN (idPerusahaan,namaPerusahaan,alamat,emailPerusahaan,statusVerifikasi,bidangUsaha,kontak,deskripsi,website,catatanVerifikasi) VALUES
(1,'Arunika Digital','Kota Malang, Jawa Timur','hr@arunika.example','Terverifikasi','Teknologi & Produk Digital','080000000001','Perusahaan simulasi yang mengembangkan produk digital untuk membantu kegiatan usaha dan pendidikan. Tim kami mengutamakan kolaborasi, pembelajaran, dan kualitas solusi.','','Dokumen simulasi telah diperiksa.'),
(2,'Ruang Karya Studio','Kota Surabaya, Jawa Timur','hr@ruangkarya.example','Menunggu','Desain & Pengembangan Web','080000000002','Studio simulasi yang berfokus pada desain dan pengembangan situs web.','','Menunggu pemeriksaan legalitas oleh admin.'),
(3,'Aksara Data','Jakarta Selatan, DKI Jakarta','hr@aksaradata.example','Terverifikasi','Analitik & Data','080000000003','Perusahaan simulasi yang mengolah data menjadi wawasan untuk pengambilan keputusan.','','Dokumen simulasi telah diperiksa.');

INSERT INTO MAHASISWA (idMahasiswa,namaMahasiswa,NIM,email,jurusan,semester,IPK,telepon,portofolio,bio) VALUES
(1,'Nadia Putri','235150400111001','nadia@student.example','Sistem Informasi',5,3.75,'080000000011','','Mahasiswa simulasi yang tertarik pada analisis data dan pengembangan sistem informasi.'),
(2,'Raka Pratama','235150200111002','raka@student.example','Teknik Informatika',5,3.65,'080000000012','','Mahasiswa simulasi yang tertarik pada pemrograman web.');

INSERT INTO AKUN (idAkun,username,email,passwordHash,namaPengguna,role,idMahasiswa,idPerusahaan) VALUES
(1,'mahasiswa','nadia@student.example','$2y$12$81OdNDSWry.jHbhfU48ssuXye8UY5yLw8czmvwxss.xM3rgJL6h4u','Nadia Putri','Mahasiswa',1,NULL),
(2,'mahasiswa2','raka@student.example','$2y$12$81OdNDSWry.jHbhfU48ssuXye8UY5yLw8czmvwxss.xM3rgJL6h4u','Raka Pratama','Mahasiswa',2,NULL),
(3,'rekruter','hr@arunika.example','$2y$12$81OdNDSWry.jHbhfU48ssuXye8UY5yLw8czmvwxss.xM3rgJL6h4u','Alya Santoso','Rekruter',NULL,1),
(4,'rekruter2','hr@ruangkarya.example','$2y$12$81OdNDSWry.jHbhfU48ssuXye8UY5yLw8czmvwxss.xM3rgJL6h4u','Bagas Pramana','Rekruter',NULL,2),
(5,'rekruter3','hr@aksaradata.example','$2y$12$81OdNDSWry.jHbhfU48ssuXye8UY5yLw8czmvwxss.xM3rgJL6h4u','Dimas Wicaksono','Rekruter',NULL,3),
(6,'admin','admin@filkom.example','$2y$12$81OdNDSWry.jHbhfU48ssuXye8UY5yLw8czmvwxss.xM3rgJL6h4u','Admin FILKOM','Admin',NULL,NULL);

INSERT INTO LOWONGAN (idLowongan,namaLowongan,jenisPekerjaan,modeKerja,lokasiPenempatan,tingkatSemesterMinimal,targetJurusan,deskripsi,dokumenPersyaratan,statusLowongan,idPerusahaan,kuota,durasiBulan,uangSaku,konversiSKS,kualifikasi) VALUES
(1,'Data Analyst Intern','Magang','Hybrid','Malang',3,'["Sistem Informasi", "Teknik Informatika", "Teknologi Informasi"]','Menganalisis data operasional, menyiapkan dashboard, dan menyampaikan temuan kepada tim produk. Kamu akan berkolaborasi dengan analis serta mendapatkan pendampingan terstruktur.','["CV", "Transkrip"]','Aktif',1,3,3,2000000,10,'Memahami statistik dasar dan spreadsheet.
Mengenal SQL dan visualisasi data.
Mampu berkomunikasi dan bekerja dalam tim.'),
(2,'Frontend Developer Intern','Magang','WFO','Malang',3,'["Sistem Informasi", "Teknik Informatika", "Teknologi Informasi"]','Membangun antarmuka website yang responsif dan mudah digunakan. Berkolaborasi dengan desainer untuk menerjemahkan rancangan menjadi halaman web.','["CV", "Transkrip"]','Aktif',1,2,3,1800000,10,'Memahami HTML, CSS, dan dasar pemrograman.
Memiliki perhatian pada detail dan aksesibilitas.'),
(3,'UI/UX Research Assistant','Paruh Waktu','WFH','Malang',3,'["Sistem Informasi", "Teknik Informatika", "Teknologi Informasi"]','Membantu riset pengguna, membuat wireframe, dan mengevaluasi alur penggunaan produk digital.','["CV", "Transkrip"]','Aktif',1,2,3,1500000,10,'Memahami proses riset pengguna.
Mampu menyusun dokumentasi dan presentasi hasil riset.'),
(4,'Data Engineer Intern','Magang','Hybrid','Jakarta',5,'["Sistem Informasi", "Teknik Informatika", "Teknologi Informasi"]','Membantu pengolahan data, penyiapan pipeline, dan pemeliharaan kualitas data untuk kebutuhan analitik.','["CV", "Transkrip"]','Aktif',3,2,3,2500000,10,'Memahami SQL dan pemrograman dasar.
Teliti dalam pemeriksaan data.'),
(5,'Quality Assurance Intern','Magang','WFH','Jakarta',3,'["Sistem Informasi", "Teknik Informatika", "Teknologi Informasi"]','Menyusun skenario uji dan membantu pengujian aplikasi web bersama tim pengembang.','["CV", "Transkrip"]','Aktif',3,1,3,1700000,10,'Memahami pengujian perangkat lunak.
Mampu melaporkan temuan secara jelas.'),
(6,'Web Developer Intern','Magang','Hybrid','Surabaya',3,'["Sistem Informasi", "Teknik Informatika", "Teknologi Informasi"]','Membantu pengembangan website dan pemeliharaan halaman produk.','["CV", "Transkrip"]','Ditutup',2,2,3,1500000,10,'Menguasai dasar HTML, CSS, PHP, dan SQL.');

UPDATE LOWONGAN SET batasLamaran=DATE_ADD(CURRENT_DATE,INTERVAL 45 DAY),tanggalDibuat=DATE_SUB(NOW(),INTERVAL idLowongan DAY);

INSERT INTO `TAHAP SELEKSI` (idTahap,urutan,namaTahap,keterangan,idLowongan) VALUES
(1,1,'Seleksi Administrasi','Pemeriksaan kesesuaian profil dan kelengkapan dokumen.',1),
(2,2,'Tes Teknis','Penilaian keterampilan sesuai posisi yang dilamar.',1),
(3,3,'Wawancara','Diskusi bersama rekruter dan pengumuman hasil akhir.',1),
(4,1,'Seleksi Administrasi','Pemeriksaan kesesuaian profil dan kelengkapan dokumen.',2),
(5,2,'Tes Teknis','Penilaian keterampilan sesuai posisi yang dilamar.',2),
(6,3,'Wawancara','Diskusi bersama rekruter dan pengumuman hasil akhir.',2),
(7,1,'Seleksi Administrasi','Pemeriksaan kesesuaian profil dan kelengkapan dokumen.',3),
(8,2,'Tes Teknis','Penilaian keterampilan sesuai posisi yang dilamar.',3),
(9,3,'Wawancara','Diskusi bersama rekruter dan pengumuman hasil akhir.',3),
(10,1,'Seleksi Administrasi','Pemeriksaan kesesuaian profil dan kelengkapan dokumen.',4),
(11,2,'Tes Teknis','Penilaian keterampilan sesuai posisi yang dilamar.',4),
(12,3,'Wawancara','Diskusi bersama rekruter dan pengumuman hasil akhir.',4),
(13,1,'Seleksi Administrasi','Pemeriksaan kesesuaian profil dan kelengkapan dokumen.',5),
(14,2,'Tes Teknis','Penilaian keterampilan sesuai posisi yang dilamar.',5),
(15,3,'Wawancara','Diskusi bersama rekruter dan pengumuman hasil akhir.',5),
(16,1,'Seleksi Administrasi','Pemeriksaan kesesuaian profil dan kelengkapan dokumen.',6),
(17,2,'Tes Teknis','Penilaian keterampilan sesuai posisi yang dilamar.',6),
(18,3,'Wawancara','Diskusi bersama rekruter dan pengumuman hasil akhir.',6);

INSERT INTO DOKUMEN (idDokumen,idAkun,namaAsli,namaInternal,ukuran) VALUES
(1,1,'CV_Nadia_Putri.pdf','demo_01.pdf',1751),
(2,1,'Transkrip_Nadia_Putri.pdf','demo_02.pdf',1763),
(3,2,'CV_Raka_Pratama.pdf','demo_03.pdf',1752),
(4,2,'Transkrip_Raka_Pratama.pdf','demo_04.pdf',1764),
(5,3,'NIB_Arunika_Digital.pdf','demo_05.pdf',1766),
(6,4,'NIB_Ruang_Karya_Studio.pdf','demo_06.pdf',1773),
(7,5,'NIB_Aksara_Data.pdf','demo_07.pdf',1760);

UPDATE MAHASISWA SET idCV=1 WHERE idMahasiswa=1;

UPDATE MAHASISWA SET idCV=3 WHERE idMahasiswa=2;

INSERT INTO DOKUMEN_PERUSAHAAN (id,idPerusahaan,idDokumen,namaDokumen,hasilPemeriksaan,catatan,idPemeriksa) VALUES
(1,1,5,'NIB','Sesuai','Data simulasi.',6),
(2,2,6,'NIB','Belum diperiksa','',NULL),
(3,3,7,'NIB','Sesuai','Data simulasi.',6);

INSERT INTO LAMARAN (idLamaran,idMahasiswa,idLowongan,dokumenPersyaratan,statusLamaran,idTahapSaatIni,administrasiLolos,suratPengantar) VALUES
(1,1,2,'{"CV":1,"Transkrip":2}','Diproses',5,1,'Saya ingin mengembangkan kemampuan membuat antarmuka web yang mudah digunakan.'),
(2,1,3,'{"CV":1,"Transkrip":2}','Wawancara',9,1,'Saya tertarik untuk belajar riset pengguna melalui kegiatan proyek.'),
(3,1,4,'{"CV":1,"Transkrip":2}','Diterima',12,1,'Saya ingin mempelajari pengolahan dan pemeliharaan kualitas data.'),
(4,1,5,'{"CV":1,"Transkrip":2}','Ditolak',13,0,'Saya ingin belajar proses pengujian aplikasi web.'),
(5,2,1,'{"CV":3,"Transkrip":4}','Lamaran Terkirim',1,0,'Saya tertarik mempelajari analisis data dan dashboard.');

UPDATE LAMARAN SET tanggalMelamar=DATE_SUB(NOW(),INTERVAL (8-idLamaran) DAY);

INSERT INTO HASIL_SELEKSI (idLamaran,idTahap,idLowongan,hasil,catatan,idPenilai) VALUES
(1,4,2,'Lolos','Dokumen lengkap. Silakan melanjutkan tes teknis.',3),
(2,7,3,'Lolos','Persyaratan administrasi sesuai.',3),
(2,8,3,'Lolos','Hasil tugas sesuai kriteria. Undangan wawancara sudah dijadwalkan.',3),
(3,10,4,'Lolos','Dokumen sesuai.',5),
(3,11,4,'Lolos','Hasil tes memenuhi kriteria.',5),
(3,12,4,'Lolos','Selamat, Anda diterima. Informasi selanjutnya melalui percakapan.',5),
(4,13,5,'Tidak Lolos','Profil belum memenuhi kebutuhan posisi saat ini.',5);

INSERT INTO JADWAL_SELEKSI(idLamaran,idTahap,idLowongan,judul,waktu,lokasi,tautan,catatan) VALUES (2,9,3,'Wawancara UI/UX',DATE_ADD(CURRENT_DATE,INTERVAL 2 DAY)+INTERVAL 10 HOUR,'Online','','Siapkan cerita tentang proses riset dan hasil tugas.');

INSERT INTO PESAN_CHAT (idLamaran,idPengirim,isi) VALUES
(2,3,'Halo Nadia, selamat lolos tes teknis. Jadwal wawancara sudah tersedia di kalender.'),
(2,1,'Terima kasih, Kak. Saya sudah melihat jadwal dan akan mempersiapkan diri.');

INSERT INTO NOTIFIKASI (idPenerima,judul,isi,tautan) VALUES
(1,'Undangan wawancara','Jadwal wawancara UI/UX tersedia. Periksa detailnya di kalender.','index.php?r=kalender'),
(1,'Hasil seleksi Data Engineer','Selamat! Lamaran Data Engineer Intern telah diterima.','index.php?r=lamaran%2Fdetail&id=3'),
(3,'Lamaran baru','Raka Pratama melamar Data Analyst Intern.','index.php?r=seleksi%2Fdetail&id=5'),
(6,'Perusahaan menunggu verifikasi','Ruang Karya Studio telah mengunggah dokumen legalitas.','index.php?r=perusahaan%2Fdetail&id=2');

INSERT INTO RIWAYAT_VERIFIKASI (idPerusahaan,idAdmin,hasil,catatan) VALUES
(1,6,'Terverifikasi','Persetujuan awal untuk data simulasi.'),
(3,6,'Terverifikasi','Persetujuan awal untuk data simulasi.');

COMMIT;
