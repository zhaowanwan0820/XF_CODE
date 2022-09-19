<?php
/**
 * @file McryptUtil.php
 * @author kuangjun
 * @date 2013/11/21
 * @version 1.0 
 *  
 **/

class McryptUtil
{

	const CRYPT_KEY = "680d2a3c757c3f8c670e8bc14a5e2c2a";

	const POW64 = "18446744073709551616";
	const POW63 = "9223372036854775808";
	const NEG_POW63 = "-9223372036854775807";

	/**
	 * 加密
	 * str 被加密串
	 * key 密钥
	 */
	static public function encrypt($str, $key=self::CRYPT_KEY) {
		/* 开启加密算法/ */
		$td = mcrypt_module_open('twofish', '', 'ecb', '');
		/* 建立 IV，并检测 key 的长度 */
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		$ks = mcrypt_enc_get_key_size($td);
		/* 生成 key */
		$keystr = substr(md5($key), 0, $ks);
		/* 初始化加密程序 */
		mcrypt_generic_init($td, $keystr, $iv);
		/* 加密, $encrypted 保存的是已经加密后的数据 */
		$encrypted = mcrypt_generic($td, $str);
		/* 检测解密句柄，并关闭模块 */
		mcrypt_module_close($td);
		/* 转化为16进制 */
		$hexdata = bin2hex($encrypted);
		//返回
		return $hexdata;
	}

	/**
	 * 解密
	 * str 被解密串
	 * key 密钥
	 */
	static public function decrypt($str, $key=self::CRYPT_KEY) {
		/* 开启加密算法/ */
		$td = mcrypt_module_open('twofish', '', 'ecb', '');
		/* 建立 IV，并检测 key 的长度 */
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		$ks = mcrypt_enc_get_key_size($td);
		/* 生成 key */
		$keystr = substr(md5($key), 0, $ks);
		/* 初始化加密模块，用以解密 */
		mcrypt_generic_init($td, $keystr, $iv);
		/* 解密 */ 
		$encrypted = pack( "H*", $str);
		$decrypted = trim(mdecrypt_generic($td, $encrypted));
		/* 检测解密句柄，并关闭模块 */
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		/* 返回原始字符串 */
		return $decrypted;
	}
	
	/**
	 * signToUnsigned64 
	 * 
	 * @param mixed $v 
	 * @static
	 * @access private
	 * @return void
	 */
	static public function signToUnsigned64($v)
	{
		$ret = bcmod($v,self::POW64);
		if($ret<0){
			return bcadd($ret,self::POW64);
		}
		return $ret;
	}
	
	/**
	 * unsignedToSign64 
	 * 
	 * @param mixed $v 
	 * @static
	 * @access private
	 * @return void
	 */
	static public function unsignedToSign64($v){
		$v = bcmod($v,self::POW64);
		if($v>self::POW63){
			$v = bcsub($v,self::POW64);
		}
		return intval($v);
	}
	
	/**
	 * bcxor64 
	 * 
	 * @param mixed $v 
	 * @param mixed $p 
	 * @static
	 * @access private
	 * @return void
	 */
	static private function bcxor64($v,$p)
	{
		$v = self::unsignedToSign64($v)^self::unsignedToSign64($p);
		return self::signToUnsigned64($v);
	}

	/**
	 * murmurHash64 
	 * 64-bit hash for 64-bit platforms
	 *
	 * @param mixed $key 
	 * @static
	 * @access public
	 * @return void
	 */
	static public function murmurHash64($data) {
		$seed = 19820125;
		$m = bcadd(bcmul(0xc6a4a793,1<<32),0x5bd1e995);
		$r = 47;
		$len = strlen($data);

		$h = self::bcxor64($seed,bcmod(bcmul($len,$m),self::POW64));
		$endLength = intval($len/8);
		$endIndex = 0;

		while ($endIndex<$endLength) {
			$k=0;
			$k ^= ord($data[$endIndex*8+7]) << 56;
			$k ^= ord($data[$endIndex*8+6]) << 48;
			$k ^= ord($data[$endIndex*8+5]) << 40;
			$k ^= ord($data[$endIndex*8+4]) << 32;
			$k ^= ord($data[$endIndex*8+3]) << 24;
			$k ^= ord($data[$endIndex*8+2]) << 16;
			$k ^= ord($data[$endIndex*8+1]) << 8;
			$k ^= ord($data[$endIndex*8+0]);
			$k = self::signToUnsigned64($k);
			$endIndex ++ ;

			$k = bcmod(bcmul($k,$m),self::POW64);
			$k = self::signToUnsigned64(self::unsignedToSign64($k)^self::unsignedToSign64(bcdiv($k,1<<$r)));
			$k = self::signToUnsigned64(bcmul($k,$m));
			$h = self::signToUnsigned64(self::unsignedToSign64($h)^self::unsignedToSign64($k));
			$h = bcmod(bcmul($h,$m),self::POW64);
		}

		$tmp = 0;
		switch ($len & 7) {
			case 7: $tmp ^= ord($data[$endIndex*8+6]) << 48;
			case 6: $tmp ^= ord($data[$endIndex*8+5]) << 40;
			case 5: $tmp ^= ord($data[$endIndex*8+4]) << 32;
			case 4: $tmp ^= ord($data[$endIndex*8+3]) << 24;
			case 3: $tmp ^= ord($data[$endIndex*8+2]) << 16;
			case 2: $tmp ^= ord($data[$endIndex*8+1]) << 8;
			case 1: $tmp ^= ord($data[$endIndex*8+0]);
					$h = bcmod(bcmul(self::signToUnsigned64($tmp^self::unsignedToSign64($h)),$m),self::POW64);
		};

		
		$h = self::signToUnsigned64(self::unsignedToSign64($h)^self::unsignedToSign64(bcdiv($h,1<<$r)));
		$h = bcmod(bcmul($h,$m),self::POW64);
		$h = self::signToUnsigned64(self::unsignedToSign64($h)^self::unsignedToSign64(bcdiv($h,1<<$r)));
		return self::unsignedToSign64($h);
	}

}



/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
?>
