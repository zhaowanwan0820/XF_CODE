<?php

require_once (dirname(__FILE__)."/../include/kuaiqianpay/kqpay_core.function.php");
require_once (dirname(__FILE__)."/../include/kuaiqianpay/kqpay_rsa.function.php");

class KuaiqianpayClass {

	public $name = 'kuaiqianzhifu';
	
	public $description = "快钱";

    // 提交地址
	protected static $submitUrl = 'https://www.99bill.com/gateway/recvMerchantInfoAction.htm';

    // 测试地址
	protected static $qasubmitUrl = 'https://sandbox.99bill.com/gateway/recvMerchantInfoAction.htm';

	public $charset = 'UTF-8';

	// notify 通知成功返回数据
	public $noticeSuccessCode = "";

	// notify 通知失败返回数据
	public $noticeFailCode = '<result>0</result><redirecturl></redirecturl>';
	
	public $debug = false;

    public $_errorInfo = NULL;
	
	protected $_noticeData = array();
	protected $_returnData = array();
	
    /**
     * 快钱参数拼接
     */
    function kq_ck_null($kq_va,$kq_na){
        if($kq_va == ""){
            return $kq_va="";
        }else{
            return $kq_va=$kq_na.'='.$kq_va.'&';
        }
    }

    /**
     * 获取返回时的签名验证结果
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @return 签名验证结果
     */
    function getSignVeryfy($para_temp, $sign) {
        
        $kq_check_all_para=$this->kq_ck_null($para_temp[merchantAcctId],'merchantAcctId');
        //网关版本，固定值：v2.0,该值与提交时相同。
        $kq_check_all_para.=$this->kq_ck_null($para_temp[version],'version');
        //语言种类，1代表中文显示，2代表英文显示。默认为1,该值与提交时相同。
        $kq_check_all_para.=$this->kq_ck_null($para_temp[language],'language');
        //签名类型,该值为4，代表PKI加密方式,该值与提交时相同。
        $kq_check_all_para.=$this->kq_ck_null($para_temp[signType],'signType');
        //支付方式，一般为00，代表所有的支付方式。如果是银行直连商户，该值为10,该值与提交时相同。
        $kq_check_all_para.=$this->kq_ck_null($para_temp[payType],'payType');
        //银行代码，如果payType为00，该值为空；如果payType为10,该值与提交时相同。
        $kq_check_all_para.=$this->kq_ck_null($para_temp[bankId],'bankId');
        //商户订单号，,该值与提交时相同。
        $kq_check_all_para.=$this->kq_ck_null($para_temp[orderId],'orderId');
        //订单提交时间，格式：yyyyMMddHHmmss，如：20071117020101,该值与提交时相同。
        $kq_check_all_para.=$this->kq_ck_null($para_temp[orderTime],'orderTime');
        //订单金额，金额以“分”为单位，商户测试以1分测试即可，切勿以大金额测试,该值与支付时相同。
        $kq_check_all_para.=$this->kq_ck_null($para_temp[orderAmount],'orderAmount');
        // 快钱交易号，商户每一笔交易都会在快钱生成一个交易号。
        $kq_check_all_para.=$this->kq_ck_null($para_temp[dealId],'dealId');
        //银行交易号 ，快钱交易在银行支付时对应的交易号，如果不是通过银行卡支付，则为空
        $kq_check_all_para.=$this->kq_ck_null($para_temp[bankDealId],'bankDealId');
        //快钱交易时间，快钱对交易进行处理的时间,格式：yyyyMMddHHmmss，如：20071117020101
        $kq_check_all_para.=$this->kq_ck_null($para_temp[dealTime],'dealTime');
        //商户实际支付金额 以分为单位。比方10元，提交时金额应为1000。该金额代表商户快钱账户最终收到的金额。
        $kq_check_all_para.=$this->kq_ck_null($para_temp[payAmount],'payAmount');
        //费用，快钱收取商户的手续费，单位为分。
        $kq_check_all_para.=$this->kq_ck_null($para_temp[fee],'fee');
        //扩展字段1，该值与提交时相同
        $kq_check_all_para.=$this->kq_ck_null($para_temp[ext1],'ext1');
        //扩展字段2，该值与提交时相同。
        $kq_check_all_para.=$this->kq_ck_null($para_temp[ext2],'ext2');
        //处理结果， 10支付成功，11 支付失败，00订单申请成功，01 订单申请失败
        $kq_check_all_para.=$this->kq_ck_null($para_temp[payResult],'payResult');
        //错误代码 ，请参照《人民币网关接口文档》最后部分的详细解释。
        $kq_check_all_para.=$this->kq_ck_null($para_temp[errCode],'errCode');

        $trans_body=substr($kq_check_all_para,0,strlen($kq_check_all_para)-1);

        return Rsaverify($trans_body, $sign);
    }

    /**
    * 构造支付请求表单
    * @param array $formData
    * 
    */
	public function buildForm(&$formData){
		
		if($this->debug) {    // 测试入口
			$submitUrl = self::$qasubmitUrl;
		}else{                // 正式入口
			$submitUrl = self::$submitUrl;
		}

        # 待签数据
        // 人民币网关账号，该账号为11位人民币网关商户编号+01,该参数必填。
        $merchantAcctId = $formData['memberID'];
        // 编码方式，1代表 UTF-8; 2 代表 GBK; 3代表 GB2312 默认为1,该参数必填。
        $inputCharset = "1";
        // 接收支付结果的页面地址。
        $pageUrl = $formData['returnUrl'];
        // 服务器接收支付结果的后台地址，该参数务必填写，不能为空。
        $bgUrl = $formData['notifyUrl'];
        // 网关版本，固定值：v2.0,该参数必填。
        $version =  "v2.0";
        // 语言种类，1代表中文显示，2代表英文显示。默认为1,该参数必填。
        $language =  "1";
        // 签名类型,该值为4，代表PKI加密方式,该参数必填。(RSA)
        $signType =  "4";
        // 商户订单号，商户可以根据自己订单号的定义规则来定义该值，不能为空。
        $orderId = $formData['tradeNo'];
        // 订单金额，金额以“分”为单位，商户测试以1分测试即可，切勿以大金额测试。该参数必填。
        $orderAmount = $formData['orderAmount'] * 100;
        // 订单提交时间，格式：yyyyMMddHHmmss，如：20071117020101，不能为空。
        $orderTime = date("YmdHis");
        // 商品名称，可以为空。
        $productName= $formData['productName'];
        // 支付方式，一般为00，代表所有的支付方式。如果是银行直连商户，该值为10，必填。
        $payType = "10";
        // 银行代码，如果payType为00，该值可以为空；如果payType为10，该值必须填写，具体请参考银行列表。
        $bankId = $formData['bankCode'];
        // 同一订单禁止重复提交标志，实物购物车填1，虚拟产品用0。1代表只能提交一次，0代表在支付不成功情况下可以再提交。可为空。
        $redoFlag = "0";
        // 付款者IP
        $payerIP = $formData['clientIp'];

        # sign the message...
        $kq_all_para=$this->kq_ck_null($inputCharset,'inputCharset');
        $kq_all_para.=$this->kq_ck_null($pageUrl,"pageUrl");
        $kq_all_para.=$this->kq_ck_null($bgUrl,'bgUrl');
        $kq_all_para.=$this->kq_ck_null($version,'version');
        $kq_all_para.=$this->kq_ck_null($language,'language');
        $kq_all_para.=$this->kq_ck_null($signType,'signType');
        $kq_all_para.=$this->kq_ck_null($merchantAcctId,'merchantAcctId');
        //$kq_all_para.=$this->kq_ck_null($payerIP,'payerIP');
        $kq_all_para.=$this->kq_ck_null($orderId,'orderId');
        $kq_all_para.=$this->kq_ck_null($orderAmount,'orderAmount');
        $kq_all_para.=$this->kq_ck_null($orderTime,'orderTime');
        $kq_all_para.=$this->kq_ck_null($productName,'productName');
        $kq_all_para.=$this->kq_ck_null($payType,'payType');
        $kq_all_para.=$this->kq_ck_null($bankId,'bankId');
        $kq_all_para.=$this->kq_ck_null($redoFlag,'redoFlag');

        $kq_all_para=substr($kq_all_para,0,strlen($kq_all_para)-1);
        
        // 签名串
        $signMsg = RsaSign($kq_all_para);
        
        $html  = '<form id="kqPay"  method="post" action="'.$submitUrl.'">';
        $html .= "<input type='hidden' name='inputCharset'      value='" . $inputCharset     . "' />";
        $html .= "<input type='hidden' name='pageUrl'  value='" . $pageUrl . "' />";
        $html .= "<input type='hidden' name='bgUrl'      value='" . $bgUrl     . "' />";
        $html .= "<input type='hidden' name='version'    value='" . $version   ."' />";
        $html .= "<input type='hidden' name='language'    value='" . $language   . "' />";
        $html .= "<input type='hidden' name='signType'         value='" . $signType        . "' />";
        $html .= "<input type='hidden' name='signMsg' value='" . $signMsg. "'/>";
        $html .= "<input type='hidden' name='merchantAcctId'     value='" . $merchantAcctId    . "' />";
        //$html .= "<input type='hidden' name='payerIP'    value='" . $payerIP   . "' />";
        $html .= "<input type='hidden' name='orderId'     value='" . $orderId    . "' />";
        $html .= "<input type='hidden' name='orderAmount'  value='" . $orderAmount . "' />";
        $html .= "<input type='hidden' name='orderTime'   value='" . $orderTime  . "' />";
        $html .= "<input type='hidden' name='productName'   value='" . $productName  . "' />";
        $html .= "<input type='hidden' name='payType'   value='" . $payType  . "' />";
        $html .= "<input type='hidden' name='bankId'   value='" . $bankId  . "' />";
        $html .= "<input type='hidden' name='redoFlag'    value='" . $redoFlag   . "' />";
        $html .= "</form>";
        $html .=  '<script>document.getElementById("kqPay").submit();</script>';
        return $html;
	}
	
	/**
	 * 获取支付异步通知结果
	 * @param array $data
	 * 		memberID: 商户ID
	 * @return boolean
	 */
	public function noticeResult($data) {
        # 支付成功 or not
        $payResult = false;

        # 待验签的数据
        $notice_data = $this->getNoticeData();
        
        # 首先对获得的商户号进行比对
        if ($notice_data['merchantAcctId'] != $data['memberID']) {
            $this->_errorInfo = "merchantAcctId error";
        }else{
            # 验签
            if (!$this->getSignVeryfy($notice_data, $notice_data['signMsg'])) {
                $this->_errorInfo = "Verify signature error";
            }else{
                # 支付是否成功
                if( $notice_data['payResult'] == '10' ){
                    $this->noticeSuccessCode = "<result>1</result><redirecturl>$data[rtnUrl]</redirecturl>";
                    $payResult = true;
                }else{
                    $this->_errorInfo = $notice_data['payResult'];
                }
            }
        }
        if($this->_errorInfo) Yii::log("thirdpay error info :kuaiqianpay notice:".$this->_errorInfo." notice data :".print_r($notice_data,true), "error");
        return $payResult;
	}
	
	/**
	 * 获取支付同步回调结果
	 * @param array $data
	 * 		memberID: 商户ID
	 * @return boolean
	 */
	public function returnResult($data) {
        # 支付成功 or not
        $payResult = false;

        //获得待验签数据
        $parameter = $this->getReturnData();
        //首先对获得的商户号进行比对
        if (trim($parameter['merchantAcctId']) != $data['memberID']) {
            $this->_errorInfo = "merchantAcctId error";
        }else{
            if(!$this->getSignVeryfy($parameter, $parameter['signMsg'])){
                $this->_errorInfo = "Verify signature error";
            }else{
                if( $parameter['payResult'] == '10' ){
                    $payResult = true;
                }else{
                    $this->_errorInfo = $parameter["payResult"];
                }
            }
        }
        if($this->_errorInfo) Yii::log("thirdpay error info :kuaiqian return :".$this->_errorInfo." return data :".print_r($parameter,true), "error");
        return $payResult;
	}
	
	/**
	 * 获取支付异步通知参数
	 * @return array
	 */
	public function &getNoticeData() {
		if(!empty($this->_noticeData)) {
			return $this->_noticeData;
		}
		$this->_noticeData = array();
		
        /* GET */
        foreach($_GET as $k => $v) {
            $this->_noticeData[$k] = $v;
        }

		return $this->_noticeData;
	}
	
	/**
	 * 获取支付同步回调参数
	 * @return array
	 */
	public function &getReturnData() {
		if(!empty($this->_returnData)) {
			return $this->_returnData;
		}
        $this->_returnData = array();

		/* GET */
        foreach($_GET as $k => $v) {
            $this->_returnData[$k] = $v;
        }
		return $this->_returnData;
	}
	
	public function getErrorInfo() {
		return $this->_errorInfo;
	}
    
    public function getNoticeSuccessCode(){
        return $this->noticeSuccessCode; 
    }

    public function getNoticeFailCode(){
        return $this->noticeFailCode;
    }
	
}
