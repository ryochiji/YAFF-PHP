<?php

class Alerts{

    static function set($name, $params){
        $a = self::getAll();
        $a[$name] = $params;
        self::save($a);
    }

    static function get($name){
        $a = self::getAll();
        return isset($a[$name]) ? $a[$name] : false;
    }

    static function getClear($name){
        $r = self::get($name);
        self::clear($name);
        return $r;
    }

    static function getAll(){
        if (isset($_COOKIE['alerts'])){
            return json_decode(stripslashes($_COOKIE['alerts']),1);
        }else{
             return array();
        }
    }

    static function clear($name){
        $a = self::getAll();
        if (isset($a[$name])) unset($a[$name]);
        self::save($a);
    }

    static function save($alerts){
        setcookie('alerts',json_encode($alerts),0,'/');
    }

}

