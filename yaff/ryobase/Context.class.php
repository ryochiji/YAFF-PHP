<?php
class ContextException extends Exception{
}

/**
 * Context class
 * Encapsulates global context, without actually relying on global variables.
 * This helps with modularity and encapsulation, by allowing global contexts
 * to be set artificially.  For instance, it becomes possible to write unit
 * tests against "form input" without actually ever setting $_GET, $_POST
 * super globals.
 */
class Context {
    private $get_;
    private $post_;
    private $server_;
    private $path_;
    private $headers_;

    function __construct($get, $post, $server){
        $this->get_ = $get;
        $this->post_ = $post;
        $this->server_ = $server;
        $this->path_ = isset($server['PHP_SELF']) ? $server['PHP_SELF'] : '/';
        $this->headers_ = array();
        $this->headers_['Content-type'] = 'text/html; CHARSET=UTF-8';
        $this->content = array('title'=>'','description'=>'', 'js'=>'','css'=>'','main'=>'');
    }

    function getGet($key=false){
        if ($key) return isset($this->get_[$key]) ? $this->get_[$key] : null;
        return $this->get_;
    }

    function setGet($key,$val){
        $this->get_[$key] = $val;
        $_GET[$key] = $val;
        $_REQUEST[$key] = $val;
    }

    function getPost($key=false){
        if ($key) return isset($this->post_[$key]) ? $this->post_[$key] : null;
        return $this->post_;
    }

    function getRequest($key){
        if (isset($this->get_[$key])) return $this->getGet($key);
        else if (isset($this->post_[$key])) return $this->getPost($key);
        else return null;
    }

    function getPath(){
        return $this->path_;
    }

    function isFile(){
        if (preg_match('/(\.js|\.html|\.htm)$/',$this->path_)) return true;
        else return false;
    }

    function getComponent($path=false){
        if ($path===false) $path = $this->path_;
        $path = preg_replace('/^[\/]+/','',$path);
        if (empty($path)) return "index";
        $a = explode('/',$path);
        return empty($a[0]) ? "index" : $a[0];
    }

    function getComponentMethod($path=false){
        if ($path===false) $path = $this->path_;
        $path = preg_replace('/^[\/]+/','',$path);
        if (!empty($path)){ 
            $pos = strpos($path,'/');
            if ($pos!==false && ($pos+1)<strlen($path)){
                return substr($path,$pos+1);
            }
        }
        return "index";
    }


    /**
     * Set an HTTP header
     * @param $header String to the left of ':'
     * @param $value Value of header
     */
    function setHeader($header, $value){
        $this->headers_[$header] = $value;
    }

    /**
     * Set Content-type to text/plain
     */
    function isText(){
        $this->setHeader('Content-type','text/plain');
    }


    /**
     * Set HTTP status code.  Only necessary if NOT 200
     * @param $code HTTP status code (e.g. 400)
     * @param $string status string
     */
    function setHTTPStatus($code, $string){
        array_unshift($this->headers_, 'HTTP/1.0 '.$code.' '.$string);
    } 


    /**
     * Flush header.  Call before flushContent()
     */
    function flushHeaders(){
        foreach($this->headers_ as $key=>$val){
            if ($key) $val = $key.': '.$val;
            header($val);
        }
    }


    /**
     * Set/append content.  This is output that gets sent back to client
     * @param $content String to append/set
     * @param $buff Buffer to append/set (e.g. "main" vs "js" vs "css")
     */
    function setContent($content, $buff='main'){
        $this->content[$buff] = $content;
    }
    function appendContent($content, $buff='main'){
        if (!isset($this->content[$buff])) $this->content[$buff] = '';
        $this->content[$buff] .= $content;
    }
    function prependContent($content, $buff='main'){
        if (!isset($this->content[$buff])) $this->content[$buff] = '';
        $this->content[$buff] = $content.$this->content[$buff];
    }


    /**
     * Get all content buffers
     */
    function getContent(){
        return $this->content;
    }


    /**
     * Flush content.  Basically echo whatever was set using setContent
     * @param $buff Which buffer to flush (e.g. "main","js","css")
     */
    function flushContent($buff='main'){
        echo $this->content['main'];
    }

    
    /**
     * Return path to components directory
     */
    private function getCompDir(){
        static $dir;

        if (!isset($dir)){
            if (defined('YAFF_COMPONENTS_DIR')){
                $dir = YAFF_COMPONENTS_DIR;
            }else if (getenv('YAFF_COMPONENTS_DIR')){
                $dir = getenv('YAFF_COMPONENTS_DIR');
            }else{
                $dir = './components/';
            }
        }

        if ($dir[strlen($dir)-1]!='/') $dir.='/';

        return $dir;
        
    }


    /**
     * Load and execute a component/method
     * @param $comp Component name  [optional]
     * @param $method Method name [optional]
     * @return output returned by the component/method
     */
    function loadComponent($comp=false, $method=false){
        if (!$comp) $comp = $this->getComponent();
        if (!$method) $method = $this->getComponentMethod();

        $compFile = $this->getCompDir().$comp.'.component.php';
        if (!@include($compFile)){
            throw new ContextException("Component '$comp' not found", 404);
        }

        $compName = $comp.'Component';
        if (method_exists($compName, $method)){
            $r = call_user_func(array($compName,$method), $this);
        }else{
            $r = call_user_func(array($compName,'router'), $this, $method);
        }
        return $r; 
    }


    /**
     * Helper function wrap an entire page.  Actual implementation should be in
     * a component.  By default, PageComponent is used, but can be overridden
     * by a constant (define()) called YAFF_PAGEWRAPPER or a environment variable
     * of the same name.  Value should be component name (e.g. "mypage")
     * @return None.  Pleaces output in content buffer 'main'
     */
    function wrapPage(){
        $comp = 'page';
        if (defined('YAFF_PAGEWRAPPER')) $comp = YAFF_PAGEWRAPPER;
        else if (getenv('YAFF_PAGEWRAPPER')) $comp = getenv('YAFF_PAGEWRAPPER');
        $this->content['main'] = $this->loadComponent($comp,'wrap');
    }


    /**
     * Redirect and exit
     */
    function redirect($url){
        header("Location: $url");
        exit;
    }
    
}

//end of context.class.php
