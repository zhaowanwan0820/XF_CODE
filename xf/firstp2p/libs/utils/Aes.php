<?php
namespace libs\utils;

use api\conf\ConstDefine;

/**
 * AES对称加密封装 (包括base64)
 * 算法: AES128位
 * 模式: ECB
 * 填充: PKCS5Padding
 *
 * @author 全恒壮 <quanhengzhuang@ucfgroup.com>
 */

class Aes
{

    /**
     * 加密 (Aes + base64)
     */
    public static function encode($input, $key)
    {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $input = self::pkcs5Padding($input, $size);

        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);

        return $data;
    }

    /**
     * 对base64加密后的字符串中'+','/','='的处理
     */
    public function urlEncode($data)
    {
        return rtrim(strtr($data, '+/', '-_'), '=');
    }

    /**
     * PKCS5方式填充
     */
    private static function pkcs5Padding($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text.str_repeat(chr($pad), $pad);
    }

    /**
     * 对base64解密字符串前的'+','/','='的处理
     */
    public function urlDecode($data)
    {
        if (strlen($data) % 4 == 0) {
            return strtr($data, '-_', '+/');
        }
        return str_pad(strtr($data, '-_', '+/'), strlen($data) + 4 - strlen($data) % 4, '=', STR_PAD_RIGHT);
    }

    /**
     * 解密 (base64 + Aes)
     */
    public static function decode($data, $key)
    {
        $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($data), MCRYPT_MODE_ECB);

        $dec_s = strlen($decrypted);
        $padding = ord($decrypted[$dec_s - 1]);
        $decrypted = substr($decrypted, 0, -$padding);

        return $decrypted;
    }

    public static function strToHexNew($string)
    {
        $hex="";
        for   ($i=0;$i<strlen($string);$i++) {

            $hex .= sprintf("%02x", ord($string[$i]));
        }
        $hex=strtoupper($hex);
        return   $hex;
    }

    public static function strToHex($string)
    {
        $hex="";
        for   ($i=0;$i<strlen($string);$i++)
            $hex.=dechex(ord($string[$i]));
        $hex=strtoupper($hex);
        return   $hex;
    }

    public static function hexToStr($hex)
    {
        $string="";
        for   ($i=0;$i<strlen($hex)-1;$i+=2)
            $string.=chr(hexdec($hex[$i].$hex[$i+1]));
        return   $string;
    }

    /**
     * signature
     * 签名函数
     *
     * @param mixed $data
     * @static
     * @access public
     * @return void
     */
    public static  function signature($datas, $key = '') {
        $query_string = self::buildString($datas);
        return md5($query_string."&key=".$key);
    }

    /**
     * buildString
     * 排序生成query_string
     *
     * @param mixed $datas
     * @static
     * @access public
     * @return void
     */
    public static function buildString($datas) {
        ksort($datas);
        $tmp = array();
        foreach ($datas as $k => $v) {
            if ("" !== $v && null != $v && ($k !== 'signature' && $k !== 'sign')) {
                $tmp[] = $k ."=". $v;
            }
        }
        return implode($tmp, '&');
    }

    /**
     * validate
     * 验证数据的正确性
     *
     * @param mixed $datas
     * @static
     * @access public
     * @return void
     */
    public static function validate($datas) {
        $signature = self::signature($datas);
        if ((isset($datas['sign']) && $datas['sign'] == $signature) || (isset($datas['signature']) && $datas['signature'] != $signature)) {
            return false;
        }
        return true;
    }


    /**
     * 为userid加密 (for JFB)
     */
    public static function encryptForJFB($data) {
        $key = 'ebe0234ba12c3e78';
        $en_data = self::encode($data, $key);
        $en_data = substr($en_data, 0, -1);
        $result = str_replace(array('/', '+', '='), array('@', '#', '_'), $en_data);
        return $result;
    }
    /**
     * 为userid加密 (for JFB)
     */
    public static function decryptForJFB($data) {
        $key = 'ebe0234ba12c3e78';
        $data .= '=';
        $en_data = str_replace(array('@', '#', '_'), array('/', '+', '='), $data);
        $result = self::decode($en_data, $key);
        return $result;
    }

    /**
     * 数字转为字符串
     * @author zhang ruoshi
     * @param int $integer
     * @return string
     */
    public static function encryptForDeal($integer) {
        $integer = intval($integer);
        $base = 'hF9mY0514DXqAkQHpyZUPiTExgN36IMJBnjura8LWwfbvtVKdOz7loSRsGeCc2';
        $length = strlen($base);
        $out = "";
        while($integer > $length - 1)
        {
            $out = $base[intval(fmod($integer, $length))] . $out;
            $integer = intval(floor( $integer / $length ));
        }
        return $base[$integer] . $out;
    }

    /**
     * 字符串转为数字
     * @author zhang ruoshi
     * @param string $string
     * @return int
     */
    public static function decryptForDeal($string) {
        $base = 'hF9mY0514DXqAkQHpyZUPiTExgN36IMJBnjura8LWwfbvtVKdOz7loSRsGeCc2';
        $length = strlen($base);
        $size = strlen($string) - 1;
        $string = str_split($string);
        $out = strpos($base, array_pop($string));
        foreach($string as $i => $char)
        {
            $out += strpos($base, $char) * pow($length, $size - $i);
        }
        return $out;
    }

    /**
     * encryptHex
     *
     * @param mixed $input
     * @param mixed $key
     * @static
     * @access public
     * @return void
     */
    public static function encryptHex($input, $key) {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $input = self::pkcs5Padding($input, $size);

        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = bin2hex($data);

        return $data;
    }

    /**
     * decryptHex
     *
     * @param mixed $data
     * @param mixed $key
     * @static
     * @access public
     * @return void
     */
    public static function decryptHex($data, $key) {

        if (function_exists('hex2bin')) {
            $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, hex2bin($data), MCRYPT_MODE_ECB);
        } else {
            $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, self::_hex2bin($data), MCRYPT_MODE_ECB);
        }

        $dec_s = strlen($decrypted);
        $padding = ord($decrypted[$dec_s - 1]);
        $decrypted = substr($decrypted, 0, -$padding);

        return $decrypted;
    }

    /**
     * _hex2bin
     *
     * @param mixed $hex
     * @static
     * @access public
     * @return void
     */
    public static function _hex2bin($hex = false){
        $ret = $hex !== false && preg_match('/^[0-9a-fA-F]+$/i', $hex) ? pack("H*", $hex) : false;
        return $ret;
    }
}
