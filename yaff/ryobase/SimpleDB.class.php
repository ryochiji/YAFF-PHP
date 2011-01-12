<?php
require('AWSBase.class.php');

class SimpleDBException extends Exception {
    //todo: parse XML message
}

class SimpleDB extends AWSBase {
    private $domain;

    function __construct($domain, $debug=false) {
        $this->domain = $domain;
        $this->debug = $debug;
    }

    function setDomain($domain) {
        $this->domain = $domain;
    }


    /**
     * CreateDomain
     * @param domain Name of domain to create
     */
    function createDomain($domain) {
        $p = array('Action'=>'CreateDomain', 'DomainName'=>$domain);
        return $this->request($p);
    }


    /**
     * DeleteDomain
     * @param domain Name of domain to create
     */
    function deleteDomain($domain) {
        $p = array('Action'=>'DeleteDomain', 'DomainName'=>$domain);
        return $this->request($p);
    }


    /**
     * ListDomains - returns array of domain names
     */
    function listDomains() {
        $p = array('Action'=>'ListDomains');
        $r = $this->request($p);
        $xml = simplexml_load_string($r);
        $out = array();
        foreach($xml->ListDomainsResult->DomainName as $dn) {
            $out[] = (string)$dn;
        }
        return $out;
    }


    /**
     * assoc2Params
     * @param assoc Associative array of key values to store.  Accepts multiple values per key.
     * @param prefix [optional] prepended to param names
     */
    private function assoc2Params($assoc, $prefix='', $replace=true){
        $p = array();
        $i = 0;
        foreach($assoc as $k=>$varr){
            if (!is_array($varr)) $varr = array($varr);
            foreach($varr as $v){
                $p[$prefix.'Attribute.'.$i.'.Name'] = $k;
                $p[$prefix.'Attribute.'.$i.'.Value'] = $v;
                if ($replace) $p[$prefix.'Attribute.'.$i.'.Replace'] = 'true';
                $i++;
            }
        }
        return $p;
    }


    /**
     * PutAttributesAssoc 
     * @param itemName - ItemName (unique identifier for record)
     * @param assoc - Associative array of key values to store.  Accepts multiple values per key.
     * @param replace - [optional] defaults to true
     */
    function putAttributesAssoc($itemName, $assoc, $replace=true){
        $p = array('Action'=>'PutAttributes');
        $p['DomainName'] = $this->domain;
        $p['ItemName'] = $itemName;
        $p = array_merge($p, $this->assoc2Params($assoc));
        return $this->request($p);
    }

    
    /**
     * Set single attribute
     * @param $itemName - ItemName (unique row identifier)
     * @param $attrName - Attribute name
     * @param $value    - Value
     * @param $replace  - [optional] default true, set to false to add new value
     */
    function setSingleAttr($itemName, $attrName, $value, $replace=true){
        $a = array(array($attrName,$value,$replace));
        $this->putAttributes($itemName, $a);
    }


    /**
     * batchPutAttributesAssoc
     * @param assocs - Associative array of associative arrays, each containing a 
     *                 record (associative array of key value pairs)
     * @param replace - [optional] defaults to true
     */
    function batchPutAttributesAssoc($assocs, $replace=true){
        $p = array('Action'=>'BatchPutAttributes');
        $p['DomainName']= $this->domain;
        $i = 0;
        foreach($assocs as $k=>$a){
            $p['Item.'.$i.'.ItemName'] = $k;
            $p = array_merge($p, $this->assoc2Params($a, 'Item.'.$i.'.'));
            $i++;
        }
        return $this->request($p);
    }


    /**
     * PutAttributes 
     * @param name  - ItemName (unique identifier)
     * @param attrs - numerically indexed arrray of arrays.  elem 0 of inner 
     *                array is name, 1 is value, 2 is replace
     *                e.g. array(0=>array('Name','Bob',true), 1=>array('Age', '029', true));
     */
    function putAttributes($itemName, $attrs) {
        $p = array('Action'=>'PutAttributes');
        $p['DomainName'] = $this->domain;
        $p['ItemName'] = $itemName;
        foreach($attrs as $i=>$a){
            if (!is_int($i)) continue;
            if (!is_array($a) || count($a)<2 || count($a)>3) continue;
            $p['Attribute.'.$i.'.Name'] = $a[0];
            $p['Attribute.'.$i.'.Value'] = $a[1];
            if (isset($a[2]) && $a[2]) {
                $p['Attribute.'.$i.'.Replace'] = 'true';
            }
        }
        return $this->request($p);
    }


    /**
     * DeleteAttributes
     * @param name  - ItemName (unique identifier)
     * @param attrs - [optional] numerically indexed arrray of arrays.  elem 0 of inner array is name, 1 is value
     *                e.g. array(0=>array('Name','Bob'));
     */
    function deleteAttributes($itemName, $attrs=array()) {
        $p = array('Action'=>'DeleteAttributes');
        $p['DomainName'] = $this->domain;
        $p['ItemName'] = $itemName;
        foreach($attrs as $i=>$a){
            if (!is_int($i)) continue;
            if (!is_array($a)) continue;
            $p['Attribute.'.$i.'.Name'] = $a[0];
            if (isset($a[1])) $p['Attribute.'.$i.'.Value'] = $a[1];
        }
        return $this->request($p);
    }

    /**
     * GetAttributes
     * @param itemName
     * @param attrName [optional] 
     */
    function getAttributes($itemName, $attrName=false) {
        $p = array('Action'=>'GetAttributes');
        $p['DomainName'] = $this->domain;
        $p['ItemName'] = $itemName;
        if ($attrName) $p['AttributeName'] = $attrName;
        $xml = simplexml_load_string($this->request($p));
        $out = array();
        foreach($xml->GetAttributesResult->Attribute as $a){
            $k = (string)$a->Name;
            $v = (string)$a->Value;
            if (!isset($out[$k])) $out[$k] = $v;
            else if (is_array($out[$k])) $out[$k][] = $v;
            else $out[$k] = array($out[$k],$v);
        }
        return $out;
    }


    /**
     * Run query
     * @param query 
     */
    function query($query){
        $p = array('Action'=>'Select');
        $p['SelectExpression'] = $query;
        $rawxml = $this->request($p);
        if ($this->debug) echo $rawxml;
        $xml = simplexml_load_string($rawxml);
        $out = array();
        foreach($xml->SelectResult->Item as $i){
            $name = (string)$i->Name;
            $a = array();
            foreach($i->Attribute as $attr){
                $key = (string)$attr->Name;
                $value = (string)$attr->Value;
                if (isset($a[$key])){
                    if (!is_array($a[$key])){
                        $a[$key] = array($a[$key]); 
                    }
                    $a[$key][] = $value;
                 }else{
                    $a[$key] = $value;
                }
            }
            $out[$name] = $a;
        }
        return $out;
    }

    
    function singleAttrQuery($attr, $vals){
        if (empty($this->domain)) throw new SimpleDBException("Domain not set");
        $query = "SELECT * FROM ".$this->domain." WHERE ";
        if (!is_array($vals)){
            $query .= "$attr='$vals'";
        }else{
            $a = array();
            foreach($vals as $val){
                $a[] = "$attr='$val'"; 
            }
            $query .= implode(' OR ',$a);
        }
        return $this->query($query);
    }


    function request($params){

        $host = 'sdb.amazonaws.com';
        $uri = '/';
        return $this->signedRequest($host, $uri, $params);

        //-----------------------------------------------------
        // DEPRECATED LEGACY CODE BELOW
        //-----------------------------------------------------
        //TODO: automatically switch to POST for large requests
        $params['AWSAccessKeyId'] = AWS_ACCESS_KEY;
        $params['Timestamp'] = gmdate('Y-m-d\TH:i:s').'Z'; 
        $params['SignatureVersion'] = 2;
        $params['SignatureMethod'] = 'HmacSHA1';
        $params['Version'] = '2009-04-15';
        $host = 'sdb.amazonaws.com';
        $uri = '/';
        $pstr = $this->parr2pstr($params);
        $message = "GET\n$host\n$uri\n$pstr";
        //$sig = $this->hex2b64($this->hmacsha1(AWS_SECRET_KEY, $message));
        $sig = base64_encode(hash_hmac('sha1', $message, AWS_SECRET_KEY, true));
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

        //make sure we get status code 200
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($code!=200) {
            echo $r."\n";
            print_r($params);
            echo "message=".$message."\n";
            echo "url=".$url."\n";
            throw new SimpleDBException($r, $code);
        }

        return $r;
    }


}

//end of SimpleDB.class.php
