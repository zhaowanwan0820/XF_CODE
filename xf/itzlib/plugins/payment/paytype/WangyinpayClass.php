<?php

class WangyinpayClass {

	public $name = 'wangyinzhifu';
	public $logo = 'Wangyinpay';
	public $description = "网银在线";
	protected static $submitUrl = 'https://pay3.chinabank.com.cn/PayGate';
    //测试地址
	protected static $qasubmitUrl = 'https://pay3.chinabank.com.cn/PayGate';

	protected $_signType = 'MD5';
	public $_errorInfo = NULL;
	public $charset = 'UTF-8';
	// notify 通知成功返回数据
	public $noticeSuccessCode = "ok";
	// notify 通知失败返回数据
	public $noticeFailCode = 'error';
	
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
            "v_mid"            =>  $formData['memberID'],
            "v_oid"            =>  $formData["tradeNo"],
            "v_amount"         =>  round($formData["orderAmount"],2), //以元为单位
            "v_moneytype"      =>  "CNY",
            "v_url"            =>  $formData['returnUrl'],
            
            'remark2'          =>  "[url:=".$formData['notifyUrl']."]",
            "pmode_id"         =>  $formData["bankCode"],
            
        );
        $param  =  $post_data["v_amount"].$post_data["v_moneytype"].$post_data["v_oid"].$post_data["v_mid"].$post_data["v_url"].$formData['privateKey']; 
        $post_data["v_md5info"]    = strtoupper(md5($param));    //签名字符串 不可空
        $html  = '<form id="wangyinzaixian"  method="post" action="'.$submitUrl.'">';
        foreach($post_data as $key=>$value){
            if(isset($value) && !empty($value)){
                $html .= "<input type='hidden' name='".$key."'  value='" . $value     . "' />";
            }
        }
        $html .= "</form>";
        $html .=  '<script>document.getElementById("wangyinzaixian").submit();</script>';
        return $html;
	}
	
	/**
	 * 获取支付通知结果
	 * @param array $data
	 * 		memberID: 商户ID
	 * 		privateKey: 私钥
	 * @return boolean
	 */
	public function noticeResult($data){
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
		
		$src = $noticeData["v_oid"].$noticeData["v_pstatus"].$noticeData["v_amount"].$noticeData["v_moneytype"].$data['privateKey'];
		
		$verifyResult = false;
		if($signType == 'MD5') {
			$srcSign = strtoupper(md5($src));
			if($srcSign == $noticeData['v_md5str']) {
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
            if ($noticeData["v_pstatus"] == 20){ //success
                $this->noticeSuccessCode = 'ok';
                $payResult = true;
            } else{
                $payResult = false;
                $this->noticeFailCode ='error';
                $this->_errorInfo = $noticeData["v_pstring"];
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
		
        $src = $noticeData["v_oid"].$noticeData["v_pstatus"].$noticeData["v_amount"].$noticeData["v_moneytype"].$data['privateKey'];
		
		$verifyResult = false;
		if($signType == 'MD5') {
			$srcSign = strtoupper(md5($src));
            if($srcSign == $noticeData['v_md5str']) {
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
			if ($noticeData["v_pstatus"] == 20){
				$payResult = true;
			} else{
				$payResult = false;
				$this->_errorInfo = $noticeData["v_pstring"];
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
		
        $this->_noticeData["v_oid"]         =  @trim($_POST['v_oid']);         // 商户发送的v_oid定单编号   
        $this->_noticeData["v_pmode"]       =  @trim($_POST['v_pmode']);       // 支付方式（字符串）   
        $this->_noticeData["v_pstatus"]     =  @trim($_POST['v_pstatus']);     //  支付状态 ：20（支付成功）；30（支付失败）
        $this->_noticeData["v_pstring"]     =  @trim($_POST['v_pstring']);     // 支付结果信息 ： 支付完成（当v_pstatus=20时）；失败原因（当v_pstatus=30时,字符串）； 
        $this->_noticeData["v_amount"]      =  @trim($_POST['v_amount']);      // 订单实际支付金额
        $this->_noticeData["v_moneytype"]   =  @trim($_POST['v_moneytype']);   //订单实际支付币种    
        $this->_noticeData["remark1"]       =  @trim($_POST['remark1' ]);      //备注字段1
        $this->_noticeData["remark2"]       =  @trim($_POST['remark2' ]);      //备注字段2
        $this->_noticeData["v_md5str"]      =  @trim($_POST['v_md5str' ]);     //拼凑后的MD5校验值  
        Yii::log("thirdpay return: ".print_r($this->_returnData,true),"info");
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
        $this->_returnData = array();
        $this->_returnData["v_oid"]         =  @trim($_POST['v_oid']);         // 商户发送的v_oid定单编号   
        $this->_returnData["v_pmode"]       =  @trim($_POST['v_pmode']);       // 支付方式（字符串）   
        $this->_returnData["v_pstatus"]     =  @trim($_POST['v_pstatus']);     // 支付状态 ：20（支付成功）；30（支付失败）
        $this->_returnData["v_pstring"]     =  @trim($_POST['v_pstring']);     // 支付结果信息 ： 支付完成（当v_pstatus=20时）；失败原因（当v_pstatus=30时,字符串）； 
        $this->_returnData["v_amount"]      =  @trim($_POST['v_amount']);      // 订单实际支付金额
        $this->_returnData["v_moneytype"]   =  @trim($_POST['v_moneytype']);   // 订单实际支付币种    
        $this->_returnData["remark1"]       =  @trim($_POST['remark1' ]);      // 备注字段1
        $this->_returnData["remark2"]       =  @trim($_POST['remark2' ]);      // 备注字段2
        $this->_returnData["v_md5str"]      =  @trim($_POST['v_md5str' ]);     // 拼凑后的MD5校验值  
		
		Yii::log("thirdpay return: ".print_r($this->_returnData,true),"info");
		return $this->_returnData;
	}
	
	public function getErrorInfo() {
		return $this->_errorInfo;
	}
    
    public function getNoticeSuccessCode(){
        return $this->noticeSuccessCode; 
    }
	
}
