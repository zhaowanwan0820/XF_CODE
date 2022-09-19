<?PHP

/**
 * 利用mcrypt做AES加密解密
 * @param type CIPHER	AES加密算法 支持的数据块为128 192 256位
 * @param type MODE		模式  aes_cfb
 * @param type KEY		密钥  先从env读明文密钥sha256	
 * @param type IV	偏移量  AES加解密需得到同样的IV
 * 
 * 为了兼容python的加密方法 故将iv 和  key 使用相同值
 */

class AesUtil{

	const CIPHER = MCRYPT_RIJNDAEL_128;	
	const MODE = MCRYPT_MODE_CFB;	
	//const KEY = 'xxx'; 
	//const IV = null;	
	
	/**
	 * 加密
	 * @param string $str	需加密的字符串
	 * @param string $key	密钥
	 */
	static public function encode($str){
		$key = $iv = substr(hash('sha256', ConfUtil::get('Ccs.secret-key'),true),0,16);
		//$iv = mcrypt_create_iv(mcrypt_get_iv_size(self::CIPHER,self::MODE),MCRYPT_RAND);
		$passcrypt = mcrypt_encrypt(self::CIPHER, $key, trim($str), self::MODE, $iv);
		$encode = base64_encode($passcrypt);
		return $encode;
	}

	/**
	 * 解密
	 * @param type $str
	 * @param type $key
	 */
	static public function decode($str){
		$key = $iv = substr(hash('sha256', ConfUtil::get('Ccs.secret-key'),true),0,16);
		//$iv = mcrypt_create_iv(mcrypt_get_iv_size(self::CIPHER,self::MODE),MCRYPT_RAND);
		$decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($str), MCRYPT_MODE_CFB, $iv));
		return $decrypted;
	}
}