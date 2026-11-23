<?php
declare(strict_types=1);
final class Upload  {
    private static array $created=[];
    public static function checkpoint():int {return count(self::$created);}
    public static function rollbackFiles(int $checkpoint):void {
        foreach(array_slice(self::$created,$checkpoint) as $path)if(is_file($path))@unlink($path);
        self::$created=array_slice(self::$created,0,$checkpoint);
    }
    public static function simpan(array $file,int $idAkun):?int {
        if(($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return null;
        if(($file['error']??-1)!==UPLOAD_ERR_OK)throw new DomainException('Unggah gagal. Pilih PDF maksimal 5 MB dan kirim kembali.');
        if(!is_uploaded_file($file['tmp_name']))throw new DomainException('Berkas unggahan tidak sah.');
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if(strtolower(pathinfo($file['name'],PATHINFO_EXTENSION))!=='pdf'||$mime!=='application/pdf')throw new DomainException('Format dokumen harus PDF.');
        if($file['size']<=0||$file['size']>APP['upload_max_bytes'])throw new DomainException('Ukuran PDF maksimal 5 MB per berkas.');
        $name=bin2hex(random_bytes(24)).'.pdf';
        $path=ROOT.'/storage/uploads/'.$name;
        if(!move_uploaded_file($file['tmp_name'],$path))throw new RuntimeException('Tidak dapat menyimpan unggahan.');
        self::$created[]=$path;
        try {
            Database::query('INSERT INTO DOKUMEN(idAkun,namaAsli,namaInternal,ukuran,mime) VALUES (?,?,?,?,?)',[$idAkun,mb_substr(basename($file['name']),0,240),$name,$file['size'],$mime]);
            return (int)Database::get()->lastInsertId();
        }
        catch(Throwable $e) {
            @unlink($path);
            throw $e;
        }
    }
    public static function milik(int $id,int $idAkun):bool {
        return (bool)Database::value('SELECT 1 FROM DOKUMEN WHERE idDokumen=? AND idAkun=?',[$id,$idAkun]);
    }
}
