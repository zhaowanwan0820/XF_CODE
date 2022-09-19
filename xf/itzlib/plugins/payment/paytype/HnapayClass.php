<?php

/**
 * HnapayClass.php
 * @author changqi
 * @version 1.0
 * @desc 新生支付接口类
 */

class HnapayClass {

	public $name = '新生支付';
	public $logo = 'hnapay';
	public $version = '2.6';
	public $description = "新生支付";
	protected static $submitUrl = 'https://www.hnapay.com/website/pay.htm';
	protected static $qasubmitUrl = 'http://qaapp.hnapay.com/website/pay.htm';

	protected $_signType = 'MD5';
	public $_errorInfo = Null;
	public $charset = 'UTF-8';
	// notify 通知成功返回数据
	public $noticeSuccessCode = 'success';
	// notify 通知失败返回数据
	public $noticeFailCode = 'fail';
	
	public $debug = false;
	
	protected $_noticeData = array();
	protected $_returnData = array();
	
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
	 * 		serialID: 可空，  请求序列号:建议使用本公司代号加当前时间流水的方式 ,  pay20140101100000
	 * 		failureTime: 可空，订单失效时间 20101117020101 默认时间为90天
	 * 		domain: 可空， 客户下单域名
	 * 		customip: 可空， 客户下单IP，服务器ip即可
	 * 		displayName: 可空 String(128) 下单商户显示名
	 * 
	 * @return string $html	
	 */
	public function buildForm(&$formData){
		
		$buildformData = array(); 
		
		if($this->debug) {// 测试入口
			$submitUrl = self::$qasubmitUrl;
		} else {// 正式入口
			$submitUrl = self::$submitUrl;
		}
		
		$version = $this->version;
		
		// 订单提交时间
		$submitTime = date("YmdHis",time());
		
		// 请求序列号:建议使用本公司代号加当前时间流水的方式
		if(isset($formData['serialID'])) {
			$serialID = $formData['serialID'];
		} else {
			$serialID = 'itouzipay'.$submitTime;
		}
		
		// 订单失效时间
		 $failureTime = $formData['failureTime'];
		// 客户下单域名及IP
		if(!empty($formData['domain']) && !empty($formData['customip'])) {
			$customerIP = $formData['domain'].'['.$formData['customip'].']';
		} else {
			$customerIP = '';
		}
		// 订单金额
		$amount = round($formData['orderAmount']*100); //元 转换为分
		// 订单详情数组
		$orderDetailList = array(
				'orderID' => $formData['tradeNo'], //订单号
				'orderAmount' => $amount, //订单明细金额
				'displayName' => '', //下单商户显示名
				'goodsName' => $formData['productName'],//商品名称 "ITZ理财产品"
				'goodsCount' => 1 //商品数量
			);
		//订单详情
		$orderDetails = implode(',', $orderDetailList);
		//订单总金额
		$totalAmount = $amount;
		//交易类型 1000: 即时支付（默认） 0001：商品购买 0002：服务购买 0003：网络拍卖 0004：捐赠
		if(!empty($formData['type'])) {
			$type = $formData['type'];
		} else {
			$type = '1000';
		}
		
		//付款方新生账户号
		if(isset($formData['bankCode'])) {
			if(!empty($formData['email'])) {
				$buyerMarked = $formData['email'];
			} else {
				$buyerMarked = 'service@xxx.com';
			}
		} else {
			$buyerMarked = '';
		}
		//付款方支付方式
		$payType = "BANK_B2C";//direct
		//目标资金机构代码
		if(isset($formData['bankCode'])) {
			$orgCode = strtolower($formData['bankCode']);
		} else {
			$orgCode = '';
		}
		
		//交易币种
		if(isset($formData['currencyCode'])) {
			$currencyCode = $formData['currencyCode'];
		} else {
			$currencyCode = '1'; //RMB 默认
		}
		
		//是否直连
		if(isset($formData['bankCode'])) {
			$directFlag = '1';
		} else {
			$directFlag = '0';
		}
		
		//资金来源借贷标识 0：无特殊要求（默认） 1：只借记 2：只贷记
		$borrowingMarked = "0";
		//优惠券标识
		if(isset($formData['couponFlag'])) {
			$couponFlag = $formData['couponFlag'];
		} else {
			$couponFlag = '1';
		}
		
		//平台商ID
		$platformID = '';
		//商户回调地址
		$returnUrl = $formData['returnUrl'];
		//商户通知地址 
		$noticeUrl = $formData['notifyUrl'];
		//商户ID
		$partnerID = $formData['memberID'];
		//$partnerID = "10000000029";
		//扩展字段
		$remark = $formData['tradeNo'];//TODO
		//编码方式
		$charset = 1;//UTF-8
		//签名类型
		$signType = 2;//MD5
		//签名字符串
		$signMsg ='version='.$version.'&serialID='.$serialID.'&submitTime='.$submitTime.'&failureTime='.$failureTime.'&customerIP='.$customerIP.'&orderDetails='.$orderDetails.'&totalAmount='.$totalAmount.'&type='.$type.'&buyerMarked='.$buyerMarked.'&payType='.$payType.'&orgCode='.$orgCode.'&currencyCode='.$currencyCode.'&directFlag='.$directFlag.'&borrowingMarked='.$borrowingMarked.'&couponFlag='.$couponFlag.'&platformID='.$platformID.'&returnUrl='.$returnUrl.'&noticeUrl='.$noticeUrl.'&partnerID='.$partnerID.'&remark='.$remark.'&charset='.$charset.'&signType='.$signType;
		
		$pkey = $formData['privateKey'];
		$signMsg = md5($signMsg.'&pkey='.$pkey);
		
		//for post method
		$html .= '';
		
		$html .=  '<form id="hnapay" method="POST" action="'.$submitUrl.'">';
		$html .=  '<input type="hidden" id="version" name="version" value="'.$version.'" />';
		$html .=  '<input type="hidden" id="serialID" name="serialID" value="'.$serialID.'"  />';
		$html .=  '<input type="hidden" id="submitTime" name="submitTime" value="'.$submitTime.'"  />';
		$html .=  '<input type="hidden" id="failureTime" name="failureTime" value="'.$failureTime.'"  />';
		$html .=  '<input type="hidden" id="customerIP" name="customerIP" value="'.$customerIP.'"  />';
		$html .=  '<input type="hidden" id="orderDetails" name="orderDetails" value="'.$orderDetails.'"  />';
		$html .=  '<input type="hidden" id="totalAmount" name="totalAmount" value="'.$totalAmount.'"   />';
		$html .=  '<input type="hidden" id="type" name="type" value="'.$type.'"  />';
		$html .=  '<input type="hidden" id="buyerMarked" name="buyerMarked" value="'.$buyerMarked.'"  />';
		$html .=  '<input type="hidden" id="payType" name="payType" value="'.$payType.'"  />';
		$html .=  '<input type="hidden" id="orgCode" name="orgCode" value="'.$orgCode.'"  />';
		$html .=  '<input type="hidden" id="currencyCode" name="currencyCode" value="'.$currencyCode.'"  />';
		$html .=  '<input type="hidden" id="directFlag" name="directFlag" value="'.$directFlag.'"  />';
		$html .=  '<input type="hidden" id="borrowingMarked" name="borrowingMarked" value="'.$borrowingMarked.'"  />';
		$html .=  '<input type="hidden" id="couponFlag" name="couponFlag" value="'.$couponFlag.'"  />';
		$html .=  '<input type="hidden" id="platformID" name="platformID" value="'.$platformID.'"  />';
		$html .=  '<input type="hidden" id="returnUrl" name="returnUrl" value="'.$returnUrl.'"  />';
		$html .=  '<input type="hidden" id="noticeUrl" name="noticeUrl" value="'.$noticeUrl.'"  />';
		$html .=  '<input type="hidden" id="partnerID" name="partnerID" value="'.$partnerID.'"  />';
		$html .=  '<input type="hidden" id="remark" name="remark" value="'.$remark.'"  />';
		$html .=  '<input type="hidden" id="charset" name="charset" value="'.$charset.'"  />';
		$html .=  '<input type="hidden" id="signType" name="signType" value="'.$signType.'"  />';
		$html .=  '<input type="hidden" id="signMsg" name="signMsg" value="'.$signMsg.'"  />';
		$html .=  '</form>';
		$html .=  '<script>document.getElementById("hnapay").submit();</script>';
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
		$payResult = false; // 支付结果
		
		if(!isset($data['privateKey']) || empty($data['privateKey'])) {
			$this->_errorInfo = '$data has not privateKey.';
			return false;
		}
		if(!isset($data['signType']) || empty($data['signType'])) {
			$signType = 'MD5';
		} else {
			$signType = $data['signType'];
		}
		// 通知参数
		$noticeData = $this->getNoticeData();
		
		$srcArr = array();
		foreach($noticeData as $key => $value) {
			if($key != 'signMsg') {
				$srcArr[] = $key.'='.$value;
			}
		}
		$srcArr[] = 'pkey='.$data['privateKey'];
		$src = implode('&', $srcArr);
		
		$verifyResult = false;
		if($signType == 'MD5') {
			$srcSign = md5($src);
			if($srcSign == $noticeData['signMsg']) {
				$verifyResult = true;
			} else {
				$this->_errorInfo = 'Verify signature is not consistent.';
				$verifyResult = false;
			}
		} elseif($signType == 'RSA') {
			$this->_errorInfo = 'RSA signtype has not supported.';
			$verifyResult = false;
		} else {
			$this->_errorInfo = $signType.' signtype is not supported ';
			$verifyResult = false;
		}
		
		if($verifyResult) {
			$stateCode = strval($noticeData["stateCode"]);
			if ($stateCode == '2'){
				$payResult = true;
			} elseif ($stateCode == '0'){
				$payResult = false;
				$this->_errorInfo = '已接受';
			} elseif ($stateCode == '1'){
				$payResult = false;
				$this->_errorInfo = '处理中';
			} elseif ($stateCode == '3'){
				$payResult = false;
				$this->_errorInfo = '处理失败';
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
		$payResult = false; // 支付结果
		if(!isset($data['privateKey']) || empty($data['privateKey'])) {
			$this->_errorInfo = '$data has not privateKey.';
			return false;
		}
		if(!isset($data['signType']) || empty($data['signType'])) {
			$signType = 'MD5';
		} else {
			$signType = $data['signType'];
		}
		
		$noticeData = $this->getReturnData();
		
		$srcArr = array();
		foreach($noticeData as $key => $value) {
			if($key != 'signMsg') {
				$srcArr[] = $key.'='.$value;
			}
		}
		$srcArr[] = 'pkey='.$data['privateKey'];
		$src = implode('&', $srcArr);
		
		$verifyResult = false;
		if($signType == 'MD5') {
			$srcSign = md5($src);
			if($srcSign == $noticeData['signMsg']) {
				$verifyResult = true;
			} else {
				$this->_errorInfo = 'Verify signature is not consistent.';
				$verifyResult = false;
			}
		} elseif($signType == 'RSA') {
			$this->_errorInfo = 'RSA signtype has not supported.';
			$verifyResult = false;
		} else {
			$this->_errorInfo = $signType.' signtype is not supported ';
			$verifyResult = false;
		}
		
		if($verifyResult) {
			$stateCode = strval($noticeData["stateCode"]);
			if ($stateCode == '2'){
				$payResult = true;
			} elseif ($stateCode == '0'){
				$payResult = false;
				$this->_errorInfo = '已接受';
			} elseif ($stateCode == '1'){
				$payResult = false;
				$this->_errorInfo = '处理中';
			} elseif ($stateCode == '3'){
				$payResult = false;
				$this->_errorInfo = '处理失败';
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
		$this->_noticeData['orderID'] = $_POST['orderID'];
		$this->_noticeData['resultCode'] = $_POST["resultCode"];
		$this->_noticeData['stateCode'] = $_POST["stateCode"];
		$this->_noticeData['orderAmount'] = $_POST["orderAmount"];//分！
		$this->_noticeData['payAmount'] = $_POST["payAmount"];//分！
		$this->_noticeData['acquiringTime'] = $_POST["acquiringTime"];
		$this->_noticeData['completeTime'] = $_POST["completeTime"];
		$this->_noticeData['orderNo'] = $_POST["orderNo"];
		$this->_noticeData['partnerID'] = $_POST["partnerID"];
		$this->_noticeData['remark'] = $_POST["remark"];
		$this->_noticeData['charset'] = $_POST["charset"];
		$this->_noticeData['signType'] = $_POST["signType"];
		$this->_noticeData['signMsg'] = $_POST["signMsg"];
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
		$this->_returnData['orderID'] = $_POST["orderID"];
		$this->_returnData['resultCode'] = $_POST["resultCode"];
		$this->_returnData['stateCode'] = $_POST["stateCode"];
		$this->_returnData['orderAmount'] = $_POST["orderAmount"];//分！
		$this->_returnData['payAmount'] = $_POST["payAmount"];//分！
		$this->_returnData['acquiringTime'] = $_POST["acquiringTime"];
		$this->_returnData['completeTime'] = $_POST["completeTime"];
		$this->_returnData['orderNo'] = $_POST["orderNo"];
		$this->_returnData['partnerID'] = $_POST["partnerID"];
		$this->_returnData['remark'] = $_POST["remark"];
		$this->_returnData['charset'] = $_POST["charset"];
		$this->_returnData['signType'] = $_POST["signType"];
		$this->_returnData['signMsg'] = $_POST["signMsg"];
		return $this->_returnData;
	}
	
	public function getErrorInfo() {
		return $this->_errorInfo;
	}
	
}
