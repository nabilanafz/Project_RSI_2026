<?php
declare(strict_types=1);
final class KalenderController  {
    public function tampilkanKalender():void {
        $u=Auth::require(['Mahasiswa','Rekruter']);
        $month=query('bulan',date('Y-m'));
        if(!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month))$month=date('Y-m');
        $reminders=Database::all('SELECT * FROM PENGINGAT WHERE idAkun=? ORDER BY waktu',[$u['idAkun']]);
        $edit=null;
        if(number('edit')) {
            $edit=Database::one('SELECT * FROM PENGINGAT WHERE idPengingat=? AND idAkun=?',[number('edit'),$u['idAkun']]);
            if(!$edit)fail(404,'Pengingat tidak ditemukan.');
        }
        View::render('kalender',['title'=>'Kalender Seleksi','bulan'=>$month,'jadwal'=>Kalender::jadwalPengguna($u),'pengingat'=>$reminders,'edit'=>$edit,'daftarLamaran'=>$u['role']==='Mahasiswa'?Lamaran::getLamaranByMahasiswa((int)$u['idMahasiswa']):Lamaran::daftarPendaftar((int)$u['idPerusahaan'])]);
    }
    public function simpanPengingat():void {
        $u=Auth::require(['Mahasiswa','Rekruter']);
        $id=number('idPengingat');
        $title=post('judul');
        $time=post('waktu');
        $note=post('catatan');
        $app=number('idLamaran');
        if($app)Lamaran::izin($app);
        if(!$title||mb_strlen($title)>150||mb_strlen($note)>2000||!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/',$time)||strtotime($time)===false)throw new DomainException('Isi judul dan waktu pengingat yang valid.');
        if($id&&!Database::value('SELECT 1 FROM PENGINGAT WHERE idPengingat=? AND idAkun=?',[$id,$u['idAkun']]))fail(403,'Pengingat bukan milik Anda.');
        $v=[$title,str_replace('T',' ',$time).':00',$note,$app?:null];
        if($id)Database::query('UPDATE PENGINGAT SET judul=?,waktu=?,catatan=?,idLamaran=?,diingatkanPada=NULL WHERE idPengingat=? AND idAkun=?',[...$v,$id,$u['idAkun']]);
        else Database::query('INSERT INTO PENGINGAT(judul,waktu,catatan,idLamaran,idAkun) VALUES (?,?,?,?,?)',[...$v,$u['idAkun']]);
        flash('Pengingat berhasil disimpan.');
        go('kalender',['bulan'=>substr($time,0,7)]);
    }
    public function hapusPengingat():void {
        $u=Auth::require(['Mahasiswa','Rekruter']);
        $id=number('id');
        if(!Database::value('SELECT 1 FROM PENGINGAT WHERE idPengingat=? AND idAkun=?',[$id,$u['idAkun']]))fail(404,'Pengingat tidak ditemukan.');
        if(!isset($_POST['konfirmasi'])) {
            View::render('confirm',['title'=>'Hapus pengingat?','message'=>'Jadwal seleksi dari rekruter tetap tersimpan. Hanya pengingat pribadi ini yang dihapus.','route'=>'kalender/hapus','fields'=>['id'=>$id],'back'=>url('kalender')]);
            return;
        }
        Database::query('DELETE FROM PENGINGAT WHERE idPengingat=? AND idAkun=?',[$id,$u['idAkun']]);
        flash('Pengingat dihapus.');
        go('kalender');
    }
}
