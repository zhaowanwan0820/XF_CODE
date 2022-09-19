<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2014-1-22 9:47:17
 * @encode UTF-8编码
 */
class P_Crypt_Sca extends P_Crypt_Abstract {

    const IDX_CHARACTERS = 'characters';
    const IDX_KEY_LENGTH = 'key_length';
    const KEY_LENGTH = 64;
    const RC4_KEY_LENGTH = 4;

    private static $_characters = array(
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '+', '/',
    );
    private $_method = self::CIPHER_RC4;

    public function __construct($need_key = false, $key_length = self::KEY_LENGTH, $method = self::CIPHER_RC4) {
        $config[self::IDX_CHARACTERS] = self::$_characters;
        $config[self::IDX_KEY_LENGTH] = $key_length > 0 ? $key_length : self::KEY_LENGTH;
        $this->_method = in_array($method, $this->_cipher_method) ? $method : self::CIPHER_RC4;
        if ($need_key) {
            parent::__construct($config);
        }
    }

    protected function _decrypt($data, $key = false) {
        if (function_exists('openssl_encrypt') && function_exists('openssl_decrypt')) {
            $ret = @openssl_decrypt($data, $this->_method, $key);
        } else {
            $ret = self::_rc4($data, __FUNCTION__, $key);
        }
        return $ret;
    }

    protected function _encrypt($data, $key = false) {
        if (function_exists('openssl_encrypt') && function_exists('openssl_decrypt')) {
            $ret = @openssl_encrypt($data, $this->_method, $key);
        } else {
            $ret = self::_rc4($data, __FUNCTION__, $key);
        }
        return $ret;
    }

    protected function _generate_key($config) {
        $key = "";
        if (function_exists('openssl_random_pseudo_bytes')) {
            $key = base64_encode(openssl_random_pseudo_bytes($config[self::IDX_KEY_LENGTH]));
            $key = substr($key, 0, $config[self::IDX_KEY_LENGTH]);
        } else {
            $characters = $config[self::IDX_CHARACTERS];
            mt_srand((double) microtime() * 1000000);
            shuffle($characters);
            $len = count($characters);
            for ($i = 0; $i < $config[self::IDX_KEY_LENGTH]; $i++) {
                $key .= $characters[mt_rand(0, $len)];
            }
        }
        return $key;
    }

    public function get_error() {
        
    }

    private static function _rc4($data, $op, $key) {
        if (empty($key)) {
            return $data;
        }
        $key = md5($key);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = self::RC4_KEY_LENGTH ? ($op == self::DECRYPT ? substr($data, 0, self::RC4_KEY_LENGTH) : substr(md5(microtime()), -self::RC4_KEY_LENGTH)) : '';
        $crypt_key = $keya . md5($keya . $keyc);
        $key_length = strlen($crypt_key);
        $data = ($op == self::DECRYPT) ? base64_decode(substr($data, self::RC4_KEY_LENGTH)) : substr(md5($data . $keyb), 0, 16) . $data;
        $data_length = strlen($data);
        $ret = '';
        $box = range(0, 255);
        $rand_key = array();
        for ($i = 0; $i <= 255; $i++) {
            $rand_key[$i] = ord($crypt_key[$i % $key_length]);
        }
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rand_key[$i]) % 256;
            $box[$i] = $box[$i] ^ $box[$j];
            $box[$j] = $box[$i] ^ $box[$j];
            $box[$i] = $box[$i] ^ $box[$j];
        }
        for ($a = $j = $i = 0; $i < $data_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $box[$a] = $box[$a] ^ $box[$j];
            $box[$j] = $box[$a] ^ $box[$j];
            $box[$a] = $box[$a] ^ $box[$j];
            $ret .= chr(ord($data[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if ($op == self::DECRYPT) {
            if (substr($ret, 0, 16) == substr(md5(substr($ret, 16) . $keyb), 0, 16)) {
                return substr($ret, 26);
            } else {
                return '';
            }
        } else {
            return $keyc . str_replace('=', '', base64_encode($ret));
        }
    }

}
