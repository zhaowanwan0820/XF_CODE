<?php
/**
 *@author longbo
 */
namespace core\service\partner\common;
use \libs\utils\Aes;

class Tools
{
    /**
     * 为id加密,需和openapi的open_id保持一致
     */
    public static function encryptID($data, $key = 'ebe0234ba12c3e78')
    {
        $en_data = Aes::encode(strval($data), $key);
        $en_data = substr($en_data, 0, -1);
        $result = str_replace(array('/', '+', '='), array('@', '#', '_'), $en_data);
        return bin2hex($result);
    }
    /**
     * decode id
     */
    public static function decryptID($data, $key = 'ebe0234ba12c3e78')
    {
        $result = Aes::decode(hex2bin($data), $key);
        return $result;
    }


}
