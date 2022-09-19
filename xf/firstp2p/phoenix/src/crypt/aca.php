<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2014-1-22 11:58:02
 * @encode UTF-8编码
 */
class P_Crypt_Aca extends P_Crypt_Abstract {

    const FUNCTION_DECRYPT = 'openssl_%s_decrypt';
    const FUNCTION_ENCRYPT = 'openssl_%s_encrypt';

    private $_type = array(
        self::PRIVATE_KEY => 'private',
        self::PUBLIC_KEY => 'public',
    );

    public function __construct($keys = array(), $config = array()) {
        if (is_array($keys) && isset($keys[self::PRIVATE_KEY], $keys[self::PUBLIC_KEY])) {
            $this->keys = array(
                self::PRIVATE_KEY => $keys[self::PRIVATE_KEY],
                self::PUBLIC_KEY => $keys[self::PUBLIC_KEY],
            );
        } else {
            parent::__construct($config);
        }
    }

    protected function _decrypt($data, $key = false) {
        $ret = @openssl_private_decrypt(base64_decode($data), $decrypt, $key);
        return $ret ? $decrypt : $ret;
    }

    protected function _encrypt($data, $key = false) {
        $ret = @openssl_public_encrypt($data, $encrypt, $key);
        return $ret ? base64_encode($encrypt) : $ret;
    }

    protected function _generate_key($config) {
        $data = false;
        do {
            if (false === ($ret = @openssl_pkey_new($config))) {
                break;
            }
            if (false === @openssl_pkey_export($ret, $private_key)) {
                break;
            }
            if (false === ($detail = @openssl_pkey_get_details($ret)) || !isset($detail['key'])) {
                break;
            }
            $this->keys = array(
                self::PRIVATE_KEY => $private_key,
                self::PUBLIC_KEY => $detail['key'],
            );
        } while (false);
        return $data;
    }

    public function get_error() {
        $error = array();
        while ($msg = @openssl_error_string()) {
            $error[] = $msg;
        }
        return implode("\r\n", $error);
    }

}
