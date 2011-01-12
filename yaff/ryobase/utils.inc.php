<?php

class Utils{
    static $css_ = '';

    static function bufferCSS($css){
        self::$css_ .= $css; 
    }

    static function getBufferedCSS(){
        return self::$css_;
    }

    static function at($data,$tpl){ return self::applyTemplate($data,$tpl); }
    static function applyTemplate($data, $tpl) {
        if (!is_array($data)) $data = array();
        extract($data);
        $r = include('templates/'.$tpl.'.tpl.php');
        if (is_array($r)){
            self::bufferCSS($r['css']);
            $r = $r['html'];
        }
        return $r;
    }

    static function a($href,$inside,$class='',$id='',$nofollow=null){
        $out = array();
        $out[] = '<a href="'.htmlspecialchars($href).'"';
        if ($class) $out[] = 'class="'.$class.'"';
        if ($id) $out[] = 'id="'.$id.'"';
        if ($nofollow){
            $out[] = 'rel="nofollow"'; 
        }else if ($nofollow===null && $href[0]!='/'){
            $out[] = 'rel="nofollow"';
        }
        $out[] = '>'.$inside.'</a>';
        return implode(' ',$out);
    }

    static function getGlobalCSS() {
        return include('templates/global.css.php');
    }

    static function getRelTime($ts){
        $diff = time() - $ts;
        if ($diff<60){
            return 'a minute ago';
        }else if ($diff<3600){
            return round($diff/60).' minutes ago';
        }else if ($diff<86400){
            return round($diff/3600).' hours ago';
        }else if ($diff<172800){
            return 'a day ago';
        }else if ($diff<5200000){
            return round($diff/86400).' days ago'; 
        }else{
            return 'months ago';
        }
    }

    static function linkify($text, $class=''){
        return nl2br(ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/\)]","<a href=\"\\0\" class=\"$class\">\\0</a>", $text));
    }

    static function extractEmailAds($s){
        $pattern = '/([a-zA-Z0-9_\+\.]+\@[a-zA-Z0-9_]+\.[a-zA-Z0-9_\.]+)/';
        preg_match_all($pattern,$s, $matches);
        if (empty($matches) || empty($matches[0])) return array();
        return $matches[1];
    }

    static function printResult($test, $r, $debugData=null){
        if (DEBUG){
            echo "\nDEBUG info for $test:\n";
            var_dump($debugData);
        }
        $test = str_pad($test,100,'.');
        $color = $r ? '#0f0' : '#f00';
        $res = $r ? 'ok' : 'FAIL';
        echo "<pre>$test<span style=\"color:$color;\">$res</span></pre>\n";
    }

    static function truncate($str, $len){
        if (strlen($str)<$len) return $str;
        $str = substr($str, 0, $len);
        return $str.'...';
    }

    static function log($message){
        $msg = date('YmdHis').' '.$_SERVER['PHP_SELF'].' '.$message."\n";
        error_log($msg, 3, '/tmp/rtt.log');
    }

    static function logException($e){
        $msg = $e->getMessage()."\n";
        $msg.= $e->getTraceAsString()."\n";
        Utils::log($msg);
    }

    static function getBaseURL(){
        return 'http://'.$_SERVER['HTTP_HOST'];
    }

    static function getURL($path='/'){
        return self::getBaseURL().$path;
    }

    static function getToken($str,$u=false){
        $txt = 'asdlkfj;lkjerbdoije';
        $txt.= '|'.$str;
        if ($u) $txt.= '|'.$u;
        return substr(md5($txt), 0, 8);
    }

    static function checkToken($tok, $str, $u=false){
        return ($tok == self::getToken($str,$u));
    }

    static function multiArraySort($a, $k, $desc=true){
        $index = array();
        foreach($a as $key=>$val){
            $index[$key] = $val[$k];
        }

        if ($desc) arsort($index);
        else asort($index);

        $out = array();
        foreach($index as $key=>$blargh){
            $out[$key] = $a[$key];
        }
        return $out;
    }
}

?>
