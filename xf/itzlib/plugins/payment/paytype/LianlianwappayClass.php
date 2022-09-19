<?php

require_once (dirname(__FILE__)."/../include/lianlianpay/llpay_core.function.php");
require_once (dirname(__FILE__)."/../include/lianlianpay/llpay_md5.function.php");
require_once (dirname(__FILE__)."/../include/lianlianpay/llpay_rsa.function.php");

class LianlianwappayClass {
    public $name = 'lianlianzhifu';
	public $logo = 'hnapay';
	public $description = "连连支付";
	protected static $submitUrl = 'https://yintong.com.cn';
    //测试地址
	protected static $qasubmitUrl = 'https://test.yintong.com.cn';

	protected $_signType = 'RSA';
	public $_errorInfo = NULL;
	public $charset = 'UTF-8';
	// notify 通知成功返回数据
	public $noticeSuccessCode = "";
	// notify 通知失败返回数据
	public $noticeFailCode = 'fail';
	
	public $debug = false;
	
	protected $_noticeData = array();
	protected $_returnData = array();
    
	function buildRequestPara($para_temp) {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = paraFilter($para_temp);
        //对待签名参数数组排序
        $para_sort = argSort($para_filter);

        //生成签名结果
        $mysign = $this->buildRequestMysign($para_sort);
        //签名结果与签名方式加入请求提交参数组中
        $para_sort['sign'] = $mysign;
        $para_sort['sign_type'] = strtoupper(trim($this->_signType));
        foreach ($para_sort as $key => $value) {
            $para_sort[$key] = $value;
        }
        return $para_sort;
        // return urldecode(json_encode($para_sort));
    }

    function buildRequestMysign($para_sort) {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = createLinkstring($para_sort);
        
        return RsaSign($prestr);
    }

    function getSignVeryfy($para_temp, $sign) {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = paraFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = argSort($para_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = createLinkstring($para_sort);

        return Rsaverify($prestr, $sign);
    }

    //WAP端银行卡绑定Form
	public function buildForm(&$formData) {
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
            'sign_type'    => $this->_signType,
        );

        $post_data = $this->buildRequestPara($post_data);

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
		# 支付成功 or not
        $payResult = false;

        //判断POST来的数组是否为空
        if (empty ($_POST)) {
            $this->_errorInfo = "POST is empty";
        }else{
            //获得待验签数据
            $parameter = $this->getReturnData();
            //首先对获得的商户号进行比对
            if (trim($parameter['oid_partner']) != $data['memberID']) {
                $this->_errorInfo = "oid_partner error";
            }else{
                if (!$this->getSignVeryfy($parameter, trim($parameter['sign' ]))) {
                    $this->_errorInfo = "Verify signature error";
                }else{
                    if( $parameter['result_pay'] == 'SUCCESS' ){
                        $payResult = true;
                    }else{
                        $this->_errorInfo = $parameter["result_pay"];
                    }
                }
            }
        }
        if($this->_errorInfo) Yii::log("thirdpay error info :lianlian return :".$this->_errorInfo." return data :".print_r($_POST,true), "error");
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

		$_POST_t = json_decode($_POST['res_data'],true);
		
		$this->_returnData = array();
		$this->_returnData['oid_partner'] = $_POST_t["oid_partner"];
		$this->_returnData['sign_type']   = $_POST_t["sign_type"];
		$this->_returnData['sign']        = $_POST_t["sign"];
		$this->_returnData['dt_order']    = $_POST_t["dt_order"];
		$this->_returnData['no_order']    = $_POST_t["no_order"];
		$this->_returnData['oid_paybill'] = $_POST_t["oid_paybill"];
		$this->_returnData['money_order'] = $_POST_t["money_order"];
		$this->_returnData['result_pay']  = $_POST_t["result_pay"];
		$this->_returnData['settle_date'] = isset($_POST_t["settle_date"])?$_POST_t["settle_date"]:"";
		$this->_returnData['info_order']  = isset($_POST_t["info_order"])?$_POST_t["info_order"]:"";
		$this->_returnData['pay_type']    = isset($_POST_t["pay_type"])?$_POST_t["pay_type"]:"";
		$this->_returnData['bank_code']   = isset($_POST_t["bank_code"])?$_POST_t["bank_code"]:"";
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
		# 支付成功 or not
        $payResult = false;

        # 待验签的数据
        $notice_data = $this->getNoticeData();
        
        # 首先对获得的商户号进行比对
        if ($notice_data['oid_partner'] != $data['memberID']) {
            $this->_errorInfo = "oid_partner error";
        }else{
            # 验签
            if (!$this->getSignVeryfy($notice_data, $notice_data['sign'])) {
                $this->_errorInfo = "Verify signature error";
            }else{
                # 支付是否成功
                if( $notice_data['result_pay'] == 'SUCCESS' ){
                    $this->noticeSuccessCode = json_encode(array("ret_code" => "0000","ret_msg" => "交易成功"));
                    $payResult = true;
                }else{
                    $this->_errorInfo = $notice_data["result_pay"];
                }
            }
        }
        if($this->_errorInfo) Yii::log("thirdpay error info :lianlian return :".$this->_errorInfo." notice data :".print_r($notice_data,true), "error");
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
        // Yii::trace($str);
        $val = json_decode($str,1);
        // Yii::trace(print_r($val,true));
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
