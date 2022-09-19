<?php
/* *
 * MD5
 * 详细：MD5加密
 * 版本：1.1
 */

/**
 * 签名字符串
 * @param $prestr 需要签名的字符串
 * @param $key 私钥
 * return 签名结果
 */
function md5Sign($prestr, $key) {
	$prestr = $prestr ."&key=". $key;
	// 记录签名待签原串 方便定位问题
	file_put_contents("logMD5.txt","签名原串:".$prestr."\n", FILE_APPEND);
	return md5($prestr);
}

/**
 * 验证签名
 * @param $prestr 需要签名的字符串
 * @param $sign 签名结果
 * @param $key 私钥
 * return 签名结果
 */
function md5Verify($prestr, $sign, $key) {
	$prestr = $prestr ."&key=". $key;
	// file_put_contents("log.txt","prestr:".$prestr."\n", FILE_APPEND);
	$mysgin = md5($prestr);
	// file_put_contents("log.txt","mysgin:".$mysgin."\n", FILE_APPEND);
	if($mysgin == $sign) {
		return true;
	}
	else {
		return false;
	}
}
?>