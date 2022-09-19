<?php

class YyqdInterface {
	
	protected $_clientObj = Null;
	protected $_config = array(
		'username' => '',
		'password' => '',
	);
	
	public function YyqdInterface($config) {
		$this->__init($config);
	}
	
	public function __init($config) {
		include_once dirname(__FILE__).'/include/YyqdSmsClass.php';
		
		// 检查必须参数
		$mustParameters = array('username', 'password');
		foreach($mustParameters as $pName) {
			if(!isset($config[$pName])) {
				return false;
			}
		}
		
		$this->_config['username'] = $config['username'];
		$this->_config['password'] = $config['password'];
		$this->_clientObj = new YyqdSmsClass();
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
	public function sendSms($phone, $content) {
		$result = array();
		
		$statusCode = $this->_clientObj->sendMessage($this->_config['username'], $this->_config['password'], $phone, $content);
		
		if(trim(strval($statusCode)) == '1') {
			$result['status'] = true;
		} else {
			$result['status'] = false;
		}
		$result['statusCode'] = strval($statusCode);
		
		return $result;	
	}
	
	public function getBalance() {
		$balance = $this->_clientObj->getBalance($this->_config['username'], $this->_config['password']);
		return $balance;
	}

}