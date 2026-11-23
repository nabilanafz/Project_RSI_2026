<?php
declare(strict_types=1);
final class ProfilController  {
    public function tampilkanProfil():void {
        $u=Auth::require();
        $profil=match($u['role']) {
            'Mahasiswa'=>Mahasiswa::getMahasiswa((int)$u['idMahasiswa']),'Rekruter'=>Perusahaan::getPerusahaan((int)$u['idPerusahaan']),default=>$u
        }
        ;
        View::render('profil',['title'=>'Profil','profil'=>$profil,'errors'=>[],'legalitas'=>$u['role']==='Rekruter'?Perusahaan::dokumen((int)$u['idPerusahaan']):[]]);
    }
    public function simpanProfil():void {
        $u=Auth::require(['Rekruter']);
        $old=Perusahaan::getPerusahaan((int)$u['idPerusahaan']);$d=[];$errors=[];
        foreach(['namaPerusahaan','emailPerusahaan','alamat','bidangUsaha','kontak','deskripsi','website'] as $k)$d[$k]=post($k);
        foreach(['namaPerusahaan'=>100,'alamat'=>2000,'bidangUsaha'=>100,'kontak'=>30,'deskripsi'=>5000,'website'=>250] as $k=>$max)if(mb_strlen($d[$k])>$max||(in_array($k,['namaPerusahaan','alamat'],true)&&!$d[$k]))$errors[]="$k wajib diisi atau melebihi batas panjang.";
        if(!filter_var($d['emailPerusahaan'],FILTER_VALIDATE_EMAIL)||mb_strlen($d['emailPerusahaan'])>100||!safeUrl($d['website']))$errors[]='Email atau website perusahaan tidak valid.';
        if(Database::value('SELECT 1 FROM PERUSAHAAN WHERE emailPerusahaan=? AND idPerusahaan<>?',[$d['emailPerusahaan'],$u['idPerusahaan']]))$errors[]='Email perusahaan sudah digunakan.';
        $hasFile=($_FILES['legalitas']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE;
        $type=post('namaDokumen');
        if($hasFile&&!in_array($type,['NIB','Akta Pendirian','Izin Usaha'],true))$errors[]='Pilih jenis dokumen legalitas.';
        if(!$errors) {
            $checkpoint=Upload::checkpoint();
            try {
                Database::transaction(function()use($u,$d,$hasFile,$type) {
                    $p=Database::one('SELECT * FROM PERUSAHAAN WHERE idPerusahaan=? FOR UPDATE',[$u['idPerusahaan']]);
                    if(!$p||$p['dihapusPada'])throw new DomainException('Perusahaan tidak aktif.');
                    $file=$hasFile?Upload::simpan($_FILES['legalitas'],(int)$u['idAkun']):null;
                    Perusahaan::simpanProfil((int)$u['idPerusahaan'],$d);
                    if($file) {
                        Database::query('DELETE FROM DOKUMEN_PERUSAHAAN WHERE idPerusahaan=? AND namaDokumen=?',[$u['idPerusahaan'],$type]);
                        Database::query('INSERT INTO DOKUMEN_PERUSAHAAN(idPerusahaan,idDokumen,namaDokumen) VALUES (?,?,?)',[$u['idPerusahaan'],$file,$type]);
                        Database::query("UPDATE PERUSAHAAN SET statusVerifikasi='Menunggu',catatanVerifikasi='Dokumen baru menunggu pemeriksaan ulang.' WHERE idPerusahaan=?",[$u['idPerusahaan']]);
                        foreach(Database::all("SELECT idAkun FROM AKUN WHERE role='Admin' AND aktif=1") as $admin)Notifikasi::kirimNotifikasi((int)$admin['idAkun'],'Dokumen perusahaan baru',$d['namaPerusahaan'].' mengunggah '.$type,url('perusahaan/detail',['id'=>$u['idPerusahaan']]));
                    }
                });
                flash($hasFile?'Profil dan legalitas tersimpan. Perusahaan menunggu pemeriksaan admin.':'Profil perusahaan diperbarui.');go('profil');
            } catch(Throwable $e) {
                Upload::rollbackFiles($checkpoint);
                if(!$e instanceof DomainException)throw $e;
                $errors[]=$e->getMessage();
            }
        }
        http_response_code(422);
        View::render('profil',['title'=>'Profil','profil'=>new Perusahaan(array_replace($old->toArray(),$d)),'errors'=>$errors,'legalitas'=>Perusahaan::dokumen((int)$u['idPerusahaan'])]);
    }
    public function unggahLegalitas():void {
        // Legacy method retained for diagram compatibility; the single profile form owns saving.
        $this->simpanProfil();
    }
}
