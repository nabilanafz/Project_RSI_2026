<?php
declare(strict_types=1);
final class Dokumen  {
    public static function informasi(array $ids):array  {
        $ids=array_values(array_unique(array_filter(array_map('intval',$ids))));
        if(!$ids)return [];
        $rows=Database::all('SELECT idDokumen,namaAsli,ukuran FROM DOKUMEN WHERE idDokumen IN ('.implode(',',array_fill(0,count($ids),'?')).')',$ids);
        $out=[];
        foreach($rows as $r)$out[(int)$r['idDokumen']]=$r;
        return $out;
    }
}
