<?php

/**
 * UrlUtil 
 * 
 */
class UrlUtil
{
    /**
     * 将ID转化为URL格式
     *
     * @param Integer $goods_id
     * @param String(eg:goods_vps/goods_hire) $goods_type
     * @return String
     */
    public static function Key2Url($key,$type) {
        return  base64_encode ($type .$key ) ;
    }

    /**
     * 将URL格式的字符串转化为ID
     *
     * @param String $str
     * @return Array(goods_type, goods_id)
     */
    public static function Url2Key($key,$type) {
        $key = base64_decode ( urldecode ( $key ) );
        return explode ($type, $key );
    }

    public static function _key2url($key, $type='bid', $separator=','){
        $DES_key='itz_prj';
        $DES_iv='1234567';
        $DES = new DES($DES_key, $DES_iv);
        return bin2hex($DES->encrypt(implode($separator,array($key,$type))));
    }
    
    public static function _url2key($key,$separator = ',') {
        if (!function_exists('hex2bin')) {
        function hex2bin($data) {
            static $old;
            if ($old === null) {
                $old = version_compare(PHP_VERSION, '5.2', '<');
            }
            $isobj = false;
            if (is_scalar($data) || (($isobj = is_object($data)) && method_exists($data, '__toString'))) {
                if ($isobj && $old) {
                    ob_start();
                    echo $data;
                    $data = ob_get_clean();
                }
                else {
                    $data = (string) $data;
                }
            }
            else {
                trigger_error(__FUNCTION__.'() expects parameter 1 to be string, ' . gettype($data) . ' given', E_USER_WARNING);
                return;//null in this case
            }
            $len = strlen($data);
            if ($len % 2) {
                trigger_error(__FUNCTION__.'(): Hexadecimal input string must have an even length', E_USER_WARNING);
                return false;
            }
            if (strspn($data, '0123456789abcdefABCDEF') != $len) {
                trigger_error(__FUNCTION__.'(): Input string must be hexadecimal string', E_USER_WARNING);
                return false;
            }
            return pack('H*', $data);
        }
    }
        $DES_key='itz_prj'; 
        $DES_iv='1234567';
        include_once(WWW_DIR."/itzlib/id5/SynPlat/DES.php");
        $DES = new DES($DES_key, $DES_iv);
        return explode($separator, $DES->decrypt(hex2bin($key)));
    }
    
    /**
     * 简单加密
     * @param unknown $str
     * @param string $key
     * @return Ambigous <string, mixed>
     */
    public static function keyToEncrypt($str,$key='itouzi889'){
  		$char = md5($str.$key);
  		$tmp_str = bin2hex($str);
  		$num = strlen($tmp_str);
  		for ($i=0; $i < $num; $i++) {
  			$n = ceil(strlen($char)/4)-4+$i*2;
  			$char = substr_replace($char, $tmp_str[$i], $n, 0);
  		}
  		return $char;
  	}
  	/**
  	 * 简单解密
  	 * @param unknown $str
  	 * @return void|boolean|string|Ambigous <void, boolean, string>
  	 */
  	public static function encryptToKey($str){
  		if (!function_exists('hex2bin')) {
  			function hex2bin($data) {
  				static $old;
  				if ($old === null) {
  					$old = version_compare(PHP_VERSION, '5.2', '<');
  				}
  				$isobj = false;
  				if (is_scalar($data) || (($isobj = is_object($data)) && method_exists($data, '__toString'))) {
  					if ($isobj && $old) {
  						ob_start();
  						echo $data;
  						$data = ob_get_clean();
  					}
  					else {
  						$data = (string) $data;
  					}
  				}
  				else {
  					trigger_error(__FUNCTION__.'() expects parameter 1 to be string, ' . gettype($data) . ' given', E_USER_WARNING);
  					return;//null in this case
  				}
  				$len = strlen($data);
  				if ($len % 2) {
  					trigger_error(__FUNCTION__.'(): Hexadecimal input string must have an even length', E_USER_WARNING);
  					return false;
  				}
  				if (strspn($data, '0123456789abcdefABCDEF') != $len) {
  					trigger_error(__FUNCTION__.'(): Input string must be hexadecimal string', E_USER_WARNING);
  					return false;
  				}
  				return pack('H*', $data);
  			}
  		}
  		
  		$char_num = 32;
  		$num = strlen($str) - $char_num;
  		if($num<1){
  			return '';
  		}
  		$tmp = '';
  		for ($i=0; $i < $num; $i++) {
  			$n = ceil($char_num/4)-4+$i*2;
  			$char_num++;
  			$tmp_str.=$str[$n];
  				
  		}
  		return hex2bin($tmp_str);
  	}
  	
    
    /**
     * 登录跳转URL地址过滤
     * @param type $url
     * @return type
     */
    public static function filterUrl($url){
        $baseUrlHttp = Yii::app()->c->baseUrlHttp;
        if(empty($url)){
            return $baseUrlHttp;
        }
        $parsers = parse_url($url);
        if($parsers['host']){
            $redirectHost = explode('.', $parsers['host']);
            $redirectHost = array_slice($redirectHost,-2);
//            $thisHost = explode('.', Yii::app()->request->hostInfo);
            $thisHost = explode('.', $baseUrlHttp);
            $thisHost = array_slice($thisHost,-2);
            if($redirectHost == $thisHost){
                return $url;
            }else{
                return $baseUrlHttp;
            }
        }else{
            return $baseUrlHttp;
        }
    }
    
}