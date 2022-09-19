<?php

class LianlianbindClass {

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
        $submitUrl .= '/llpayh5/signApply.htm';
        
        $post_data = array(
            'version'      => '1.1',
            'oid_partner'  => $formData['memberID'],
            'user_id'      => $formData['user_id'],
            'app_request'  => '3',
            'id_type'      => $formData['id_type'],
            'id_no'        => $formData['id_no'],
            'acct_name'    => $formData['acct_name'],
            'card_no'      => $formData['card_no'],
            'url_return'   => $formData['returnUrl'],
            'sign_type'    => 'MD5',
        );
        $post_data_order = $post_data;
        ksort($post_data_order);
        reset($post_data_order);

        $param = '';
        foreach ($post_data_order AS $key => $val){
            $param .= "$key=" .$val. "&";
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
		
		$returnData = $this->getReturnData();
        if(isset($returnData['status']) && $returnData['status'] != '0000'){
            $this->_errorInfo = $returnData['result'];
            return false;
        }
        
		$srcArr = array();
		foreach($returnData as $key => $value) {
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
			if($srcSign == $returnData['sign']) {
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

        if($this->_errorInfo) Yii::log("thirdpay error info :lianlianbind return: ".$this->_errorInfo,"error");
		return $verifyResult;
	}   
    
	/**
	 * 获取WAP绑定回调参数
	 * @return boolean
	 */
	public function &getReturnData() {
		if(!empty($this->_returnData)) {
			return $this->_returnData;
		}
		$this->_returnData = array();
		
        Yii::trace(json_encode($_GET));
        if($_GET['status'] != '0000'){
            $this->_returnData['status']      = $_GET['status'];
            $this->_returnData['result']      = $_GET['result'];
        }else{
            $val = json_decode($_GET['result'],true);
            Yii::trace(print_r($val,true));
            $this->_returnData['oid_partner'] = $val['oid_partner'];
            $this->_returnData['user_id']     = $val['user_id'];
            $this->_returnData['agreeno']     = $val['agreeno'];
            $this->_returnData['sign_type']   = $val['sign_type'];
            $this->_returnData['sign']        = $val['sign'];
        }
		return $this->_returnData;
	}
	
	public function getErrorInfo() {
		return $this->_errorInfo;
	}
    
    public function getNoticeSuccessCode(){
        return $this->noticeSuccessCode; 
    }
	
}
