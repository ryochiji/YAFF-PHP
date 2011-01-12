<?php

class AWSBaseException extends Exception {

}


class AWSBase {
    private $log_;

    function __construct(){
        $this->log_ = array();
    }

    public function getLog(){
        return $this->log_;
    }

    private function encodeParam($str){
        $str = urlencode($str);
        return str_replace('+', '%20', str_replace('%7E','~',$str));
    }


    /**
     * Implode array of params into single '&' separated string, with appropriate encoding
     */
    function parr2pstr($params) {
        ksort($params);
        $parr= array();
        foreach($params as $k=>$v){
            $parr[] = $k.'='.$this->encodeParam($v);
        }
        return implode('&', $parr);
    }



    /**
     * Found on the interwebs.  Original author unknown.
     */
    private function hmacsha1($key,$data) {
        $blocksize=64;
        $hashfunc='sha1';
        if (strlen($key)>$blocksize)
            $key=pack('H*', $hashfunc($key));
        $key=str_pad($key,$blocksize,chr(0x00));
        $ipad=str_repeat(chr(0x36),$blocksize);
        $opad=str_repeat(chr(0x5c),$blocksize);
        $hmac = pack(
                    'H*',$hashfunc(
                        ($key^$opad).pack(
                            'H*',$hashfunc(
                                ($key^$ipad).$data
                            )
                        )
                    )
                );
        return bin2hex($hmac);
    }
 

    /*
     * Used to encode a field for Amazon Auth
     * (taken from the Amazon S3 PHP example library)
     */
    function hex2b64($str) {
        $raw = '';
        for ($i=0; $i < strlen($str); $i+=2)
        {
            $raw .= chr(hexdec(substr($str, $i, 2)));
        }
        return base64_encode($raw);
    }



    /**
     * Make a signed request
     */
    function signedRequest($host, $uri, $params, $akey=AWS_ACCESS_KEY, $skey=AWS_SECRET_KEY){
        //TODO: automatically switch to POST for large requests
        $params['AWSAccessKeyId'] = $akey;
        $params['Timestamp'] = gmdate('Y-m-d\TH:i:s').'Z'; 
        $params['SignatureVersion'] = 2;
        $params['SignatureMethod'] = 'HmacSHA1';
        $params['Version'] = '2009-04-15';
        ksort($params);
        $pstr = $this->parr2pstr($params);
        $message = "GET\n$host\n$uri\n$pstr";
        //$sig = $this->hex2b64($this->hmacsha1(AWS_SECRET_KEY, $message));
        $sig = base64_encode(hash_hmac('sha1', $message, $skey, true));
        $url = 'https://'.$host.$uri.'?'.$pstr.'&Signature='.$this->encodeParam($sig);

        if ($this->debug){ 
            print_r($params);
            echo $message."\n";
            echo $sig."\n";
            echo $url."\n";
        }

        //make curl request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $r = curl_exec($ch);

        //log
        $log = array('url'=>$url, 'time'=>curl_getinfo($ch, CURLINFO_TOTAL_TIME));
        $this->log_[] = $log;

        //make sure we get status code 200
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($code!=200) {
            echo $r."\n";
            throw new AWSBaseException($r, $code);
        }

        return $r;
    }

}
