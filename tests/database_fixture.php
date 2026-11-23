<?php
// CLI-only fixture access for disposable integration databases; never expose through a web route.
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('RSI_TEST_ALLOW_MUTATIONS')!=='1'){fwrite(STDERR,"Explicit disposable-test opt-in required.\n");exit(2);}
require dirname(__DIR__).'/app/bootstrap.php';
try {
    $request=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR);
    if(($request['mode']??'query')==='exec'){$result=Database::query($request['sql'],$request['params']??[])->rowCount();}
    else {$result=Database::all($request['sql'],$request['params']??[]);}
    echo json_encode($result,JSON_THROW_ON_ERROR);
} catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
