<?php
declare(strict_types=1);
final class DokumenController  {
    public function lihatDokumen(int $id):void {
        $u=Auth::require();
        $d=Database::one('SELECT * FROM DOKUMEN WHERE idDokumen=?',[$id]);
        if(!$d)fail(404,'Dokumen tidak ditemukan.');
        $allow=(int)$d['idAkun']===(int)$u['idAkun'];
        if(!$allow&&$u['role']==='Admin')$allow=(bool)Database::value('SELECT 1 FROM DOKUMEN_PERUSAHAAN WHERE idDokumen=?',[$id]);
        if(!$allow&&in_array($u['role'],['Rekruter','Admin'],true)) {
            $rows=$u['role']==='Rekruter'?Lamaran::daftarPendaftar((int)$u['idPerusahaan']):array_map(fn($r)=>new Lamaran($r),Database::all("SELECT * FROM LAMARAN WHERE statusLamaran<>'Draf'"));
            foreach($rows as $a)if(in_array($id,array_map('intval',$a['dokumenPersyaratan']),true)) {
                $allow=true;
                break;
            }
        }
        if(!$allow)fail(403,'Anda tidak memiliki izin membuka dokumen ini.');
        $path=ROOT.'/storage/uploads/'.basename($d['namaInternal']);
        if(!is_file($path))fail(404,'Berkas tidak tersedia pada penyimpanan server.');
        header('Content-Type: application/pdf');
        header('Content-Length: '.filesize($path));
        header('Content-Disposition: '.(query('unduh')==='1'?'attachment':'inline').'; filename="document-'.$id.'.pdf"; filename*=UTF-8\'\''.rawurlencode($d['namaAsli']));
        readfile($path);
        exit;
    }
}
