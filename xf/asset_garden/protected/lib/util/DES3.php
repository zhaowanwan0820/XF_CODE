<?php

class DES3{
    private $key;

    public function __construct($key)
    {
        $this->key = $key;
    }

    public static function encrypt($data,$secret='123456')
    {
        $des3 = new self($secret);
        return base64_encode(openssl_encrypt(json_encode($data), 'DES-ECB', $des3->key, OPENSSL_RAW_DATA));
    }

    public static function decrypt($str,$secret='123456')
    {
        $des3 = new self($secret);
        $str = base64_decode($str);
        return json_decode(openssl_decrypt($str, 'DES-ECB', $des3->key, OPENSSL_RAW_DATA),true);
    }
}