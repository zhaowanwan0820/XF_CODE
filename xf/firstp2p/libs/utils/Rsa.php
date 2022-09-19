<?php

namespace libs\utils;

/**
 * 使用openssl实现非对称加密
 */
class Rsa{
    private $_privKey;      /*** private key*/
    private $_pubKey;       /*** public key*/
    private $_keyPath;      /*** the keys saving path*/

    /**
     * the construtor,the param $path is the keys saving path
     */
    public function __construct($path=""){
        $path = $path ? $path : APP_RUNTIME_PATH . "key";
        if(empty($path) || !is_dir($path)){
            throw new Exception('Must set the keys save path');
        }

        $this->_keyPath = $path;
    }

    /**
     * after creating the pub key,get it!
     */
    public function getPubKey() {
        return $this->_pubKey;
    }

    /**
     * create private key
     */
    public function createPriKey() {
        $r = openssl_pkey_new();
        openssl_pkey_export($r, $privKey);
        file_put_contents($this->_keyPath . DIRECTORY_SEPARATOR . 'key.pem', $privKey);
        $this->_privKey = openssl_pkey_get_public($privKey);
    }

    /**
     * create public key
     */
    public function createPubKey() {
        $r = openssl_pkey_new();
        $rp = openssl_pkey_get_details($r);
        $pubKey = $rp['key'];
        $this->_pubKey = $pubKey;
    }

    /**
     * create the key pair,save the key to $this->_keyPath
     */
    public function createKey(){
        $r = openssl_pkey_new();
        openssl_pkey_export($r, $privKey);
        file_put_contents($this->_keyPath . DIRECTORY_SEPARATOR . 'key.pem', $privKey);
        $this->_privKey = openssl_pkey_get_public($privKey);

        $rp = openssl_pkey_get_details($r);
        $pubKey = $rp['key'];
        file_put_contents($this->_keyPath . DIRECTORY_SEPARATOR .  'pub.pem', $pubKey);
        $this->_pubKey = openssl_pkey_get_public($pubKey);
    }

    /**
     * setup the private key
     */
    public function setupPrivKey(){
        if(is_resource($this->_privKey)){
                return true;
        }
        $file = $this->_keyPath . DIRECTORY_SEPARATOR . 'key.pem';
        $prk = file_get_contents($file);
        $this->_privKey = openssl_pkey_get_private($prk);
        return true;
    }

    /**
     * setup the public key
     */
    public function setupPubKey(){
        if(is_resource($this->_pubKey)){
                return true;
        }
        $file = $this->_keyPath . DIRECTORY_SEPARATOR .  'pub.pem';
        $puk = file_get_contents($file);
        $this->_pubKey = openssl_pkey_get_public($puk);
        return true;
    }

    /**
     * encrypt with the private key
     */
    public function privEncrypt($data){
        if(!is_string($data)){
            return null;
        }

        $this->setupPrivKey();

        $r = openssl_private_encrypt($data, $encrypted, $this->_privKey);
        if($r){
            return base64_encode($encrypted);
        }
        return null;
    }

    /**
     * decrypt with the private key
     */
    public function privDecrypt($encrypted){
        if(!is_string($encrypted)){
            return null;
        }

        $this->setupPrivKey();

        $encrypted = base64_decode($encrypted);

        $r = openssl_private_decrypt($encrypted, $decrypted, $this->_privKey);
        if($r){
            return $decrypted;
        }
        return null;
    }

    /**
     * encrypt with public key
     */
    public function pubEncrypt($data){
        if(!is_string($data)){
                return null;
        }

        $this->setupPubKey();

        $r = openssl_public_encrypt($data, $encrypted, $this->_pubKey);
        if($r){
                return base64_encode($encrypted);
        }
        return null;
    }

    /**
     * decrypt with the public key
     */
    public function pubDecrypt($crypted){
        if(!is_string($crypted)){
                return null;
        }

        $this->setupPubKey();

        $crypted = base64_decode($crypted);

        $r = openssl_public_decrypt($crypted, $decrypted, $this->_pubKey);
        if($r){
                return $decrypted;
        }
            return null;
    }

    public function __destruct(){
        @ fclose($this->_privKey);
        @ fclose($this->_pubKey);
    }
}
?>
