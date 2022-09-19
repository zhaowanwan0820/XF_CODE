<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2014-1-21 16:20:30
 * @encode UTF-8编码
 */
abstract class P_Crypt_Abstract {

    const CIPHER_AES_128_CBC = 'AES-128-CBC';
    const CIPHER_AES_128_CFB = 'AES-128-CFB';
    const CIPHER_AES_128_CFB1 = 'AES-128-CFB1';
    const CIPHER_AES_128_CFB8 = 'AES-128-CFB8';
    const CIPHER_AES_128_ECB = 'AES-128-ECB';
    const CIPHER_AES_128_OFB = 'AES-128-OFB';
    const CIPHER_AES_192_CBC = 'AES-192-CBC';
    const CIPHER_AES_192_CFB = 'AES-192-CFB';
    const CIPHER_AES_192_CFB1 = 'AES-192-CFB1';
    const CIPHER_AES_192_CFB8 = 'AES-192-CFB8';
    const CIPHER_AES_192_ECB = 'AES-192-ECB';
    const CIPHER_AES_192_OFB = 'AES-192-OFB';
    const CIPHER_AES_256_CBC = 'AES-256-CBC';
    const CIPHER_AES_256_CFB = 'AES-256-CFB';
    const CIPHER_AES_256_CFB1 = 'AES-256-CFB1';
    const CIPHER_AES_256_CFB8 = 'AES-256-CFB8';
    const CIPHER_AES_256_ECB = 'AES-256-ECB';
    const CIPHER_AES_256_OFB = 'AES-256-OFB';
    const CIPHER_BF_CBC = 'BF-CBC';
    const CIPHER_BF_CFB = 'BF-CFB';
    const CIPHER_BF_ECB = 'BF-ECB';
    const CIPHER_BF_OFB = 'BF-OFB';
    const CIPHER_CAST5_CBC = 'CAST5-CBC';
    const CIPHER_CAST5_CFB = 'CAST5-CFB';
    const CIPHER_CAST5_ECB = 'CAST5-ECB';
    const CIPHER_CAST5_OFB = 'CAST5-OFB';
    const CIPHER_DES_CBC = 'DES-CBC';
    const CIPHER_DES_CFB = 'DES-CFB';
    const CIPHER_DES_CFB1 = 'DES-CFB1';
    const CIPHER_DES_CFB8 = 'DES-CFB8';
    const CIPHER_DES_ECB = 'DES-ECB';
    const CIPHER_DES_EDE = 'DES-EDE';
    const CIPHER_DES_EDE_CBC = 'DES-EDE-CBC';
    const CIPHER_DES_EDE_CFB = 'DES-EDE-CFB';
    const CIPHER_DES_EDE_OFB = 'DES-EDE-OFB';
    const CIPHER_DES_EDE3 = 'DES-EDE3';
    const CIPHER_DES_EDE3_CBC = 'DES-EDE3-CBC';
    const CIPHER_DES_EDE3_CFB = 'DES-EDE3-CFB';
    const CIPHER_DES_EDE3_CFB1 = 'DES-EDE3-CFB1';
    const CIPHER_DES_EDE3_CFB8 = 'DES-EDE3-CFB8';
    const CIPHER_DES_EDE3_OFB = 'DES-EDE3-OFB';
    const CIPHER_DES_OFB = 'DES-OFB';
    const CIPHER_DESX_CBC = 'DESX-CBC';
    const CIPHER_IDEA_CBC = 'IDEA-CBC';
    const CIPHER_IDEA_CFB = 'IDEA-CFB';
    const CIPHER_IDEA_ECB = 'IDEA-ECB';
    const CIPHER_IDEA_OFB = 'IDEA-OFB';
    const CIPHER_RC2_40_CBC = 'RC2-40-CBC';
    const CIPHER_RC2_64_CBC = 'RC2-64-CBC';
    const CIPHER_RC2_CBC = 'RC2-CBC';
    const CIPHER_RC2_CFB = 'RC2-CFB';
    const CIPHER_RC2_ECB = 'RC2-ECB';
    const CIPHER_RC2_OFB = 'RC2-OFB';
    const CIPHER_RC4 = 'RC4';
    const CIPHER_RC4_40 = 'RC4-40';
    const DECRYPT = 'decrypt';
    const ENCRYPT = 'encrypt';
    const MD_DSA = 'DSA';
    const MD_DSA_SHA = 'DSA-SHA';
    const MD_MD4 = 'MD4';
    const MD_MD5 = 'MD5';
    const MD_RIPEMD160 = 'RIPEMD160';
    const MD_SHA = 'SHA';
    const MD_SHA1 = 'SHA1';
    const MD_SHA224 = 'SHA224';
    const MD_SHA256 = 'SHA256';
    const MD_SHA384 = 'SHA384';
    const MD_SHA512 = 'SHA512';
    const PRIVATE_KEY = 0;
    const PUBLIC_KEY = 1;

    protected $_cipher_method = array(
        self::CIPHER_AES_128_CBC,
        self::CIPHER_AES_128_CFB,
        self::CIPHER_AES_128_CFB1,
        self::CIPHER_AES_128_CFB8,
        self::CIPHER_AES_128_ECB,
        self::CIPHER_AES_128_OFB,
        self::CIPHER_AES_192_CBC,
        self::CIPHER_AES_192_CFB,
        self::CIPHER_AES_192_CFB1,
        self::CIPHER_AES_192_CFB8,
        self::CIPHER_AES_192_ECB,
        self::CIPHER_AES_192_OFB,
        self::CIPHER_AES_256_CBC,
        self::CIPHER_AES_256_CFB,
        self::CIPHER_AES_256_CFB1,
        self::CIPHER_AES_256_CFB8,
        self::CIPHER_AES_256_ECB,
        self::CIPHER_AES_256_OFB,
        self::CIPHER_BF_CBC,
        self::CIPHER_BF_CFB,
        self::CIPHER_BF_ECB,
        self::CIPHER_BF_OFB,
        self::CIPHER_CAST5_CBC,
        self::CIPHER_CAST5_CFB,
        self::CIPHER_CAST5_ECB,
        self::CIPHER_CAST5_OFB,
        self::CIPHER_DES_CBC,
        self::CIPHER_DES_CFB,
        self::CIPHER_DES_CFB1,
        self::CIPHER_DES_CFB8,
        self::CIPHER_DES_ECB,
        self::CIPHER_DES_EDE,
        self::CIPHER_DES_EDE_CBC,
        self::CIPHER_DES_EDE_CFB,
        self::CIPHER_DES_EDE_OFB,
        self::CIPHER_DES_EDE3,
        self::CIPHER_DES_EDE3_CBC,
        self::CIPHER_DES_EDE3_CFB,
        self::CIPHER_DES_EDE3_CFB1,
        self::CIPHER_DES_EDE3_CFB8,
        self::CIPHER_DES_EDE3_OFB,
        self::CIPHER_DES_OFB,
        self::CIPHER_DESX_CBC,
        self::CIPHER_IDEA_CBC,
        self::CIPHER_IDEA_CFB,
        self::CIPHER_IDEA_ECB,
        self::CIPHER_IDEA_OFB,
        self::CIPHER_RC2_40_CBC,
        self::CIPHER_RC2_64_CBC,
        self::CIPHER_RC2_CBC,
        self::CIPHER_RC2_CFB,
        self::CIPHER_RC2_ECB,
        self::CIPHER_RC2_OFB,
        self::CIPHER_RC4,
        self::CIPHER_RC4_40,
    );
    protected $_md_method = array(
        self::MD_DSA,
        self::MD_DSA_SHA,
        self::MD_MD4,
        self::MD_MD5,
        self::MD_RIPEMD160,
        self::MD_SHA,
        self::MD_SHA1,
        self::MD_SHA224,
        self::MD_SHA256,
        self::MD_SHA384,
        self::MD_SHA512,
    );
    public $keys = array(
        self::PRIVATE_KEY => false,
        self::PUBLIC_KEY => false,
    );

    public function __construct($config) {
        $keys = $this->_generate_key($config);
        if ((is_array($keys) && !isset($keys[self::PRIVATE_KEY], $keys[self::PUBLIC_KEY])) || (!is_array($keys) && !is_string($keys))) {
            return;
        }
        if (is_string($keys)) {
            $this->keys = array(
                self::PRIVATE_KEY => trim(strval($keys)),
                self::PUBLIC_KEY => trim(strval($keys)),
            );
        } else {
            $this->keys = array(
                self::PRIVATE_KEY => trim(strval($keys[self::PRIVATE_KEY])),
                self::PUBLIC_KEY => trim(strval($keys[self::PUBLIC_KEY])),
            );
        }
    }

    abstract protected function _decrypt($data, $key = false);

    public function decrypt($data, $key = false, $now_time = false) {
        $decrypt = false;
        $key = (false !== $key) ? trim(strval($key)) : trim(strval($this->keys[self::PRIVATE_KEY]));
        do {
            if (false === ($decrypt = $this->_decrypt($data, $key))) {
                break;
            }
            if (false === $now_time) {
                break;
            }
            $expire = intval(substr($decrypt, -10));
            if ($expire < intval($now_time)) {
                break;
            }
            $decrypt = substr($decrypt, 0, -10);
        } while (false);
        return $decrypt;
    }

    abstract protected function _encrypt($data, $key = false);

    public function encrypt($data, $key = false, $expire = false) {
        $key = (false !== $key) ? trim(strval($key)) : trim(strval($this->keys[self::PUBLIC_KEY]));
        if (false !== $expire) {
            $data = $data . sprintf('%010d', $expire);
        }
        return $this->_encrypt($data, $key);
    }

    abstract protected function _generate_key($config);

    abstract public function get_error();

    public function get_keys() {
        return $this->keys;
    }

    public function process($data, $keys = array(), $method = self::ENCRYPT, $time = false) {
        if (!method_exists($this, $method) || (!is_array($keys) && !is_string($keys))) {
            return $data;
        }
        $ret = array();
        foreach ($data as $k => $v) {
            if (is_scalar($v) && is_string($keys)) {
                $ret[$k] = $this->$method($v, $keys, $time);
                continue;
            }
            if (is_array($v)) {
                $ret[$k] = $this->process($v, $keys, $method, $time);
                continue;
            }
            if (is_scalar($v) && is_array($keys) && isset($keys[$k])) {
                $ret[$k] = $this->$method($v, $keys[$k], $time);
                continue;
            }
        }
        return $ret;
    }

}
