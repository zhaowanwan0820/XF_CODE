<?PHP
namespace NCFGroup\Common\Library;

class OpenSSL
{
    public static function privateKeyDecodeRSA($cryptText, $privateFileName)
    {
        if (is_file($privateFileName)) {
            $file = file_get_contents($privateFileName);
            $privateKey = openssl_get_privatekey($file);
            if (openssl_private_decrypt(base64_decode($cryptText), $decryptText, $privateKey)) {
                return $decryptText;
            }
        }
        return '';
    }

    public static function publicKeyEncodeRSA($string, $publicFileName)
    {
        if (is_file($publicFileName)) {
            $file = file_get_contents($publicFileName);
            $publicKey = openssl_get_publickey($file);
            if (openssl_public_encrypt($string, $cryptText, $publicKey)) {
                return base64_encode($cryptText);
            }
        }
        return '';
    }

    public static function signature($string, $privateKey, $algorithm)
    {
        openssl_sign($string, $signature, $privateKey, $algorithm);
        return $signature;
    }

    public static function verifySignature($string, $signature, $publicKey, $algorithm)
    {
        $verifyRes = openssl_verify($string, $signature, $publicKey, $algorithm);
        if ($verifyRes == 1) {
            return true;
        }
        return false;
    }
}

