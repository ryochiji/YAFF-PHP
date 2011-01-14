<?php
//if (!empty($_GET['__errors'])){
    ini_set('error_reporting', E_ALL|E_PARSE);
    ini_set('display_errors', 1);
//}
require('./ryobase/utils.inc.php');
require('./ryobase/Context.class.php');
require('./ryobase/Component.class.php');
require('./ryobase/Alerts.class.php');
$env = getenv('YAFF_ENV');
if ($config=getenv('YAFF_CONFIG')){
    @include($config);
}else if (!empty($env)){
    @include('./includes/configs_'.$env.'.inc.php');
}else{
    @include('./includes/configs.inc.php');
}

$ctx = new Context($_GET, $_POST, $_SERVER);

if ($ctx->isFile()){
    readfile('.'.$ctx->getPath());
    exit;
}else if (preg_match('/\.php$/',$ctx->getPath())){
    include('.'.$ctx->getPath());
    exit;
}else{
    ob_start();
    try{
        $r = $ctx->loadComponent(); 
    }catch(ContextException $ce){
        if ($ce->getCode()==404){
            $ctx->setHTTPStatus(404, "Not found");
            $ctx->setContent($ce->getMessage());
        }else{
            $ctx->setHTTPStatus(500, "Internal error in PHP software");
            $ctx->setContent($ce->getMessage());
            
        }
    }
    $output = ob_get_clean();
    if (!empty($output)) $ctx->appendContent($output);
    if (!empty($r)) $ctx->appendContent($r);
}

$ctx->flushHeaders();
$ctx->flushContent();
?>
