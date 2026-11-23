<?php
declare(strict_types=1);
final class NotifikasiController  {
    public function tampilkanNotifikasi():void {
        $u=Auth::require();
        View::render('notifikasi',['title'=>'Notifikasi','notifikasi'=>Notifikasi::daftar((int)$u['idAkun'])]);
    }
    public function tandaiDibaca():void {
        $u=Auth::require();
        $id=number('id');
        if(!$id)Database::query('UPDATE NOTIFIKASI SET sudahDibaca=1 WHERE idPenerima=?',[$u['idAkun']]);
        else {
            $n=Database::one('SELECT * FROM NOTIFIKASI WHERE idNotifikasi=? AND idPenerima=?',[$id,$u['idAkun']]);
            if(!$n)fail(404,'Notifikasi tidak ditemukan.');
            Database::query('UPDATE NOTIFIKASI SET sudahDibaca=1 WHERE idNotifikasi=? AND idPenerima=?',[$id,$u['idAkun']]);
            if(str_starts_with($n['tautan'],'index.php?')) {
                header('Location: '.$n['tautan'],true,303);
                exit;
            }
        }
        go('notifikasi');
    }
}
