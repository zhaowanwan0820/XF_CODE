<?php
//	禁用错误报告
	error_reporting(0);
//	===== 密钥配置 =====
//	商户的私钥的绝对路径
	define("privatekey",WWW_DIR.'/itzlib/plugins/payment/include/liandong/9843_aitouzi.key.pem');
//	商户私钥配置地址,如果需配置多个商户配置私钥地址，则按如下规则添加
//	global $mer_pk ;//= array();

	$mer_pk = array();
    $mer_pk['9843'] = WWW_DIR.'/itzlib/plugins/payment/include/liandong/9843_aitouzi.key.pem';
//	$mer_pk['9995'] = '/opt/cert/testMer.key.pem';
//	$mer_pk['9996'] = '/opt/cert/testMer.key.pem';
//	$mer_pk['7000998'] = '/opt/cert/7000998_Mer.key.pem';
//        $mer_pk['34003000'] = '/opt/cert/34003000_UMPAYZJTDMSTEST.key.pem';

//	UMPAY的平台证书路径
	define("platcert",WWW_DIR.'/itzlib/plugins/payment/include/liandong/cert_2d59.cert.pem');

//	日志生成目录
	define("logpath",WWW_DIR.'/itzlib/plugins/payment/include/liandong/UmpDemo_ShouYinTai_PHP.log');
//	记录日志文件的同时是否在页面输出:要输出为true,否则为false
	define("log_echo",false);
//	UMPAY平台地址,无需修改
	define("plat_url","https://pay.soopay.net");
//	支付产品名称:无需修改
	define("plat_pay_product_name","spay");
	return $mer_pk;
?>