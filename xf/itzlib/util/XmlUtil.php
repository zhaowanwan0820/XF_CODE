<?php
/**
 * @file XmlUtil.php
 * @author kuangjun
 * @date 2013/11/21
 * @version 1.0 
 *  
 **/

class XmlUtil
{
	/**
	 * array2Xml
	 * 数组转变为xml，以document开头，每个子元素以item开头
	 * 
	 * @param mixed $arr 
	 * @static
	 * @access public
	 * @return void
	 */
	public static function array2Xml($arr)
	{
		if(!is_array($arr)){
			throw new exception("arr[".print_r($arr,true)."] must be array");
		}
		$xml = "<?xml version=\"1.0\" encoding='UTF-8'?>\n";
		$xml .= "<document>";
		foreach($arr as $key => $value)
		{
			if(!is_array($value)){
				$xml .= "<item><![CDATA[$value]]></item>";
			} else {
				$xml.="<item>";
				$xml.=self::arrayTransform($value);
				$xml.="</item>";
			}
		}
		$xml .= "</document>";
//		var_dump($arr);
		return $xml;
	}

	/**
	 * arrayTransform 
	 * 
	 * @param mixed $arr 
	 * @static
	 * @access private
	 * @return void
	 */
	private static function arrayTransform($arr){
		if(!is_array($arr)){
			throw new exception("arr[".print_r($arr,true)."] must be array");
		}
		$text = "";
		foreach($arr as $key => $value)
		{
			if(!is_array($value)){
				$text .= "<$key><![CDATA[$value]]></$key>";
			} else {
				$text.="<$key>";
				$text.=self::arrayTransform($value);
				$text.="</$key>";
			}
		}
		return $text;
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
?>
