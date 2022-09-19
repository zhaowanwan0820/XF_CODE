<?php

/**
 * ShengpayClass.php
 * @author changqi
 * @version 1.0
 * @desc 财付通支付接口类
 */

class TenpayClass  {

	public $name = '财付通';
	public $logo = 'TENPAY';
	public $version = '1.0';
	public $description = "腾讯财付通";
	public $charset = 'UTF-8';
	// notify 通知成功返回数据
	public $noticeSuccessCode = 'success';
	// notify 通知失败返回数据
	public $noticeFailCode = 'fail';
	
	public $debug = false;
	
	public $_errorInfo = Null;
	
	// 支付接口
	protected static $payUrl = 'https://gw.tenpay.com/gateway/pay.htm'; //  
	// 通知查询接口
	protected static $noticeQueryUrl = 'https://gw.tenpay.com/gateway/verifynotifyid.xml';
	// 订单查询接口
	protected static $orderQueryUrl = 'https://gw.tenpay.com/gateway/normalorderquery.xml';
	// 退款接口
	protected static $refundUrl = 'https://mch.tenpay.com/refundapi/gateway/refund.xml';
	// 退款明细查询接口
	protected static $refundQueryUrl = 'https://gw.tenpay.com/gateway/normalrefundquery.xml';
	
	public static $attach = 'itouzi_tenpay';
	// 支持库的路径
	protected static $_includePath = '';
	
	protected $_noticeData = array();
	protected $_returnData = array();

	//签名类型MD5 or RSA
	protected $sign_type = 'RSA';
	
	function __construct() {
		
		self::$_includePath = dirname(__FILE__).'/../include';

	}
	
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
	 * 
	 * @return string $html
	 */
	public function buildForm($formData){
		# 记录当前通道的签名方式
        Yii::log("TenPay sign_type : ".strtoupper(trim($this->sign_type)), "info","TenPayClass.buildForm");

		include_once self::$_includePath .'/tenpay/RequestHandler.class.php';
		
		/* 商户号 */
		$partner = $formData['memberID'];

		/* 密钥 */
		$key = $formData['privateKey'];
		
		//date_default_timezone_set(PRC);
		$strDate = date("Ymd");
		$strTime = date("His");

		//4位随机数
		$randNum = rand(1000, 9999);
		//10位序列号,可以自行调整。
		$strReq = $strTime.$randNum;

		/* 商家订单号,长度若超过32位，取前32位。财付通只记录商家订单号，不保证唯一。 */
		$out_trade_no = $formData['tradeNo'];
		
		/* 财付通交易单号，规则为：10位商户号+8位时间（YYYYmmdd)+10位流水号 */
		$transaction_id = $partner.$strDate.$strReq;
		
		/* 商品价格（包含运费），以分为单位 */
		$total_fee = round($formData['orderAmount']*100);
		
		/* 银行类型 */
		$bank_type = !empty($formData['bankCode']) ? $formData['bankCode'] : 'DEFAULT';
		
		/* 创建支付请求对象 */
		$reqHandler = new RequestHandler();
		$reqHandler->init();
		$reqHandler->setKey($key);

		//----------------------------------------
		//设置支付参数
		//----------------------------------------
		$reqHandler->setParameter("partner", $partner);
		$reqHandler->setParameter("spbill_create_ip", $formData['clientIp']); //客户端IP
		$reqHandler->setParameter("out_trade_no", $out_trade_no);
		$reqHandler->setParameter("total_fee", $total_fee);  //总金额
		$reqHandler->setParameter("return_url", $formData['returnUrl']);      //支付成功后返回
		$reqHandler->setParameter("notify_url", $formData['notifyUrl']);
		$reqHandler->setParameter("body", $formData['productDesc']);
		$reqHandler->setParameter("bank_type", $bank_type);  	              //银行类型，默认为财付通
		$reqHandler->setParameter("fee_type", "1");                           //币种
		$reqHandler->setParameter('attach', self::$attach);
		$reqHandler->setParameter('input_charset', $this->charset);
		$reqHandler->setParameter('sign_type', $this->sign_type);           //签名类型MD5 or RSA
		
		//请求的URL
		//$reqUrl = $reqHandler->getRequestURL();
		$reqHandler->setGateURL(self::$payUrl);
		$reqUrl = $reqHandler->getGateUrl();
		$reqHandler->createSign();

		//for post method
		$html = '';
		$html .=  '<form id="tenpayform" method="post" action="'.$reqUrl.'">';
		$params = $reqHandler->getAllParameters();
		foreach($params as $key=>$value){
			$html .=  '<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
		}
		$html .=  '</form>';
		$html .=  '<script>document.getElementById("tenpayform").submit();</script>';
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
		$payResult = false;
		include_once (self::$_includePath ."/tenpay/ResponseHandler.class.php");
		include_once (self::$_includePath ."/tenpay/RequestHandler.class.php");
		include_once (self::$_includePath ."/tenpay/client/ClientResponseHandler.class.php");
		include_once (self::$_includePath ."/tenpay/client/TenpayHttpClient.class.php");
		
		/* 商户号 */
		$partner = $data['memberID'];
		
		/* 密钥 */
		$key = $data['privateKey'];
		
		/* 创建支付应答对象 */
		$resHandler = new ResponseHandler();
		$resHandler->setKey($key);
		
		//判断签名
		if($resHandler->isTenpaySign()) {
			
			//通知id
			$notify_id = $resHandler->getParameter("notify_id");
		
			//通过通知ID查询，确保通知来至财付通
			//创建查询请求
			$queryReq = new RequestHandler();
			$queryReq->init();
			$queryReq->setKey($key);
			$queryReq->setGateUrl("https://gw.tenpay.com/gateway/verifynotifyid.xml");
			$queryReq->setParameter("partner", $partner);
			$queryReq->setParameter("notify_id", $notify_id);
			$queryReq->setParameter('sign_type', $this->sign_type);           //签名类型MD5 or RSA
		
			//通信对象
			$httpClient = new TenpayHttpClient();
			$httpClient->setTimeOut(5);
			//设置请求内容
			$httpClient->setReqContent($queryReq->getRequestURL());
		
			//后台调用
			if($httpClient->call()) {
				//设置结果参数
				$queryRes = new ClientResponseHandler();
				// exmaple:  itouzi_tenpay BL 0 0 1 GBK 13813095392709 1900000109 1 0 F443FEAE85D2E28528C9321B2EEB8E59 1 MD5 20131009170636 1 1 0 1900000109201310090370383604 0
				$queryRes->setContent($httpClient->getResContent());
				$queryRes->setKey($key);
					
				//判断签名及结果
				$isTenpaySign = $queryRes->isTenpaySign();
				$retcode = $queryRes->getParameter("retcode");
				$trade_state = $queryRes->getParameter("trade_state");
				$trade_mode = $queryRes->getParameter("trade_mode");
				//只有签名正确,retcode为0，trade_state为0才是支付成功
				if($isTenpaySign && $retcode == "0" && $trade_state == "0" && $trade_mode == "1" ) {
					//echo "success";
					$payResult = true;
					// notice 数据
					$this->_noticeData = $resHandler->getAllParameters();
				} else {
					$payResult = false; //echo "fail";
					$this->_errorInfo = 'fail';
				}
			} else {
				$payResult = false; //echo "fail";
				$this->_errorInfo = 'verify notify by notifyid request failed';
			}
		} else {
			$payResult = false;//"fail";
			$this->_errorInfo = 'Verify signature is not consistent.';
		}
		if($this->_errorInfo) Yii::log("thirdpay error info :tenpay notice :".$this->_errorInfo." notice data :".print_r($resHandler->getAllParameters(),true), "error");
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
		$payResult = false;
		include_once (self::$_includePath ."/tenpay/ResponseHandler.class.php");
		include_once (self::$_includePath ."/tenpay/RequestHandler.class.php");
		include_once (self::$_includePath ."/tenpay/client/ClientResponseHandler.class.php");
		include_once (self::$_includePath ."/tenpay/client/TenpayHttpClient.class.php");
		
		/* 商户号 */
		$partner = $data['memberID'];
		/* 密钥 */
		$key = $data['privateKey'];
		
		/* 创建支付应答对象 */
		$resHandler = new ResponseHandler();
		$resHandler->setKey($key);
		
		//判断签名
		if($resHandler->isTenpaySign()) {
			
			# 确认该请求来自 财付通
			//通知id
			$notify_id = $resHandler->getParameter("notify_id");
			
			//通过通知ID查询，确保通知来至财付通
			//创建查询请求
			$queryReq = new RequestHandler();
			$queryReq->init();
			$queryReq->setKey($key);
			$queryReq->setGateUrl("https://gw.tenpay.com/gateway/verifynotifyid.xml");
			$queryReq->setParameter("partner", $partner);
			$queryReq->setParameter("notify_id", $notify_id);
			$queryReq->setParameter('sign_type', $this->sign_type);           //签名类型MD5 or RSA
			
			//通信对象
			$httpClient = new TenpayHttpClient();
			$httpClient->setTimeOut(5);
			//设置请求内容
			$httpClient->setReqContent($queryReq->getRequestURL());

			//后台调用
			if($httpClient->call()) {
				//设置结果参数
				$queryRes = new ClientResponseHandler();
				$queryRes->setContent($httpClient->getResContent());
				$queryRes->setKey($key);
				
				//判断签名及结果
				$isTenpaySign = $queryRes->isTenpaySign();
				$retcode = $queryRes->getParameter("retcode");
				$trade_state = $queryRes->getParameter("trade_state");
				$trade_mode = $queryRes->getParameter("trade_mode");

				// var_dump(array($isTenpaySign,$retcode,$trade_state,$trade_mode));die;

				//只有签名正确,retcode为0，trade_state为0才是支付成功
				if( $isTenpaySign && $retcode == "0" && $trade_state == "0" && $trade_mode == "1" ) {
					//echo "success" when notice;
					$payResult = true;
					# return数据
					$this->_returnData = $resHandler->getAllParameters();
				} else {
					//echo "fail" when notice;
					$payResult = false;
					$this->_errorInfo = 'tenpayClass->returnResult : fail';
				}
			} else {
				$payResult = false; //echo "fail";
				$this->_errorInfo = 'tenpayClass->returnResult:httpClient->call : fail';
			}
		} else {
			$payResult = false;
			$this->_errorInfo = 'tenpayClass->returnResult : Verify signature error';
		}
		if($this->_errorInfo) Yii::log("thirdpay error info :tenpay return :".$this->_errorInfo." return data :".print_r($resHandler->getAllParameters(),true), "error");
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
		include_once (self::$_includePath ."/tenpay/ResponseHandler.class.php");
		$resHandler = new ResponseHandler();
		
		$this->_noticeData = $resHandler->getAllParameters();
		
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
		include_once (self::$_includePath ."/tenpay/ResponseHandler.class.php");
		$resHandler = new ResponseHandler();
		
		$this->_returnData = $resHandler->getAllParameters();
		
		return $this->_returnData;
	}

	public function getNoticeSuccessCode(){
		return $this->noticeSuccessCode;
	}

	public function getNoticeFailCode(){
		return $this->noticeFailCode;
	}
	
	public function getErrorInfo() {
		return $this->_errorInfo;
	}

}
