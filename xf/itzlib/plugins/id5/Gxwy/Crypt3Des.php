<?php
class Crypt3Des
{
    var $key   = '88888888';
    var $deviceid   = '';
    var $user   = '';
    var $lsh   = '';
    var $cipherText = '';
    var $HcipherText = '';
    var $decrypted_data ='';
    var $iv='';

    //加密
    function en($str)
    {
        $cipher = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_ECB, '');
        $iv     = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_3DES,MCRYPT_MODE_ECB), MCRYPT_RAND);

        if (mcrypt_generic_init($cipher, $this->key, $iv) != -1)
        {
            $str=$this->pad($str);
            $str=$this->PaddingPKCS7($str);
            $this->cipherText = mcrypt_generic($cipher,$str);
            mcrypt_generic_deinit($cipher);
            // 以十六进制字符显示加密后的字符
            $this->HcipherText=bin2hex($this->cipherText);
            // printf("<p>3DES encrypted:\n%s</p>",$this->cipherText);
            //printf("<p>3DES HexEncrypted:\n%s</p>",$this->HcipherText);
        }
        mcrypt_module_close($cipher);
        return strtoupper($this->HcipherText);
    }
    //解密
    function de($str)
    {
        $str    = pack('H*', $str);
        $cipher = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_ECB, '');
        $iv     = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_3DES,MCRYPT_MODE_ECB), MCRYPT_RAND);
        if (mcrypt_generic_init($cipher,$this->key, $iv) != -1)
        {
            $this->decrypted_data = mdecrypt_generic($cipher,$str);
            mcrypt_generic_deinit($cipher);
        }
        mcrypt_module_close($cipher);
        return $this->unpad($this->decrypted_data);
    }

    private function pad ($data)
    {
        $data = str_replace("\n","",$data);
        $data = str_replace("\t","",$data);
        $data = str_replace("\r","",$data);
        return $data;
    }

    private function unpad ($text)
    {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text)) {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
            return false;
        }
        return substr($text, 0, - 1 * $pad);
    }
    private function PaddingPKCS7($input) 
    {
        $srcdata = $input;
        $block_size = mcrypt_get_block_size('tripledes', 'ecb');
        $padding_char = $block_size - (strlen($input) % $block_size);
        $srcdata .= str_repeat(chr($padding_char),$padding_char);
        return $srcdata;
    }
 }