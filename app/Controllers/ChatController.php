<?php
declare(strict_types=1);
final class ChatController  {
    private function percakapan(int $id):Lamaran {
        $u=Auth::require(['Mahasiswa','Rekruter']);
        $a=Lamaran::izin($id);
        if(!$a['administrasiLolos'])fail(403,'Chat tersedia setelah lamaran lolos tahap administrasi.');
        if($a['perusahaanDihapus'])fail(403,'Percakapan ditutup karena perusahaan tidak aktif.');
        return $a;
    }
    public function tampilkanChat():void {
        $u=Auth::require(['Mahasiswa','Rekruter']);
        $rows=$u['role']==='Mahasiswa'?Lamaran::getLamaranByMahasiswa((int)$u['idMahasiswa']):Lamaran::daftarPendaftar((int)$u['idPerusahaan']);
        $id=number('id');
        $a=$id?$this->percakapan($id):null;
        $pesan=$a?Database::all('SELECT p.*,u.namaPengguna,u.role FROM PESAN_CHAT p JOIN AKUN u ON u.idAkun=p.idPengirim WHERE p.idLamaran=? ORDER BY p.idPesan',[$id]):[];
        View::render('chat',['title'=>'Percakapan','percakapan'=>$rows,'lamaran'=>$a,'pesan'=>$pesan]);
    }
    public function kirimPesan():void {
        $id=number('idLamaran');
        $a=$this->percakapan($id);
        $u=Auth::user();
        $text=post('isi');
        if(!$text||mb_strlen($text)>4000)throw new DomainException('Pesan wajib diisi, maksimal 4.000 karakter.');
        Database::transaction(function()use($id,$a,$u,$text) {
            Database::query('INSERT INTO PESAN_CHAT(idLamaran,idPengirim,isi) VALUES (?,?,?)',[$id,$u['idAkun'],$text]);
            if($u['role']==='Mahasiswa')Notifikasi::perusahaan((int)$a['idPerusahaan'],'Pesan baru',$u['namaPengguna'].' mengirim pesan.',url('chat',['id'=>$id]));
            else Notifikasi::kirimNotifikasi(Akun::mahasiswa((int)$a['idMahasiswa']),'Pesan dari rekruter',$a['namaPerusahaan'].' mengirim pesan.',url('chat',['id'=>$id]));
        }
        );
        go('chat',['id'=>$id]);
    }
}
