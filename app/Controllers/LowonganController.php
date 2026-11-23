<?php
declare(strict_types=1);
final class LowonganController  {
    private int $idPerusahaan;
    public function __construct() {
        $this->idPerusahaan=(int)(Auth::user()['idPerusahaan']??0);
    }
    public function cariLowongan(string $keyword):void {
        $u=Auth::require(['Mahasiswa','Rekruter']);
        $f=[];
        foreach(['jenis','semester','jurusan','lokasi','mode','status','urutan'] as $k)$f[$k]=query($k);
        if($u['role']==='Rekruter') {
            $rows=Lowongan::getDaftarLowongan($this->idPerusahaan);
            if($keyword!=='')$rows=array_values(array_filter($rows,fn($l)=>mb_stripos($l['namaLowongan'].' '.$l['deskripsi'],$keyword)!==false));
        }
        else $rows=$keyword===''?Lowongan::getSemuaLowongan():Lowongan::cariLowongan($keyword);
        $rows=Lowongan::saring($rows,$f);
        if($f['urutan']==='terlama')$rows=array_reverse($rows);
        DaftarLowonganPage::tampilkanDaftar($rows,$rows?'':'Lowongan tidak ditemukan');
    }
    public function getDetailLowongan(int $idLowongan):void {
        $u=Auth::require();
        $l=Lowongan::getLowonganById($idLowongan);
        if(!$l||$l['statusLowongan']==='Draf')fail(404,'Lowongan tidak tersedia.');
        if($u['role']==='Mahasiswa') {
            (new LamaranController())->cekStatusMelamar((int)$u['idMahasiswa'],$idLowongan);
            return;
        }
        if($u['role']==='Rekruter'&&(int)$l['idPerusahaan']!==$this->idPerusahaan)fail(403,'Lowongan bukan milik perusahaan Anda.');
        DetailLowonganPage::tampilkanDetail($l,'');
    }
    public function bukaFormLowongan(int $idPerusahaan):void {
        Auth::verified();
        if($idPerusahaan!==$this->idPerusahaan)fail(403,'Akses perusahaan ditolak.');
        $id=number('id');
        $state=$_SESSION['lowongan_form']??null;
        if(query('baru')==='1'||!$state||(int)$state['idLowongan']!==$id) {
            $l=$id?$this->milik($id):null;
            $data=$l?$l->toArray():['idLowongan'=>0,'namaLowongan'=>'','jenisPekerjaan'=>'Magang','modeKerja'=>'Hybrid','lokasiPenempatan'=>'','tingkatSemesterMinimal'=>3,'targetJurusan'=>['Sistem Informasi'],'deskripsi'=>'','kualifikasi'=>'','dokumenPersyaratan'=>['CV','Transkrip'],'batasLamaran'=>date('Y-m-d',strtotime('+30 days')),'kuota'=>3,'durasiBulan'=>3,'uangSaku'=>0,'konversiSKS'=>0,'statusLowongan'=>'Aktif'];
            $data['daftarTahap']=$id?array_map(fn($t)=>$t->toArray(),TahapSeleksi::getTahapByLowongan($id)):[['namaTahap'=>'Seleksi Administrasi','keterangan'=>'Pemeriksaan profil dan dokumen.'],['namaTahap'=>'Tes Teknis','keterangan'=>'Penilaian kemampuan sesuai posisi.'],['namaTahap'=>'Wawancara','keterangan'=>'Diskusi bersama rekruter.']];
            $_SESSION['lowongan_form']=$data;
        }
        FormPembuatanLowonganPage::tampilkanForm(Perusahaan::getPerusahaan($idPerusahaan));
    }
    public function prosesForm():void {
        Auth::verified();
        $action=post('aksiForm','publikasi');
        if(!in_array($action,['publikasi','tambah','batal','kembali','konfirmasi_batal'],true)&&!preg_match('/^hapus:\d+$/',$action))throw new DomainException('Tindakan lowongan tidak tersedia.');
        if($action==='konfirmasi_batal') {
            $this->batalkanPembuatan();
            return;
        }
        if($action==='kembali') {
            FormPembuatanLowonganPage::tampilkanForm(Perusahaan::getPerusahaan($this->idPerusahaan));
            return;
        }
        $id=number('idLowongan');
        if($id)$this->milik($id);
        $d=[];
        foreach(['namaLowongan','jenisPekerjaan','modeKerja','lokasiPenempatan','deskripsi','kualifikasi','batasLamaran'] as $k)$d[$k]=post($k);
        foreach(['tingkatSemesterMinimal','kuota','durasiBulan','uangSaku','konversiSKS'] as $k)$d[$k]=post($k,'0');
        $d['idLowongan']=$id;
        $d['targetJurusan']=array_values(array_intersect(jurusanOptions(),is_array($_POST['targetJurusan']??null)?$_POST['targetJurusan']:[]));
        $d['dokumenPersyaratan']=array_values(array_intersect(array_keys(documentOptions()),is_array($_POST['dokumenPersyaratan']??null)?$_POST['dokumenPersyaratan']:[]));
        $d['dokumenPersyaratan']=array_values(array_unique(['CV',...$d['dokumenPersyaratan']]));
        $d['statusLowongan']='Aktif';
        $d['batasLamaran']=$d['batasLamaran']?:null;
        $t=[];
        foreach(array_slice(is_array($_POST['tahap']??null)?$_POST['tahap']:[],0,12) as $row)if(is_array($row))$t[]=['namaTahap'=>trim((string)($row['namaTahap']??'')),'keterangan'=>trim((string)($row['keterangan']??''))];
        if($id&&(int)Database::value("SELECT COUNT(*) FROM LAMARAN WHERE idLowongan=? AND statusLamaran<>'Draf'",[$id]))$t=array_map(fn($x)=>$x->toArray(),TahapSeleksi::getTahapByLowongan($id));
        if($action==='tambah'&&count($t)<12)$t[]=['namaTahap'=>'','keterangan'=>''];
        if(str_starts_with($action,'hapus:')) {
            array_splice($t,(int)substr($action,6),1);
        }
        if($id&&(int)Database::value("SELECT COUNT(*) FROM LAMARAN WHERE idLowongan=? AND statusLamaran<>'Draf'",[$id]))$t=array_map(fn($x)=>$x->toArray(),TahapSeleksi::getTahapByLowongan($id));
        $d['daftarTahap']=$t;
        $_SESSION['lowongan_form']=$d;
        if($action==='batal') {
            FormPembuatanLowonganPage::tampilkanKonfirmasiBatal();
            return;
        }
        if($action==='tambah'||str_starts_with($action,'hapus:')) {
            FormPembuatanLowonganPage::tampilkanForm(Perusahaan::getPerusahaan($this->idPerusahaan));
            return;
        }
        $this->buatLowongan(new Lowongan($d),array_map(fn($r)=>new TahapSeleksi($r),$t));
    }
    public function buatLowongan(Lowongan $dataLowongan,array $daftarTahap):void {
        Auth::verified();
        if($dataLowongan['statusLowongan']!=='Aktif')throw new DomainException('Lowongan hanya dapat disimpan sebagai publikasi aktif.');
        $errors=$this->validasiData($dataLowongan,$daftarTahap);
        if($errors) {
            http_response_code(422);
            FormPembuatanLowonganPage::tampilkanErrorKolom($errors);
            return;
        }
        $id=(int)$dataLowongan['idLowongan'];
        Database::transaction(function()use($dataLowongan,$daftarTahap,$id) {
            // Kunci sama dengan jalur pendaftaran untuk mencegah roadmap berubah saat lamaran dikirim.
            if($id) {
                $old=Database::one('SELECT * FROM LOWONGAN WHERE idLowongan=? AND idPerusahaan=? FOR UPDATE',[$id,$this->idPerusahaan]);
                if(!$old)throw new DomainException('Lowongan tidak ditemukan.');
                $count=(int)Database::value("SELECT COUNT(*) FROM LAMARAN WHERE idLowongan=? AND statusLamaran<>'Draf'",[$id]);

            }
            $p=Database::one('SELECT statusVerifikasi FROM PERUSAHAAN WHERE idPerusahaan=? FOR UPDATE',[$this->idPerusahaan]);
            if($p['statusVerifikasi']!=='Terverifikasi')throw new DomainException('Verifikasi perusahaan belum disetujui.');
            $newId=Lowongan::simpan($dataLowongan,$this->idPerusahaan);
            TahapSeleksi::simpanRoadmap($newId,$daftarTahap);
        }
        );
        unset($_SESSION['lowongan_form']);
        flash('Lowongan berhasil dipublikasikan!');
        go('lowongan');
    }
    private function validasiData(Lowongan $d,array $t):array {
        $error=[];
        foreach(['namaLowongan'=>100,'lokasiPenempatan'=>100,'deskripsi'=>15000,'kualifikasi'=>10000] as $k=>$max) {
            if($k!=='kualifikasi'&&trim((string)$d[$k])==='')$error[$k]='Kolom ini wajib diisi.';
            if(mb_strlen((string)$d[$k])>$max)$error[$k]="Maksimal $max karakter.";
        }
        if(trim((string)$d['namaLowongan'])==='')$error['namaLowongan']='Nama lowongan wajib diisi.';
        if(!in_array($d['jenisPekerjaan'],['Magang','Paruh Waktu'],true))$error['jenisPekerjaan']='Pilih jenis pekerjaan yang valid.';
        if(!in_array($d['modeKerja'],['WFO','WFH','Hybrid'],true))$error['modeKerja']='Pilih mode kerja yang valid.';
        foreach(['tingkatSemesterMinimal'=>[1,14],'kuota'=>[1,10000],'durasiBulan'=>[1,36],'uangSaku'=>[0,1000000000],'konversiSKS'=>[0,24]] as $k=>[$lo,$hi])if(filter_var($d[$k],FILTER_VALIDATE_INT)===false||(int)$d[$k]<$lo||(int)$d[$k]>$hi)$error[$k]="Masukkan bilangan bulat $lo–$hi.";
        if(!$d['targetJurusan'])$error['targetJurusan']='Pilih minimal satu jurusan.';
        if(!$d['dokumenPersyaratan'])$error['dokumenPersyaratan']='Pilih minimal satu dokumen wajib.';
        if(!$t)$error['roadmapSeleksi']='Tambahkan minimal satu tahap seleksi.';
        foreach($t as $row)if(trim((string)$row['namaTahap'])===''||mb_strlen($row['namaTahap'])>100||mb_strlen((string)$row['keterangan'])>2000)$error['roadmapSeleksi']='Isi nama setiap tahap (maksimal 100 karakter) dan keterangan maksimal 2.000 karakter.';
        if($d['batasLamaran'] && (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$d['batasLamaran'])||date('Y-m-d',strtotime($d['batasLamaran']))!==$d['batasLamaran']||($d['batasLamaran']<date('Y-m-d'))))$error['batasLamaran']='Batas lamaran harus berupa tanggal hari ini atau sesudahnya.';
        return $error;
    }
    public function batalkanPembuatan():void {
        unset($_SESSION['lowongan_form']);
        flash('Pembuatan/perubahan lowongan dibatalkan.','info');
        go('lowongan');
    }
    public function ubahStatus():void {
        $l=$this->milik(number('id'));
        $action=post('aksi');
        if(!in_array($action,['tutup','hapus'],true))fail(422,'Aksi tidak valid.');
        if(!isset($_POST['konfirmasi'])) {
            View::render('confirm',['title'=>$action==='hapus'?'Hapus lowongan?':'Tutup lowongan?','message'=>'Lowongan tidak akan tampil pada pencarian mahasiswa. Riwayat lamaran tetap disimpan.','route'=>'lowongan/status','fields'=>['id'=>$l['idLowongan'],'aksi'=>$action],'back'=>url('lowongan')]);
            return;
        }
        if($action==='hapus')Database::query('UPDATE LOWONGAN SET dihapusPada=NOW(),statusLowongan=? WHERE idLowongan=? AND idPerusahaan=?',['Ditutup',$l['idLowongan'],$this->idPerusahaan]);
        else Database::query('UPDATE LOWONGAN SET statusLowongan=? WHERE idLowongan=? AND idPerusahaan=?',['Ditutup',$l['idLowongan'],$this->idPerusahaan]);
        flash('Lowongan berhasil '.($action==='hapus'?'dihapus.':'ditutup.'));
        go('lowongan');
    }
    private function milik(int $id):Lowongan {
        $l=Lowongan::getLowonganById($id);
        if(!$l||$l['dihapusPada']||$l['statusLowongan']==='Draf')fail(404,'Lowongan tidak ditemukan.');
        if((int)$l['idPerusahaan']!==Auth::perusahaanId())fail(403,'Lowongan bukan milik perusahaan Anda.');
        return $l;
    }
}
