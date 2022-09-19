<?php

class EmayInterface {
	
	protected $_clientObj = Null;
	
	public function EmayInterface($config) {
		$this->__init($config);
	}
	
	public function __init($config) {
		include_once dirname(__FILE__).'/include/EmayClass.php';
		
		// 检查必须参数
		$mustParameters = array('gatewayUrl', 'serialNumber', 'password', 'sessionKey');
		foreach($mustParameters as $pName) {
			if(!isset($config[$pName])) {
				return false;
			}
		}
		
		$gwUrl = $config['gatewayUrl'];
		$serialNumber = $config['serialNumber'];
		$password = $config['password'];
		$sessionKey = $config['sessionKey'];
		
		$this->_clientObj = new EmayClass($gwUrl, $serialNumber, $password, $sessionKey);
		$this->_clientObj->setOutgoingEncoding("UTF-8");
		
		return true;
	}
	
	/**
	 * 发送短信
	 * @param string $phone
	 * @param string $content
	 * @param string $smsID
	 * @return: array $result
	 * 			$result['status']: 发送状态(true:成功  false:失败)
	 * 			$result['statusCode']: 发送返回状态码
	 */
	public function sendSms($phone, $content, $smsID = '') {
		$result = array();
		
		$statusCode = $this->_clientObj->sendSMS(array($phone), $content, '', '', 'UTF-8', '5', $smsID);
		if(trim(strval($statusCode)) === '0') {
			$result['status'] = true;
		} else {
			$result['status'] = false;
		}
		$result['statusCode'] = strval($statusCode);
		
		return $result;	
	}
	/**
	 * 发送语音验证码
	 * @param string $phone
	 * @param string $content
	 * @param string $smsID
	 * @return: array $result
	 * 			$result['status']: 发送状态(true:成功  false:失败)
	 * 			$result['statusCode']: 发送返回状态码
	 */
	public function sendVoice($phone, $content, $smsID = '') {
		$result = array();
	
		$statusCode = $this->_clientObj->sendVoice(array($phone), $content, '', '', 'UTF-8', '5', $smsID);
        //Yii::log("YMYMYMYM：$statusCode;", "info", "YMMMMMMM");
        if($statusCode == 0) {
			$result['status'] = true;
		} else {
			$result['status'] = false;
		}
		$result['statusCode'] = strval($statusCode);
	
		return $result;
	}
	/**
	 * 获取网关账户余额
	 * @return number
	 */
	public function getBalance() {
		$balance = $this->_clientObj->getBalance();
		return $balance;
	}

}