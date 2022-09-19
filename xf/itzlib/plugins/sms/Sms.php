<?php
/*
 * @author: guyun
 * @date: 2014-01-14 14:00
 * version: 1.0
 */


class Sms {
	
	static $client;
	// 配置数据
	protected static $_config;
	// 当前短息网关ID
	protected static $_currentGateway;
	// 当前短息网关对象
	protected static $_interfaceObj = Null;
	
	public function __construct() {
		$this->loadConfig();
	}
	
	/**
	 * 载入当前短信网关的配置信息
	 */
	public function loadConfig() {
		if(empty(self::$_config)) {
			self::$_config = include( 'smsConfig.php' );
		}
		return true;
	}
	
	/**
	 * 获取当前短信网关的配置信息
	 */
	public function getConfig() {
		if(empty(self::$_config)) {
			$this->loadConfig();
		}
		return self::$_config;
	}
	
	/**
	 * 初始化短信网关
	 * @param string $gateway
	 * @param array $params
	 * @return boolean
	 */
	public function initGateway($gateway, $params = array()) {
		$result = array();
		if(!empty(self::$_interfaceObj)) {
			self::$_interfaceObj = Null;
		}
		
		self::$_currentGateway = $gateway;
		// 载入通道配置
		if(!isset(self::$_config['gateway'][$gateway])) {
			$result['status'] = false;
			$result['info'] = 'gateway does not exist.';
			return $result;
		}
		$gatewayConf = self::$_config['gateway'][$gateway];
		
		$interfaceClassName = ucfirst($gatewayConf['type']).'Interface';
		$interfaceClassPath = dirname(__FILE__) .'/'.$interfaceClassName.'.php';
		
		if(!file_exists($interfaceClassPath)) {
			$result['status'] = false;
			$result['info'] = ' interface of gateway does not exist.';
		} else {
			include_once($interfaceClassPath);
			self::$_interfaceObj = new $interfaceClassName( $gatewayConf );
			$result['status'] = true;
			$result['info'] = '';
		}
		
		return $result;
	}
	
	/**
	 * 发送短息
	 * @param string | array $phone
	 * @param string $content
	 * @param array $params
	 * @return boolean | array
	 */
	public function sendSms($phone, $content, $params = array()) {
		if(empty(self::$_interfaceObj)) {
			return false;
		}
		
		$result = self::$_interfaceObj->sendSms($phone, $content);
		return $result;
	}
	/**
	 * 发送语音消息
	 * @param string | array $phone
	 * @param string $content
	 * @param array $params
	 * @return boolean | array
	 */
	public function sendVoice($phone, $content, $params = array()) {
        $gatewayConf = self::$_config['gateway'][1];
        //语言验证码只有亿美有
        $interfaceClassName = 'EmayInterface';
        $interfaceClassPath = dirname(__FILE__) .'/EmayInterface.php';
        include_once($interfaceClassPath);
        $interfaceObj = new $interfaceClassName($gatewayConf);
        $result = $interfaceObj->sendVoice($phone, $content);
        return $result;
    }
	/**
	 * 获取当前短信网关账户余额
	 * @return boolean | string
	 */
	public function getBalance() {
		if(empty(self::$_interfaceObj)) {
			return false;
		}
		
		$result = self::$_interfaceObj->getBalance();
		return $result;
	}

}
