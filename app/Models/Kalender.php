<?php
declare(strict_types=1);
final class Kalender  {
    public static function jadwalLamaran(int $id):array {
        return Database::all('SELECT j.*,t.namaTahap FROM JADWAL_SELEKSI j JOIN `TAHAP SELEKSI` t ON t.idTahap=j.idTahap WHERE j.idLamaran=? ORDER BY j.waktu',[$id]);
    }
    public static function jadwalPengguna(array $u):array {
        if($u['role']==='Admin')return [];
        $where=$u['role']==='Mahasiswa'?'a.idMahasiswa=?':'l.idPerusahaan=?';
        $val=$u['role']==='Mahasiswa'?$u['idMahasiswa']:$u['idPerusahaan'];
        return Database::all("SELECT j.*,l.namaLowongan,m.namaMahasiswa FROM JADWAL_SELEKSI j JOIN LAMARAN a ON a.idLamaran=j.idLamaran JOIN LOWONGAN l ON l.idLowongan=a.idLowongan JOIN MAHASISWA m ON m.idMahasiswa=a.idMahasiswa WHERE $where AND a.statusLamaran NOT IN ('Ditolak','Diterima') AND l.dihapusPada IS NULL ORDER BY j.waktu",[$val]);
    }
    public static function kirimPengingatJatuhTempo():int {
        return Database::transaction(function() {
            $rows=Database::all('SELECT * FROM PENGINGAT WHERE diingatkanPada IS NULL AND waktu<=NOW() FOR UPDATE');
            foreach($rows as $r) {
                Notifikasi::kirimNotifikasi((int)$r['idAkun'],'Pengingat: '.$r['judul'],$r['catatan']?:'Jadwal pengingat telah tiba.',url('kalender'));
                Database::query('UPDATE PENGINGAT SET diingatkanPada=NOW() WHERE idPengingat=?',[$r['idPengingat']]);
            }
            return count($rows);
        }
        );
    }
}
