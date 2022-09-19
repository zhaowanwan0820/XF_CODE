<?php

class ZjzwMarketingInterface {
	
	protected $_clientObj = Null;
	protected $_config = array(
		'username' => '',
		'password' => '',
	);
	
	public function ZjzwMarketingInterface($config) {
		$this->__init($config);
	}
	
	public function __init($config) {
		include_once dirname(__FILE__).'/include/ZjzwMarketingSmsClass.php';
		
		// 检查必须参数
		$mustParameters = array('username', 'password');
		foreach($mustParameters as $pName) {
			if(!isset($config[$pName])) {
				return false;
			}
		}
		$this->_config['username'] = $config['username'];
		$this->_config['password'] = $config['password'];
		$this->_clientObj = new ZjzwMarketingSmsClass();
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
		$statusCount = $this->_clientObj->sendMessage($this->_config['username'], $this->_config['password'], $phone, $content,true);
		if($statusCount) {
			$result['status'] = true;
			$result['statusCode'] = '1';
		} else {
			$result['status'] = false;
		}
		
		return $result;	
	}
	
	public function getBalance() {
		$balance = $this->_clientObj->getBalance($this->_config['username'], $this->_config['password']);
		return $balance;
	}

}