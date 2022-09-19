<?php

/**
 * ShengpayClass.php
 * @author changqi
 * @version 1.0
 * @desc 盛付通支付接口类
 */

class ShengpayClass {

	public $name = '盛付通';
	public $logo = 'shengpay';
	public $version = 20130711;
	public $description = "盛付通";
	public $type = 1;//1->只能启动，2->可以添加
	
	protected static $submitUrl = 'https://mas.sdo.com/web-acquire-channel/cashier.htm';
	protected static $qasubmitUrl = 'https://mas.sdo.com/web-acquire-channel/cashier.htm'; //'http://mer.mas.sdo.com/web-acquire-channel/cashier.htm';
	
	public $charset = 'UTF-8';
	public $_errorInfo = Null;
	// notify 通知成功返回数据
	public $noticeSuccessCode = 'OK';
	// notify 通知失败返回数据
	public $noticeFailCode = 'Fail';
	
	public $debug = false;
	
	protected $_noticeData = array();
	protected $_returnData = array();
	
	protected $_signParams = array(
								'Name',
								'Version',
								'Charset',
								'TraceNo',
								'MsgSender',
								'SendTime',
								'InstCode',
								'OrderNo',
								'OrderAmount',
								'TransNo',
								'TransAmount',
								'TransStatus',
								'TransType',
								'TransTime',
								'MerchantNo',
								'ErrorCode',
								'ErrorMsg',
								'Ext1',
								'SignType',
							);
	
	/**
	 * 构造支付请求表单
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
	public function buildForm($formData){
		//$result = paymentClass::GetOne(array("nid"=>"shengpay"));
		
		$version = 'V4.1.1.1.1';
		if($this->debug) {// 测试入口
			$submitUrl = self::$qasubmitUrl;
		} else {// 正式入口
			$submitUrl = self::$submitUrl;
		}
		
		$SendTime = date('YmdHis'); //发送支付请求时间
		$OrderTime = date('YmdHis'); //订单提交时间
		$ProductName = $formData['productName']; //ITZ理财产品
		$Charset = $this->charset;

		$params = array(
			'Name' => 'B2CPayment', // 版本名称,默认属性值为:B2CPayment
			'Version' => $version,  // 版本号,默认属性值为: V4.1.1.1.1
			'Charset' => $Charset,  // 字符集:支持GBK、UTF-8、GB2312,默认属性值为:UTF-8
			'MsgSender' => $formData['memberID'], // 发送方标识: 由盛付通提供,默认为:商户号(由盛付通提供的6位正整数),用于盛付通判别请求方的身份
			'SendTime' => $SendTime, // 发送支付请求时间: 用户通过商户网站提交订单的支付时间,必须为14位正整数数字,格式为:yyyyMMddHHmmss
			'OrderNo' => $formData['tradeNo'],  // 商户订单号:商户订单号,50个字符内、只允许使用数字、字母,确保在商户系统唯一
			'OrderAmount' => $formData['orderAmount'], // 支付金额:支付金额,必须大于0,包含2位小数
			'OrderTime' => $OrderTime, // 商户订单提交时间:商户提交用户订单时间,必须为14位正整数数字,格式为:yyyyMMddHHmmss
			'PayType' => 'PT001',// 支付类型编码: 网银直连
			'PayChannel' => '19',// 支付渠道: 支付渠道，当指定PayType 为 PT001网银直连支付模式时有效（19 储蓄卡，20 信用卡）
			'InstCode' => $formData['bankCode'],    // 银行编码
			'PageUrl' => $formData['returnUrl'],      // 支付成功后客户端浏览器回调地址 
			'BackUrl' =>  $formData['BackUrl'],     // 在收银台跳转到商户指定的地址
			'NotifyUrl' => $formData['notifyUrl'], // 服务端通知发货地址
			'ProductName' => $ProductName, // 商品名称
			'BuyerContact' => '', // 支付人联系方式
			'BuyerIp' => $formData['clientIp'], // 买家IP地址
			'Ext1' => '', // 扩展1
			'SignType' => 'MD5', // 签名类型
			'SignMsg' => '', // 签名串
		);

		$origin = '';
		foreach($params as $key=>$value){
			if(!empty($value))
				$origin.=$value;
		}

		$params['SignMsg'] = strtoupper(md5($origin.$formData['privateKey']));
		
		//for post method
		$html = '';
		$html .=  '<form id="shengpayform" method="post" action="'.$submitUrl.'">';
		foreach($params as $key=>$value){
			$html .=  '<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
		}
		$html .=  '</form>';
		$html .=  '<script>document.getElementById("shengpayform").submit();</script>';
		return $html;
	}
	
	/**
	 * 获取支付通知结果
	 * @param array $data
	 * 		memberID: 商户ID
	 * 		privateKey: 私钥
	 * @return boolean
	 */
	public function noticeResult($data) {
		if(!isset($data['privateKey']) || empty($data['privateKey'])) {
			$this->_errorInfo = '$data has not privateKey.';
			return false;
		}
		if(!isset($data['SignType']) || empty($data['SignType'])) {
			$signType = 'MD5';
		} else {
			$signType = $data['SignType'];
		}
		// 通知参数
		$noticeData = $this->getNoticeData();
		
		$originArr = array();
		foreach($this->_signParams as $key) {
			if($key != 'SignMsg' && isset($noticeData[$key]) && !empty($noticeData[$key])) {
				$originArr[] = $noticeData[$key];
			}
		}
		$origin = implode('', $originArr);
		// 验证结果 默认为:false
		$verifyResult = false;
		if($signType == 'MD5') {// MD5的验证方式
			$originSign = strtoupper(md5($origin.$data['privateKey']));
			if($originSign == $noticeData['SignMsg']) {
				$verifyResult = true;
			} else {
				$this->_errorInfo = 'Verify signature is not consistent.';
				$verifyResult = false;
			}
		} else { // 不支持的验证方法
			$this->_errorInfo = $signType.' signtype is not supported ';
			$verifyResult = false;
		}
		
		if($verifyResult) {
			if ($noticeData['TransStatus'] == '01'){
				$payResult = true;
			} elseif ($noticeData['TransStatus'] == '00') {
				$payResult = false;
				$this->_errorInfo = '等待付款中';
			} elseif ($noticeData['TransStatus'] == '02') {
				$payResult = false;
				$this->_errorInfo = '付款失败';
			} else {
				$payResult = false;
				$this->_errorInfo = '未知错误';
			}
		} else {
			$payResult = false;
			$this->_errorInfo = 'Verify signature is not consistent.';
		}
		
		return $payResult;
	}
	
	/**
	 * 获取支付回调结果
	 * @param array $data
	 * 		memberID: 商户ID
	 * 		privateKey: 私钥
	 * @return boolean
	 */
	public function returnResult($data) {
		if(!isset($data['privateKey']) || empty($data['privateKey'])) {
			$this->_errorInfo = '$data has not privateKey.';
			return false;
		}
		if(!isset($data['SignType']) || empty($data['SignType'])) {
			$signType = 'MD5';
		} else {
			$signType = $data['SignType'];
		}
		
		$noticeData = $this->getReturnData();

        $originArr = array();
        foreach($this->_signParams as $key) {
            if($key != 'SignMsg' && isset($noticeData[$key]) && !empty($noticeData[$key])) {
                $originArr[] = $noticeData[$key];
            }
        }
        $origin = implode('', $originArr);
		// 验证结果 默认为:false
        $verifyResult = false;
		if($signType == 'MD5') {// MD5的验证方式
			$originSign = strtoupper(md5($origin.$data['privateKey']));
            if($originSign == $noticeData['SignMsg']) {
				$verifyResult = true;
			} else {
				$this->_errorInfo = 'Verify signature is not consistent.';
				$verifyResult = false;
			}
		} else { // 不支持的验证方法
			$this->_errorInfo = $signType.' signtype is not supported ';
			$verifyResult = false;
		}
		
		if($verifyResult) {
			if ($noticeData['TransStatus'] == '01'){
				$payResult = true;
			} elseif ($noticeData['TransStatus'] == '00') {
				$payResult = false;
				$this->_errorInfo = '等待付款中';
			} elseif ($noticeData['TransStatus'] == '02') {
				$payResult = false;
				$this->_errorInfo = '付款失败';
			} else {
				$payResult = false;
				$this->_errorInfo = '未知错误';
			}
		} else {
			$payResult = false;
			$this->_errorInfo = 'Verify signature is not consistent.';
		}
		
		return $payResult;
	}
	
	/**
	 * 获取支付通知参数
	 * @return boolean
	 */
	public function &getNoticeData() {
		if(!empty($this->_noticeData)) {
			return $this->_noticeData;
		}
		$this->_noticeData = array();
		foreach($_POST as $key => $value) {
			$this->_noticeData[$key] = $value;
		}
		return $this->_noticeData;
	}
	
	/**
	 * 获取支付回调参数
	 * @return boolean
	 */
	public function &getReturnData() {
		if(!empty($this->_returnData)) {
			return $this->_returnData;
		}
		$this->_returnData = array();
		foreach($_POST as $key => $value) {
			$this->_returnData[$key] = $value;
		}
		return $this->_returnData;
	}
	
	/**
	 * 生成数据的签名
	 * @param array $data
	 * @param string $signType
	 */
	public function sign($data, $signType = 'MD5') {
		if($signType == 'MD5') {// MD5的验证方式
			$originSign = md5($data['origin'].$data['pkey']);
		} else { // 不支持的验证方法
			$this->_errorInfo = $signType.' signtype is not supported ';
			$originSign = '';
		}
		return $originSign;
	}
	
	public function getErrorInfo() {
		return $this->_errorInfo;
	}

}