<?php

class WeibopayClass {

	public $name = 'weibozhifu';
	public $logo = 'weibopay';
	public $description = "微支付";
	protected static $submitUrl = 'https://gate.pay.sina.com.cn/acquire-order-channel/gateway/receiveOrderLoading.htm';
    //测试地址
	protected static $qasubmitUrl = 'https://testgate.pay.sina.com.cn/acquire-order-channel/gateway/receiveOrderLoading.htm';

	protected $_signType = 'MD5';
	public $_errorInfo = NULL;
	public $charset = 'UTF-8';
	// notify 通知成功返回数据
	public $noticeSuccessCode = "";
	// notify 通知失败返回数据
	public $noticeFailCode = '';
	
	public $debug = false;
	
	protected $_noticeData = array();
	protected $_returnData = array();
	
    /**
    * 构造支付请求表单
    * @param array $formData
    */
	public function buildForm(&$formData){
		
		$buildformData = array(); 
		
		if($this->debug) {// 测试入口
			$submitUrl = self::$qasubmitUrl;
		} else {// 正式入口
			$submitUrl = self::$submitUrl;
		}
        $post_data = array(
            'inputCharset'   =>  '1',
            'bgUrl'          =>  $formData['notifyUrl'],
            'version'        =>  'v2.3',
            'language'       =>  '1',
            'signType'       =>  '1',
            //'merchantAcctId' =>  '200100100120000373358401101', //测试环境的对公地址，没有抽成变量
            'merchantAcctId' =>  '200100100120000665976000001',
            "orderId"        =>  $formData["tradeNo"],
            "orderAmount"    =>  round($formData["orderAmount"]*100), //以分为单位
            "orderTime"      =>  date("YmdHis"),
            "productName"    =>  "ITZ",
            "bankId"         =>  $formData["bankCode"],
            "pid"            =>  $formData['memberID'],
        );
        $param = '';
        foreach ($post_data AS $key => $val){
            if($key!="signMsg" && isset($val) && @$val!=""){
                 $param .= "$key=" .$val. "&";
            }
        }
        $param    = substr($param, 0, -1). '&key=' . $formData['privateKey'];//echo $param;die;
        $post_data["signMsg"]    = strtolower(md5($param));    //签名字符串 不可空
        
        $html  = '<form id="weibo"  method="get" action="'.$submitUrl.'">';
        foreach($post_data as $key=>$value){
            if(isset($value) && !empty($value)){
                $html .= "<input type='hidden' name='".$key."'  value='" . $value     . "' />";
            }
        }
        $html .= "</form>";
        $html .=  '<script>document.getElementById("weibo").submit();</script>';
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
			$this->_errorInfo = 'data has not privateKey.';
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
            if($key!="signMsg" && !is_null($value) && @$value!="") {
                $srcArr[$key] = $value;
            }
        }
        $src = '';
        foreach ($srcArr AS $key => $val){
                 $src .= "$key=" .$val. "&";
        }
        $src = substr($src, 0, -1). '&key=' . $data['privateKey'];
		
		$verifyResult = false;
		if($signType == 'MD5') {
			$srcSign = strtolower(md5($src));
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
            if ($noticeData["payResult"] == 10){ //success
                $this->noticeSuccessCode = '<result>1</result><redirecturl><![CDATA['.Yii::app()->createUrl("/newuser/PaymentReturn/weibopaySuccess").']]></redirecturl>';
                $payResult = true;
            } else{
                $payResult = false;
                $this->noticeFailCode ='<result>1</result><redirecturl><![CDATA['.Yii::app()->createUrl("/newuser/PaymentReturn/weibopayFaild").']]></redirecturl>';
                $this->_errorInfo = $noticeData["payResult"];
            } 
        } else {
            $payResult = false;
            $this->_errorInfo = 'Verify signature is not consistent.';
        }
		if($this->_errorInfo) Yii::log("thirdpay error info :".$this->_errorInfo,"error");
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
			$this->_errorInfo = 'data has not privateKey.';
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
			if($key != 'sign' && $value) {
				$srcArr[$key] = $value;
			}
		}
        ksort($srcArr);
        reset($srcArr);
        $src = '';
        foreach ($srcArr AS $key => $val){
            if($val){
                 $src .= "$key=" .$val. "&";
            }
        }
        $src = substr($src, 0, -1). '&key=' . $data['privateKey'];
		
		$verifyResult = false;
		if($signType == 'MD5') {
			$srcSign = strtolower(md5($src));
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
			if ($noticeData["payResult"] == 10){
				$payResult = true;
			} else{
				$payResult = false;
				$this->_errorInfo = $noticeData["payResult"];
			} 
		} else {
			$payResult = false;
			$this->_errorInfo = 'Verify signature is not consistent.';
		}
        if($this->_errorInfo) Yii::log("thirdpay error info :".$this->_errorInfo,"error");
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
		
        $this->_noticeData["merchantAcctId"]=  @$_REQUEST["merchantAcctId"];
        $this->_noticeData["version"]       =  @$_REQUEST["version"];
        $this->_noticeData["language"]      =  @$_REQUEST["language"];
        $this->_noticeData["signType"]      =  @$_REQUEST["signType"];
        $this->_noticeData["payType"]       =  @$_REQUEST["payType"];
        $this->_noticeData["bankId"]        =  @$_REQUEST["bankId"];
        $this->_noticeData["orderId"]       =  @$_REQUEST["orderId"];
        $this->_noticeData["orderTime"]     =  @$_REQUEST["orderTime"];
        $this->_noticeData["orderAmount"]   =  @$_REQUEST["orderAmount"];
        $this->_noticeData["dealId"]        =  @$_REQUEST["dealId"];
        $this->_noticeData["bankDealId"]    =  @$_REQUEST["bankDealId"];
        $this->_noticeData["dealTime"]      =  @$_REQUEST["dealTime"];
        $this->_noticeData["payAmount"]     =  @$_REQUEST["payAmount"];
        $this->_noticeData["fee"]           =  @$_REQUEST["fee"];
        $this->_noticeData["ext1"]          =  @$_REQUEST["ext1"];
        $this->_noticeData["ext2"]          =  @$_REQUEST["ext2"];
        $this->_noticeData["payResult"]     =  @$_REQUEST["payResult"];
        $this->_noticeData["payIp"]         =  @$_REQUEST["payIp"];
        $this->_noticeData["errCode"]       =  @$_REQUEST["errCode"];
        $this->_noticeData["signMsg"]       =  @$_REQUEST["signMsg"];
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
        //返回通知参数说明 ，请见参考文档
        $this->_returnData["merchantAcctId"]=  @$_REQUEST["merchantAcctId"];
        $this->_returnData["version"]       =  @$_REQUEST["version"];
        $this->_returnData["language"]      =  @$_REQUEST["language"];
        $this->_returnData["signType"]      =  @$_REQUEST["signType"];
        $this->_returnData["payType"]       =  @$_REQUEST["payType"];
        $this->_returnData["bankId"]        =  @$_REQUEST["bankId"];
        $this->_returnData["orderId"]       =  @$_REQUEST["orderId"];
        $this->_returnData["orderTime"]     =  @$_REQUEST["orderTime"];
        $this->_returnData["orderAmount"]   =  @$_REQUEST["orderAmount"];
        $this->_returnData["dealId"]        =  @$_REQUEST["dealId"];
        $this->_returnData["bankDealId"]    =  @$_REQUEST["bankDealId"];
        $this->_returnData["dealTime"]      =  @$_REQUEST["dealTime"];
        $this->_returnData["payAmount"]     =  @$_REQUEST["payAmount"];
        $this->_returnData["fee"]           =  @$_REQUEST["fee"];
        $this->_returnData["ext1"]          =  @$_REQUEST["ext1"];
        $this->_returnData["ext2"]          =  @$_REQUEST["ext2"];
        $this->_returnData["payResult"]     =  @$_REQUEST["payResult"];
        $this->_returnData["payIp"]         =  @$_REQUEST["payIp"];
        $this->_returnData["errCode"]       =  @$_REQUEST["errCode"];
        $this->_returnData["signMsg"]       =  @$_REQUEST["signMsg"];
		return $this->_returnData;
	}
	
	public function getErrorInfo() {
		return $this->_errorInfo;
	}
    
    public function getNoticeSuccessCode(){
        return $this->noticeSuccessCode; 
    }
	
}
