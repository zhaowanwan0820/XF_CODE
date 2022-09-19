<?php
/**
 * EncodeUtil 
 * 编码处理util
 */
class EncodeUtil
{
	
	public static $encodes = array(
		'gbk'=>true,
	);

	/**
	 * changeEncodeToUTF8 
	 * 将编码改变成utf8
	 *
	 * @param string $str 需要编码的字符串
	 * @param string $ie 指定字符串编码 
	 * @static
	 * @access public
	 * @return UTF8字符串
	 */
	public static function changeEncodeToUTF8($str,$ie="")
	{
		//判断是否指定了编码
		if(isset(self::$encodes[$ie])){
			return mb_convert_encoding($str,'UTF-8',$ie);
		}
		$ret=self::isUtf8($str);
		if(empty($ret)){
//			使用iconv 1.424205E-30681.424205E-3061AB1.958129E-3065%A7 会报错
//			return iconv("gbk",'UTF-8',$str);
			return mb_convert_encoding($str,'UTF-8','GBK');
		}
		return $str;
	}
	
	/**
	 * changeArrEncodeToUTF8 
	 * 
	 * @param mixed $arrStr 
	 * @param string $ie 
	 * @static
	 * @access public
	 * @return void
	 */
	public static function changeArrEncodeToUTF8($arrStr,$ie="")
	{
	    if(is_array($arrStr)){
		    foreach($arrStr as $k=>$v){
			    $arrStr[$k] = self::changeArrEncodeToUTF8($v,$ie);
			}
		}else{
		    $arrStr = self::changeEncodeToUTF8($arrStr,$ie);
		}
		return $arrStr;
	}

	/**
	 * isUtf8 
	 * 判断编码是否utf8
	 *
	 * @param string $str 字符串 
	 * @static
	 * @access public
	 * @return int 匹配次数
	 */
	public static function isUtf8($str)
	{
	    //紧急fix 在大字符串的时候有问题
	    if(strlen($str)>5000){
		    return false;
		}
		//http://www.w3.org/International/questions/qa-forms-utf-8.en.php
		return preg_match('%^(?:
					[\x09\x0A\x0D\x20-\x7E]            # ASCII
					| [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
					|  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
					| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
					|  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
					|  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
					| [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
					|  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
					)*$%xs', $str);
	}
}
