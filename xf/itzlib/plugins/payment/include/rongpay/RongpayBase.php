<?php

class RongpayBase  {

	/**
	 * 功能：融宝支付接口公用函数
	 * 详细：该页面是请求、通知返回两个文件所调用的公用函数核心处理文件，不需要修改
	 */
	public static function buildMySign($sort_array, $key, $sign_type = "MD5") {
		$prestr = self::createLinkString($sort_array);     	//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = $prestr.$key;							    //把拼接后的字符串再与安全校验码直接连接起来
		$mysgin = self::sign($prestr, $sign_type);			//把最终的字符串签名，获得签名结果
		return $mysgin;
	}
	
	/**
	 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
	 * $array 需要拼接的数组
	 * return 拼接完成以后的字符串
	 */
	public static function createLinkString($array) {
		$arg  = "";
		foreach($array as $key => $val) {
			$arg.=$key."=".$val."&";
		}
		$arg = substr($arg, 0, count($arg)-2);    //去掉最后一个&字符
		return $arg;
	}
	
	/**
	 * 除去数组中的空值和签名参数
	 * @parameter: 签名参数组
	 * @return: 去掉空值与签名参数后的新签名参数组
	 */
	public static function paraFilter($parameter) {
		$para = array();
		foreach($parameter as $key => $val) {
			if($key == "sign" || $key == "sign_type" || $val == "") {
				continue;
			} else {
				$para[$key] = $parameter[$key];
			}
		}
		return $para;
	}
	
	/**对数组排序
	 * $array 排序前的数组
	 * return 排序后的数组
	 */
	public static function argSort($array) {
		ksort($array);
		reset($array);
		return $array;
	}
	
	/**签名字符串
	 * $prestr 需要签名的字符串
	 * return 签名结果
	 */
	public static function sign($prestr,$sign_type) {
		$sign='';
		if($sign_type == 'MD5') {
			$sign = md5($prestr);
		} else {
			die("融宝支付暂不支持".$sign_type."类型的签名方式");
		}
		return $sign;
	}

}