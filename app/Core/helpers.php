<?php
declare(strict_types=1);
function e(mixed $s):string {
    return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
}
function url(string $route='dashboard',array $p=[]):string {
    return 'index.php?'.http_build_query(['r'=>$route]+$p);
}
function go(string $route,array $p=[]):never {
    header('Location: '.url($route,$p),true,303);
    exit;
}
function csrf():string {
    return '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">';
}
function post(string $key,string $default=''):string {
    $v=$_POST[$key]??$default;
    return is_scalar($v)?trim((string)$v):$default;
}
function query(string $key,string $default=''):string {
    $v=$_GET[$key]??$default;
    return is_scalar($v)?trim((string)$v):$default;
}
function number(string $key):int {
    return max(0,(int)($_POST[$key]??$_GET[$key]??0));
}
function flash(string $message,string $type='success'):void {
    $_SESSION['flash'][]=compact('message','type');
}
function fail(int $status,string $message):never {
    ErrorHandler::handle($status,$message);
}
function selected(mixed $a,mixed $b):string {
    return (string)$a===(string)$b?' selected':'';
}
function checked(bool $v):string {
    return $v?' checked':'';
}
function tanggal(?string $date,string $format='d M Y'):string {
    return $date?date($format,strtotime($date)):'—';
}
function rupiah(mixed $n):string {
    return (float)$n>0?'Rp '.number_format((float)$n,0,',','.'): 'Sesuai kesepakatan';
}
function initials(string $s):string {
    $w=preg_split('/\s+/',trim($s));
    return mb_strtoupper(mb_substr($w[0]??'',0,1).mb_substr($w[1]??'',0,1));
}
function json(array $data):string {
    return json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}
function statusClass(string $s):string {
    return match($s) {
        'Diterima','Terverifikasi','Lolos','Sesuai','Layak','Aktif'=>'green','Ditolak','Tidak Lolos','Tidak sesuai','Tidak Layak','Dihapus'=>'red','Wawancara','Diproses','Menunggu'=>'amber','Draf','Ditutup'=>'gray',default=>'blue'
    }
    ;
}
function badge(string $s):string {
    return '<span class="badge '.statusClass($s).'">'.e($s).'</span>';
}
function documentOptions():array {
    return ['CV'=>'Curriculum Vitae (CV / Resume)','Transkrip'=>'Transkrip Nilai Akademik','Portofolio'=>'Portofolio / Tautan Karya','Surat Rekomendasi'=>'Surat Rekomendasi / Pengantar'];
}
function jurusanOptions():array {
    return ['Sistem Informasi','Teknik Informatika','Teknologi Informasi','Teknik Komputer','Pendidikan Teknologi Informasi'];
}
function safeUrl(string $v):bool {
    return $v===''||(filter_var($v,FILTER_VALIDATE_URL)!==false&&in_array(strtolower(parse_url($v,PHP_URL_SCHEME)??''),['http','https'],true));
}
function icon(string $name):string {
    $paths=[ 'home'=>'<path d="m3 10 9-7 9 7v10H3Z"/><path d="M9 20v-7h6v7"/>', 'briefcase'=>'<rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V3h8v4M3 12c6 4 12 4 18 0M10 13h4"/>', 'file'=>'<path d="M6 3h9l4 4v14H6Z"/><path d="M14 3v5h5M9 12h7M9 16h7"/>', 'bell'=>'<path d="M5 17h14l-2-3V9a5 5 0 0 0-10 0v5ZM10 21h4"/>', 'user'=>'<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>', 'calendar'=>'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 2v6M17 2v6M3 11h18M7 15h3M14 15h3"/>', 'chat'=>'<path d="M4 4h16v12H9l-5 5Z"/><path d="M8 8h8M8 12h5"/>', 'search'=>'<circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/>', 'arrow'=>'<path d="M4 12h16m-6-6 6 6-6 6"/>', 'plus'=>'<path d="M12 4v16M4 12h16"/>', 'location'=>'<path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 0 1 14 0Z"/><circle cx="12" cy="10" r="2"/>', 'check'=>'<path d="m5 12 4 4L19 6"/>', 'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2"/>', 'logout'=>'<path d="M9 3H4v18h5M9 12h12m-5-5 5 5-5 5"/>', 'building'=>'<path d="M4 21V3h12v18M16 9h4v12M8 7h4M8 11h4M8 15h4M9 21v-3h3v3"/>', 'chart'=>'<path d="M4 3v18h17M8 17v-4M13 17V8M18 17V5"/>', 'shield'=>'<path d="m12 2 9 4v6c0 5-9 10-9 10S3 17 3 12V6Z"/><path d="m8 12 3 3 5-6"/>', 'download'=>'<path d="M12 3v12m-5-5 5 5 5-5M4 16v5h16v-5"/>', 'close'=>'<path d="m5 5 14 14M19 5 5 19"/>', ];
    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??$paths['file']).'</svg>';
}

function old(string $key,mixed $default=''):mixed {
    return $_POST[$key]??($GLOBALS['formValues'][$key]??$default);
}
