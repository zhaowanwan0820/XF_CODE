<?php

class LianlianwappayClass {
    public $name = 'lianlianzhifu';
	public $logo = 'hnapay';
	public $description = "连连支付";
	protected static $submitUrl = 'https://yintong.com.cn';
    //测试地址
	protected static $qasubmitUrl = 'https://test.yintong.com.cn';

	protected $_signType = 'MD5';
	public $_errorInfo = NULL;
	public $charset = 'UTF-8';
	// notify 通知成功返回数据
	public $noticeSuccessCode = "";
	// notify 通知失败返回数据
	public $noticeFailCode = 'fail';
	
	public $debug = false;
	
	protected $_noticeData = array();
	protected $_returnData = array();
    
    //WAP端银行卡绑定Form
	public function buildForm(&$formData){
		$submitUrl = $this->debug ? self::$qasubmitUrl : self::$submitUrl;
        $submitUrl .= '/llpayh5/authpay.htm';
        
        $post_data = array(
            'version'       => '1.1',
            'oid_partner'   => $formData['memberID'],
            'user_id'       => $formData['user_id'],
            'app_request'   => '3',
            'busi_partner'  => '101001',
            'no_order'      => $formData['trade_no'],
            'dt_order'      => date("YmdHis"),
//            'name_goods'    => 'WAP充值',
            'money_order'   => $formData['money'],
            'notify_url'    => $formData['notifyUrl'],
            'url_return'    => $formData['returnUrl'],
            'no_agree'      => $formData['no_agree'],
//            'id_type'      => $formData['id_type'],
            'id_no'        => $formData['id_no'],
            'acct_name'    => $formData['acct_name'],
            'card_no'      => $formData['card_no'],
            'sign_type'    => 'MD5',
        );
        $post_data_order = $post_data;
        ksort($post_data_order);
        reset($post_data_order);

        $param = '';
        foreach ($post_data_order AS $key => $val){
            if($val){
                 $param .= "$key=" .$val. "&";
            }
        }
        $param    = substr($param, 0, -1). '&key=' . $formData['privateKey'];
        $post_data["sign"]    = md5($param);    //签名字符串 不可空
        $req_data = json_encode($post_data);
        
        Yii::trace($req_data);
        $html  = '<form id="lianlian"  method="post" action="'.$submitUrl.'">';
        $html .= "<input type='hidden' name='req_data' value='" .$req_data. "' />";
        $html .= "</form>";
        $html .=  '<script>document.getElementById("lianlian").submit();</script>';
        
        return $html;
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
			$srcSign = md5($src);
			if($srcSign == $noticeData['sign']) {
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
			if ($noticeData["result_pay"] == 'SUCCESS'){
				$payResult = true;
			} else{
				$payResult = false;
				$this->_errorInfo = $noticeData["result_pay"];
			} 
		} else {
			$payResult = false;
			$this->_errorInfo = 'Verify signature is not consistent.';
		}
        if($this->_errorInfo) Yii::log("thirdpay error info :lianlianwap return:".$this->_errorInfo." noticedata :".print_r($noticeData,true)
        ." src :".$src." md5 src: ".md5($src),"error");
		return $payResult;
	}
    
	/**
	 * 获取支付回调参数
	 * @return boolean
	 */
	public function &getReturnData() {
		if(!empty($this->_returnData)) {
			return $this->_returnData;
		}
        $str = $_POST['res_data'];
        Yii::trace($str);
        $val = json_decode($str,true);
        Yii::trace(print_r($val,true));
        $this->_returnData['oid_partner'] = $val["oid_partner"];
        $this->_returnData['sign_type']   = $val["sign_type"];
        $this->_returnData['sign']        = $val["sign"];
        $this->_returnData['dt_order']    = $val["dt_order"];
        $this->_returnData['no_order']    = $val["no_order"];
        $this->_returnData['oid_paybill'] = $val["oid_paybill"];
        $this->_returnData['money_order'] = $val["money_order"];
        $this->_returnData['result_pay']  = $val["result_pay"];
        $this->_returnData['settle_date'] = isset($val["settle_date"])?$val["settle_date"]:"";
        $this->_returnData['info_order']  = isset($val["info_order"])?$val["info_order"]:"";
        $this->_returnData['pay_type']    = isset($val["pay_type"])?$val["pay_type"]:"";
        $this->_returnData['bank_code']   = isset($val["bank_code"])?$val["bank_code"]:"";
		return $this->_returnData;
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
			$srcSign = md5($src);
			if($srcSign == $noticeData['sign']) {
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
            if ($noticeData["result_pay"] == 'SUCCESS'){
                $this->noticeSuccessCode = json_encode(array("ret_code" => "0000","ret_msg" => "交易成功"));
                $payResult = true;
            } else{
                $payResult = false;
                $this->_errorInfo = $noticeData["result_pay"];
            } 
        } else {
            $payResult = false;
            $this->_errorInfo = 'Verify signature is not consistent.';
        }
		if($this->_errorInfo) Yii::log("thirdpay error info :lianlian notice: ".$this->_errorInfo." noticedata :".print_r($noticeData,true)
        ." src :".$src."md5 src:".md5($src),"error");
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
		
        $str = file_get_contents("php://input");
        Yii::trace($str);
        $val = json_decode($str,1);
        Yii::trace(print_r($val,true));
        $this->_noticeData['oid_partner'] = $val["oid_partner"];
        $this->_noticeData['sign_type']   = $val["sign_type"];
        $this->_noticeData['sign']        = $val["sign"];
        $this->_noticeData['dt_order']    = $val["dt_order"];
        $this->_noticeData['no_order']    = $val["no_order"];
        $this->_noticeData['oid_paybill'] = $val["oid_paybill"];
        $this->_noticeData['money_order'] = $val["money_order"];
        $this->_noticeData['result_pay']  = $val["result_pay"];
        $this->_noticeData['settle_date'] = isset($val["settle_date"])?$val["settle_date"]:"";
        $this->_noticeData['info_order']  = isset($val["info_order"])?$val["info_order"]:"";
        $this->_noticeData['pay_type']    = isset($val["pay_type"])?$val["pay_type"]:"";
        $this->_noticeData['bank_code']   = isset($val["bank_code"])?$val["bank_code"]:"";
		return $this->_noticeData;
	}
	
	public function getErrorInfo() {
		return $this->_errorInfo;
	}
    
    public function getNoticeSuccessCode(){
        return $this->noticeSuccessCode; 
    }
	
}
