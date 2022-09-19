<?php
class QRCodeDataUtil {

    // 安全码
    static $safe_code = 'cbb5741dfe7abf64eeea5a15056140d2';

    // 加密
    static function encode($string) {
        if(!$string){
            return '';
        }
        $string['time'] = time();
        $string = json_encode($string);
        return DesUtil::encrypt($string,self::$safe_code);
    }

    // 解密
    static function decode($string) {
        if(!$string){
            return '';
        }
        $string = DesUtil::decrypt($string,self::$safe_code);
        return json_decode($string,true);
    }
}
?>