<?php
declare(strict_types=1);
final class LamaranController  {
    private int $idMahasiswa;
    public function __construct() {
        $this->idMahasiswa=(int)(Auth::user()['idMahasiswa']??0);
    }
    public function cekStatusMelamar(int $idMahasiswa,int $idLowongan):void {
        $this->pemilik($idMahasiswa);
        $l=$this->tersedia($idLowongan);
        $pesan=Lamaran::cekSudahMelamar($idMahasiswa,$idLowongan)?'Anda sudah mendaftar pada lowongan ini':'';
        DetailLowonganPage::tampilkanDetail($l,$pesan);
    }
    public function bukaFormLamaran(int $idLowongan):void {
        $this->pemilik($this->idMahasiswa);
        $l=$this->tersedia($idLowongan);
        if(Lamaran::cekSudahMelamar($this->idMahasiswa,$idLowongan)) {
            DetailLowonganPage::tampilkanDetail($l,'Anda sudah mendaftar pada lowongan ini');
            return;
        }
        FormPendaftaranLowonganPage::tampilkanFormPendaftaran($l);
    }
    public function prosesForm():void {
        $this->pemilik($this->idMahasiswa);
        $id=number('idLowongan');$l=$this->tersedia($id);
        $action=post('aksiForm','kirim');
        if($action==='batal') { unset($_SESSION['lamaran_uploads'][$id]);go('lowongan/detail',['id'=>$id]); }
        if(!in_array($action,['kirim','unggah_otomatis','hapus_berkas'],true))throw new DomainException('Tindakan formulir tidak tersedia.');
        if(Lamaran::cekSudahMelamar($this->idMahasiswa,$id))throw new DomainException('Anda sudah mendaftar pada lowongan ini.');
        $state=self::unggahanSementara($id);$errors=[];$u=Auth::user();
        if(isset($_FILES['dokumen']['name']['Portofolio']))throw new DomainException('Portofolio harus berupa tautan http/https, bukan unggahan dokumen.');
        if($action==='hapus_berkas') {
            $key=post('jenisDokumen');
            if(!isset(documentOptions()[$key])||$key==='Portofolio')throw new DomainException('Jenis dokumen tidak valid.');
            unset($state['dokumenPersyaratan'][$key]);
        }
        foreach(array_keys(documentOptions()) as $key) {
            if($key==='Portofolio'||!isset($_FILES['dokumen']['name'][$key]))continue;
            $f=[];foreach(['name','type','tmp_name','error','size'] as $k)$f[$k]=$_FILES['dokumen'][$k][$key]??null;
            try { $new=Upload::simpan($f,(int)$u['idAkun']);if($new)$state['dokumenPersyaratan'][$key]=$new; }
            catch(DomainException $e) { $errors[]=$key.': '.$e->getMessage(); }
        }
        if($action==='kirim') {
            $state['portofolio']=post('portofolio');$state['suratPengantar']=post('suratPengantar');
            if(!safeUrl($state['portofolio'])||mb_strlen($state['portofolio'])>250)$errors[]='Portofolio harus berupa tautan http/https maksimal 250 karakter.';
            if(str_word_count($state['suratPengantar'])>500||mb_strlen($state['suratPengantar'])>6000)$errors[]='Surat pengantar maksimal 500 kata / 6.000 karakter.';
        }
        $state['diperbarui']=time();$_SESSION['lamaran_uploads'][$id]=$state;
        if($errors) {http_response_code(422);FormPendaftaranLowonganPage::tampilkanPesan('Dokumen belum dapat diproses',$errors);return;}
        if($action!=='kirim') {FormPendaftaranLowonganPage::tampilkanFormPendaftaran($l);return;}
        $this->mendaftarLowongan($this->idMahasiswa,$id,$state['dokumenPersyaratan']);
    }
    public static function unggahanSementara(int $idLowongan):array {
        $state=$_SESSION['lamaran_uploads'][$idLowongan]??[];
        if(($state['diperbarui']??0)<time()-7200)$state=[];
        return $state+['dokumenPersyaratan'=>[],'portofolio'=>'','suratPengantar'=>''];
    }
    public function mendaftarLowongan(int $idMahasiswa,int $idLowongan,array $dokumen):void {
        $this->pemilik($idMahasiswa);
        if(Lamaran::cekSudahMelamar($idMahasiswa,$idLowongan)) {
            FormPendaftaranLowonganPage::tampilkanPesan('Anda telah Melamar Lowongan ini',[]);
            return;
        }
        $state=self::unggahanSementara($idLowongan);
        if($dokumen!==$state['dokumenPersyaratan'])throw new DomainException('Gunakan dokumen yang diunggah pada formulir lamaran ini.');
        $errors=$this->validasiLamaran($idMahasiswa,$idLowongan,$dokumen);
        if($errors) {
            http_response_code(422);
            FormPendaftaranLowonganPage::tampilkanPesan('Berkas Wajib Belum Lengkap',$errors);
            return;
        }
        $id=Database::transaction(function()use($idMahasiswa,$idLowongan,$dokumen) {
            Database::query('SELECT idLowongan FROM LOWONGAN WHERE idLowongan=? FOR UPDATE',[$idLowongan]);
            $l=$this->tersedia($idLowongan);
            if(Lamaran::cekSudahMelamar($idMahasiswa,$idLowongan))throw new DomainException('Anda sudah mendaftar pada lowongan ini.');
            $state=self::unggahanSementara($idLowongan);
            $t=TahapSeleksi::getTahapByLowongan($idLowongan);
            if(!$t)throw new DomainException('Roadmap seleksi belum tersedia.');
            $data=['portofolio'=>$state['portofolio'],'suratPengantar'=>$state['suratPengantar']];
            // Convert only a pre-upgrade, unsent row; never create a new draft.
            $legacy=Database::value("SELECT idLamaran FROM LAMARAN WHERE idMahasiswa=? AND idLowongan=? AND statusLamaran='Draf'",[$idMahasiswa,$idLowongan]);
            if($legacy)$data['idLamaran']=(int)$legacy;
            $data=array_replace($data,['idMahasiswa'=>$idMahasiswa,'idLowongan'=>$idLowongan,'dokumenPersyaratan'=>$dokumen,'statusLamaran'=>'Lamaran Terkirim','tanggalMelamar'=>date('Y-m-d H:i:s'),'idTahapSaatIni'=>$t[0]['idTahap']]);
            $a=new Lamaran($data);
            Lamaran::simpanLamaran($a);
            $id=(int)$a['idLamaran'];
            Notifikasi::perusahaan((int)$l['idPerusahaan'],'Lamaran baru',Mahasiswa::getMahasiswa($idMahasiswa)['namaMahasiswa'].' melamar '.$l['namaLowongan'],url('seleksi/detail',['id'=>$id]));
            Notifikasi::kirimNotifikasi((int)Auth::user()['idAkun'],'Lamaran terkirim','Lamaran untuk '.$l['namaLowongan'].' berhasil diterima.',url('lamaran/detail',['id'=>$id]));
            return $id;
        }
        );
        unset($_SESSION['lamaran_uploads'][$idLowongan]);
        flash('Lamaran Terkirim. Pantau perkembangannya di halaman ini.');
        go('lamaran/detail',['id'=>$id]);
    }
    private function validasiLamaran(int $idMahasiswa,int $idLowongan,array $dokumen):array {
        $l=$this->tersedia($idLowongan);
        $m=Mahasiswa::getMahasiswa($idMahasiswa);
        $state=self::unggahanSementara($idLowongan);
        $errors=[];
        foreach(array_unique(['CV',...$l['dokumenPersyaratan']]) as $key) {
            if($key==='Portofolio') {
                if(!$state['portofolio']||!safeUrl($state['portofolio']))$errors[]='Tautan portofolio http/https wajib diisi.';
                continue;
            }
            if(empty($dokumen[$key])||!Upload::milik((int)$dokumen[$key],(int)Auth::user()['idAkun']))$errors[]=documentOptions()[$key]??$key;
        }
        if(post('persetujuan')!=='1')$errors[]='Centang pernyataan kebenaran data dan dokumen.';
        if((int)$m['semester']<(int)$l['tingkatSemesterMinimal'])$errors[]='Semester aktif belum memenuhi syarat minimal lowongan.';
        if(!in_array($m['jurusan'],$l['targetJurusan'],true))$errors[]='Program studi belum sesuai target jurusan lowongan.';

        return $errors;
    }
    public function getRiwayatLamaran(int $idMahasiswa):void {
        $this->pemilik($idMahasiswa);
        $rows=Lamaran::getLamaranByMahasiswa($idMahasiswa);
        $status=query('status');
        if($status!=='')$rows=array_values(array_filter($rows,fn($r)=>$r['statusLamaran']===$status));
        RiwayatLamaranPage::tampilkanDaftarLamaran($rows,$rows?'':'Riwayat lamaran masih kosong');
    }
    public function getDetailLamaran(int $id):void {
        $a=Lamaran::izin($id);
        View::render('lamaran/detail',['title'=>'Detail Lamaran','lamaran'=>$a,'tahapan'=>TahapSeleksi::getTahapByLowongan((int)$a['idLowongan']),'hasil'=>Lamaran::hasil($id),'jadwal'=>Kalender::jadwalLamaran($id),'dokumenInfo'=>Dokumen::informasi($a['dokumenPersyaratan']),'lowonganLamaran'=>Lowongan::getLowonganById((int)$a['idLowongan'])]);
    }
    private function pemilik(int $id):void {
        if(Auth::mahasiswaId()!==$id)fail(403,'Identitas mahasiswa tidak sesuai sesi.');
    }
    private function tersedia(int $id):Lowongan {
        $l=Lowongan::getLowonganById($id);
        if(!$l||!$l->tersedia())fail(404,'Lowongan tidak tersedia, telah ditutup, atau melewati batas lamaran.');
        return $l;
    }
}
