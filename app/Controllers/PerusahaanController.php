<?php
declare(strict_types=1);
final class PerusahaanController  {
    public function getDaftarPerusahaan():void {
        Auth::require(['Admin']);
        $rows=Perusahaan::getDaftarPerusahaan(query('status'));
        $q=query('keyword');
        if($q!=='')$rows=array_values(array_filter($rows,fn($p)=>mb_stripos($p['namaPerusahaan'].' '.$p['alamat'],$q)!==false));
        View::render('perusahaan/index',['title'=>'Perusahaan','perusahaan'=>$rows]);
    }
    private function perusahaan(int $id):Perusahaan {
        Auth::require(['Admin']);
        $p=Perusahaan::getPerusahaan($id);
        if(!$p||$p['dihapusPada'])fail(404,'Perusahaan tidak ditemukan.');
        return $p;
    }
    public function getDetailPerusahaan(int $id):void {
        $p=$this->perusahaan($id);
        $data=['title'=>'Detail Perusahaan','perusahaan'=>$p,'dokumen'=>Perusahaan::dokumen($id),'riwayat'=>Database::all('SELECT v.*,a.namaPengguna FROM RIWAYAT_VERIFIKASI v JOIN AKUN a ON a.idAkun=v.idAdmin WHERE v.idPerusahaan=? ORDER BY v.idVerifikasi DESC',[$id]),'lowongan'=>Lowongan::getDaftarLowongan($id)];
        $data['versi']=self::versi($p->toArray(),$data['dokumen']);
        $cache=$data;$cache['perusahaan']=$p->toArray();$cache['lowongan']=array_map(fn($x)=>$x->toArray(),$data['lowongan']);
        $_SESSION['company_review'][$id]=['user'=>Auth::user(),'data'=>$cache];
        // Limit cached authorized pages rather than growing the session indefinitely.
        if(count($_SESSION['company_review'])>5)unset($_SESSION['company_review'][array_key_first($_SESSION['company_review'])]);
        View::render('perusahaan/detail',$data);
    }
    private static function versi(array $company,array $documents):string {
        return hash('sha256',json([$company,array_map(fn($d)=>[$d['id'],$d['idDokumen'],$d['hasilPemeriksaan']],$documents)]));
    }
    public function periksaDokumen():void {
        $this->perusahaan(number('idPerusahaan'));
        throw new DomainException('Pemeriksaan dokumen disimpan bersama keputusan Terima atau Tolak.');
    }
    public function verifikasiPerusahaan():void {
        $this->periksaDokumen();
    }
    public function putuskanPerusahaan():void {
        $id=number('idPerusahaan');$this->perusahaan($id);
        $choice=post('keputusan');$note=post('alasan');
        if(!in_array($choice,['setujui','tolak'],true)||mb_strlen($note)>3000)throw new DomainException('Keputusan atau catatan tidak valid.');
        if($choice==='tolak'&&!$note)throw new DomainException('Alasan penolakan wajib diisi.');
        $reviews=$_POST['pemeriksaan']??[];
        if(!is_array($reviews))throw new DomainException('Data pemeriksaan tidak valid.');
        Database::transaction(function()use($id,$choice,$note,$reviews) {
            $p=Database::one('SELECT * FROM PERUSAHAAN WHERE idPerusahaan=? FOR UPDATE',[$id]);
            if(!$p||$p['dihapusPada'])throw new DomainException('Perusahaan tidak tersedia.');
            if($p['statusVerifikasi']!=='Menunggu')throw new DomainException('Perusahaan tidak sedang menunggu peninjauan. Muat ulang halaman untuk melihat status terbaru.');
            $docs=Perusahaan::dokumen($id);
            if(!hash_equals(self::versi($p,$docs),post('versi')))throw new DomainException('Data atau dokumen perusahaan telah berubah. Periksa data terbaru sebelum mengambil keputusan.');
            $ids=array_map(fn($d)=>(string)$d['id'],$docs);
            foreach($reviews as $docId=>$review)if(!in_array((string)$docId,$ids,true)||!is_array($review))throw new DomainException('Dokumen pemeriksaan tidak sesuai perusahaan.');
            if($choice==='setujui'&&!$docs)throw new DomainException('Dokumen legalitas wajib tersedia sebelum perusahaan diterima.');
            foreach($docs as $doc) {
                $review=$reviews[$doc['id']]??[];$result=$review['hasil']??'';$catatan=$review['catatan']??'';
                if(!is_string($result)||!is_string($catatan)||mb_strlen($catatan)>2000)throw new DomainException('Catatan pemeriksaan dokumen tidak valid.');
                if($result!==''&&!in_array($result,['Sesuai','Tidak sesuai','Bermasalah'],true))throw new DomainException('Hasil pemeriksaan dokumen tidak valid.');
                if($choice==='setujui'&&$result!=='Sesuai')throw new DomainException('Periksa semua dokumen dan pilih Sesuai sebelum menerima perusahaan.');
                if($result!=='')Database::query('UPDATE DOKUMEN_PERUSAHAAN SET hasilPemeriksaan=?,catatan=?,idPemeriksa=? WHERE id=? AND idPerusahaan=?',[$result,$catatan,Auth::user()['idAkun'],$doc['id'],$id]);
            }
            $status=$choice==='setujui'?'Terverifikasi':'Ditolak';
            $message=$note?:'Perusahaan memenuhi persyaratan verifikasi.';
            Database::query('INSERT INTO RIWAYAT_VERIFIKASI(idPerusahaan,idAdmin,hasil,catatan) VALUES (?,?,?,?)',[$id,Auth::user()['idAkun'],$choice==='setujui'?'Layak':'Tidak Layak',$message]);
            Database::query('UPDATE PERUSAHAAN SET statusVerifikasi=?,catatanVerifikasi=? WHERE idPerusahaan=?',[$status,$message,$id]);
            Database::query('INSERT INTO RIWAYAT_VERIFIKASI(idPerusahaan,idAdmin,hasil,catatan) VALUES (?,?,?,?)',[$id,Auth::user()['idAkun'],$status,$message]);
            Notifikasi::perusahaan($id,'Hasil verifikasi: '.$status,$message,url('profil'));
        });
        unset($_SESSION['form_values']['perusahaan/detail|'.$id]);
        flash('Keputusan perusahaan tersimpan dan rekruter menerima notifikasi.');go('perusahaan/detail',['id'=>$id]);
    }
    public function hapusPerusahaan():void {
        $id=number('idPerusahaan');
        $p=$this->perusahaan($id);
        if($p['statusVerifikasi']!=='Terverifikasi')throw new DomainException('Penghapusan karena pelanggaran berlaku untuk perusahaan terverifikasi.');
        $note=post('alasan');
        if(!isset($_POST['konfirmasi'])) {
            View::render('confirm',['title'=>'Hapus perusahaan ini?','message'=>$p['namaPerusahaan'].' dan seluruh lowongannya akan disembunyikan. Akun rekruter dinonaktifkan. Riwayat lamaran mahasiswa dipertahankan.','route'=>'perusahaan/hapus','fields'=>['idPerusahaan'=>$id,'alasan'=>$note],'back'=>url('perusahaan/detail',['id'=>$id])]);
            return;
        }
        if(!$note||mb_strlen($note)>3000)throw new DomainException('Tuliskan alasan/bukti pelanggaran sebelum menghapus perusahaan.');
        Database::transaction(function()use($id,$note) {
            $locked=Database::one('SELECT statusVerifikasi,dihapusPada FROM PERUSAHAAN WHERE idPerusahaan=? FOR UPDATE',[$id]);
            if(!$locked||$locked['dihapusPada']||$locked['statusVerifikasi']!=='Terverifikasi')throw new DomainException('Status perusahaan telah berubah.');
            Database::query("UPDATE PERUSAHAAN SET dihapusPada=NOW(),statusVerifikasi='Dihapus',catatanVerifikasi=? WHERE idPerusahaan=?",[$note,$id]);
            Database::query("UPDATE LOWONGAN SET dihapusPada=NOW(),statusLowongan='Ditutup' WHERE idPerusahaan=?",[$id]);
            Database::query('UPDATE AKUN SET aktif=0 WHERE idPerusahaan=?',[$id]);
            Database::query('INSERT INTO RIWAYAT_VERIFIKASI(idPerusahaan,idAdmin,hasil,catatan) VALUES (?,?,?,?)',[$id,Auth::user()['idAkun'],'Dihapus',$note]);
            $students=Database::all("SELECT DISTINCT a.idMahasiswa FROM LAMARAN a JOIN LOWONGAN l ON l.idLowongan=a.idLowongan WHERE l.idPerusahaan=? AND a.statusLamaran<>'Draf'",[$id]);
            foreach($students as $m)Notifikasi::kirimNotifikasi(Akun::mahasiswa((int)$m['idMahasiswa']),'Perusahaan tidak lagi tersedia','Lowongan perusahaan terkait dinonaktifkan oleh admin. Riwayat lamaran tetap dapat diakses.',url('lamaran'));
        }
        );
        flash('Perusahaan dan lowongannya dihapus dari daftar aktif.');
        go('perusahaan');
    }
}
