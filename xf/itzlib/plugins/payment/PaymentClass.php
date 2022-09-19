<?php
/**
 * PaymentClass.php
 * @version 1.0
 * @desc 支付接口调用类
 * 网关支付用此类来实例化出具体通道类
 */

class PaymentClass {
	
	public $charset = 'UTF-8';
	
	public $debug = false;
	
	// 调用的支付接口处理接口对象
	protected $_interfaceObj = Null;
	
	protected $_errorInfo = Null;
	
	// 当前支付接口类型名称
	protected $_currentpaytype = Null;
	
	public function __construct($paytype = '') {
		
		$initFlag = $this->initPayType($paytype);
		
	}
	
	/**
	 * 初始化支付接口
	 * @param string $paytype
	 * @return boolean
	 */
	public function initPayType($paytype) {
		if(empty($paytype)) {
			$this->_errorInfo = ' paytype is empty.';
			return false;
		}
		
		$this->_currentpaytype = $paytype;
		
		if(!empty($this->_interfaceObj)) {
			$this->_interfaceObj = Null;
		}
		
		$interfaceClassName = ucfirst($paytype).'Class';
		$interfaceClassPath = dirname(__FILE__) .'/paytype/'.$interfaceClassName.'.php';
		
		if(!file_exists($interfaceClassPath)) {
			$this->_errorInfo = ' interface does not exist.';
			return false;
		} else {
			include_once($interfaceClassPath);
			$this->_interfaceObj = new $interfaceClassName();
			$this->_interfaceObj->debug = $this->debug;
			return true;
		}
		
		return false;
	}
	
	/**
	 * 发送支付请求
	 * @param array $formData
	 *   $formData 中元素含义解释
	 *   	tradeNo: 必填 商户提供的唯一订单号
	 *   	orderAmount: 必填 订单明细金额, 整型数字，以元为整数单位
	 *   	productName: 必填 商品名称
	 * 		bankCode: 可空, 银行机构代码
	 * 		returnUrl: 商户回调地址
	 * 		notifyUrl: 商户通知地址
	 * 		memberID: 商户ID
	 * 		privateKey: 私钥
	 * 		clientIp: 客户端ip
	 * 		productDesc: 可空, 商品描述
	 * 
	 * 
	 * @return string $html
	 */
	public function buildForm($requestData) {
		if(empty($this->_interfaceObj)) {
			$this->_errorInfo = 'The interface is not initialized.';
			return false;
		}
		// 调用支付接口发送支付请求方法
		return $this->_interfaceObj->buildForm($requestData);
	}
    
    /**
	 * 连连手机支付发送支付请求
	 * @param array $formData
	 * @return string $html
	 */
	public function mobileForm($requestData) {
		if(empty($this->_interfaceObj)) {
			$this->_errorInfo = 'The interface is not initialized.';
			return false;
		}
		// 调用支付接口发送支付请求方法
		return $this->_interfaceObj->mobileForm($requestData);
	}
	
	/**
	 * 获取支付通知结果
	 * @param array $data
	 * 		memberID: 商户ID
	 * 		privateKey: 私钥
	 * @return boolean
	 */
	public function noticeResult($data = array()) {
		if($this->_interfaceObj->noticeResult($data)) {
			return true;
		} else {
			$this->_errorInfo = $this->_interfaceObj->getErrorInfo();
			return false;
		}
	}
	
	/**
	 * 获取支付回调结果
	 * @param array $data
	 * 		memberID: 商户ID
	 * 		privateKey: 私钥
	 * @return boolean
	 */
	public function returnResult($data = array()) {
		if($this->_interfaceObj->returnResult($data)) {
			return true;
		} else {
			$this->_errorInfo = $this->_interfaceObj->getErrorInfo();
			return false;
		}
	}
	
	/**
	 * 获取支付通知参数
	 * @return boolean
	 */
	public function &getNoticeData() {
		return $this->_interfaceObj->getNoticeData();
	}
	
	/**
	 * 获取支付回调参数 
	 * @return boolean
	 */
	public function &getReturnData() {
		return $this->_interfaceObj->getReturnData();
	}
	
	/**
	 * 获取错误信息
	 * @return string
	 */
	public function getErrorInfo() {
		return $this->_errorInfo;
	}
	
	public function getCharset() {
		return $this->_interfaceObj->charset;
	}
	
	/**
	 * 获取notice通知要返回的表示成功的代码
	 */
	public function getNoticeSuccessCode() {
		return $this->_interfaceObj->noticeSuccessCode;
	}
	
	/**
	 * 获取notice通知要返回的表示失败的代码
	 */
	public function getNoticeFailCode() {
		return $this->_interfaceObj->noticeFailCode;
	}
    
    public function __call($name, $arguments) {
        if(!method_exists($this->_interfaceObj,$name)){
            Yii::log('PaymentClass call exists:'.$name.print_r($arguments,true),'error');
            return false;
        }
        try {
        	$data = call_user_func_array( array($this->_interfaceObj, $name ), $arguments );
		}catch(Exception $e) {
        	throw new Exception("{$method} :Get data from server is error! " . $e->getMessage());
		}
        return $data;
    }
	
}
