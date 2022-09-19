<?php
namespace libs\utils;

class Token {
    public static function genToken() {
        $day = date('ymdHis');
        $rand = substr(microtime(),2,5) . mt_rand(100,999);
        return $day . $rand;
    }
}
