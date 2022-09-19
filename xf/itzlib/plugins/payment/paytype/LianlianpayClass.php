<?php

require_once (dirname(__FILE__)."/../include/lianlianpay/llpay_core.function.php");
require_once (dirname(__FILE__)."/../include/lianlianpay/llpay_md5.function.php");
require_once (dirname(__FILE__)."/../include/lianlianpay/llpay_rsa.function.php");

class LianlianpayClass {

	public $name = 'lianlianzhifu';
	public $logo = 'hnapay';
	public $description = "连连支付";
    // 提交地址
	//protected static $submitUrl = 'https://yintong.com.cn/payment/bankgateway.htm';
    // 测试地址
	//protected static $qasubmitUrl = 'https://test.yintong.com.cn/payment/bankgateway.htm';
	// 提交地址 20170427lianlianpay域名变更接口更新
	protected static $submitUrl = 'https://cashier.lianlianpay.com/payment/bankgateway.htm';
	// 测试地址 20170427lianlianpay域名变更接口更新
	protected static $qasubmitUrl = 'https://test.cashier.lianlianpay.com/payment/bankgateway.htm';

    // 签名方式
	protected $_signType = 'RSA';

    // 签名为'MD5'时签名用的key
    protected $_key = '';

	public $_errorInfo = NULL;
	public $charset = 'UTF-8';
	// notify 通知成功返回数据
	public $noticeSuccessCode = "";
	// notify 通知失败返回数据
	public $noticeFailCode = 'fail';
	
	public $debug = false;
	
	protected $_noticeData = array();
	protected $_returnData = array();

    /**
    *获取密钥
    */
    function getKey() {
        return $this->_key;
    }
    
    /**
    *设置密钥
    */
    function setKey($key) {
        $this->_key = $key;
    }
	
    /**
     * 生成要请求给连连支付的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组
     */
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

    /**
     * 生成签名结果
     * @param $para_sort 已排序要签名的数组
     * return 签名结果字符串
     */
    function buildRequestMysign($para_sort) {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = createLinkstring($para_sort);
        
        $mysign = "";
        switch (strtoupper(trim($this->_signType))) {
            case "MD5" :
                $mysign = md5Sign($prestr, $this->_key);
                break;
            case "RSA" :
                $mysign = RsaSign($prestr);
                break;
            default :
                $mysign = "";
        }
        // file_put_contents("log.txt","签名:".$mysign."\n", FILE_APPEND);
        return $mysign;
    }

    /**
     * 获取返回时的签名验证结果
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @return 签名验证结果
     */
    function getSignVeryfy($para_temp, $sign) {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = paraFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = argSort($para_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = createLinkstring($para_sort);

        //file_put_contents("log.txt", "原串:" . $prestr . "\n", FILE_APPEND);
        //file_put_contents("log.txt", "sign:" . $sign . "\n", FILE_APPEND);
        $isSgin = false;
        switch (strtoupper(trim($this->_signType))) {
            case "MD5" :
                $isSgin = md5Verify($prestr, $sign, $this->_key);
                break;
            case "RSA" :
                $isSgin = Rsaverify($prestr, $sign);
                break;
            default :
                $isSgin = false;
        }
        return $isSgin;
    }

    /**
    * 构造支付请求表单
    * @param array $formData
    * <form action="https://test.yintong.com.cn/payment/bankgateway.htm" method="post">
    * 1<input type="text" name="version" value="1.0"> 版本号:1.0
    * 1<input type="text" name="oid_partner" value="201304121000001004"> 商户编号是商户在连连钱包支付平台上开设的商户号码，为18位数字，如：201304121000001004，此商户号为支付交易时的商户号
    * 1<input type="text" name="user_id" value="111111">该用户在商户系统中的唯一编号，要求是该编号在商户系统中唯一标识该用户
    * 1<input type="text" name="busi_partner" value="101001"> 虚拟商品销售：101001 实物商品销售：109001
    * 1<input type="text" name="no_order" value="2013099129111111"> 商户系统唯一订单号
    * 1<input type="text" name="timestamp" value="20130224120224"> 格式：YYYYMMDDH24MISS 14位数字，精确到秒
    * 1<input type="text" name="dt_order" value="20130224120224"> 格式：YYYYMMDDH24MISS 14位数字，精确到秒
    * 0<input type="text" name="name_goods" value="测试商品">
    * 0<input type="text" name="info_order" value="用户购买话费100元"> 订单描述
    * 1<input type="text" name="money_order" value="49.95"> 该笔订单的资金总额，单位为RMB-元。大于0的数字，精确到小数点后两位。
    * 1<input type="text" name="notify_url" value="http://domain:port/notify "> 服务器异步通知地址
    * 0<input type="text" name="url_return" value="http://domain:port/return "> 支付结束后显示的合作商户系统页面地址
    * 1<input type="text" name="userreq_ip" value="*.*.*.*"> 用户端申请IP
    * 0<input type="text" name="url_order" value="http://domain:port/orderUrl "> 合作系统中订单的详情链接地址
    * 0<input type="text" name="bank_code" value="01020000"> 8位数字具体对应的银行编号见附录 直达银行
    * 0<input type="text" name="pay_type" value="1"> 1：网银支付（借记卡）8：网银支付（信用卡）2：快捷支付（借记卡）3：快捷支付（信用卡）9：B2B企业网银支付
    * 1<input type="text" name="sign_type" value="RSA"> RSA 或者 MD5
    * 1<input type="text" name="sign" value="RSA签名结果"> RSA加密签名，见安全签名机制
    * <input type="submit" value="立即支付">
    * </form>
    * 参数的值中，不能存在影响请求或返回的数据结构的特殊字符，如：“””、“&”、“{”、“}”、“+”、“\”
    */
	public function buildForm(&$formData){

        # 记录当前通道的签名方式
        Yii::log("LianLianPay sign_type : ".strtoupper(trim($this->_signType)), "info","LianlianpayClass.buildForm");
		
		// if($this->debug) {    // 测试入口
		// 	$submitUrl = self::$qasubmitUrl;
		// }else{                // 正式入口
		// 	$submitUrl = self::$submitUrl;
		// }

        $submitUrl = self::$submitUrl;

        $this->setKey($key);

        # 待签数据
        $post_data = array(
            "version"      => "1.0",
            "sign_type"    => strtoupper(trim($this->_signType)),
            "oid_partner"  => $formData['memberID'],
            "user_id"      => substr($formData["tradeNo"],9),
            "busi_partner" => "101001",
            "no_order"     => $formData["tradeNo"],
            "timestamp"    => date("YmdHis"),
            "dt_order"     => date("YmdHis"),
            //"name_goods" =>  "",
            //"info_order" =>  "",
            "money_order"  => round($formData["orderAmount"], 2),
            "notify_url"   => $formData['notifyUrl'],
            "url_return"   => $formData['returnUrl'],
            "userreq_ip"   => $formData['clientIp'],
            //"url_order"  => "",
            //"valid_order"=> ""
            "bank_code"    => $formData["bankCode"],
            //"pay_type"   => "",
            //"risk_item"  => ""
            //"no_agree"   => ""
        );

        $post_data_t = $this->buildRequestPara($post_data);
        
        $html  = '<form id="lianlian"  method="post" action="'.$submitUrl.'">';
        $html .= "<input type='hidden' name='version'      value='" . $post_data_t["version"]     . "' />"; //版本号
        $html .= "<input type='hidden' name='oid_partner'  value='" . $post_data_t["oid_partner"] . "' />"; //支付交易商户编号
        $html .= "<input type='hidden' name='user_id'      value='" . $post_data_t["user_id"]     . "' />"; //商户用户唯一编号
        $html .= "<input type='hidden' name='timestamp'    value='" . $post_data_t["timestamp"]   ."' />";  //时间戳
        $html .= "<input type='hidden' name='sign_type'    value='" . $post_data_t["sign_type"]   . "' />"; //签名方式
        $html .= "<input type='hidden' name='sign'         value='" . $post_data_t["sign"]        . "' />"; //签名
        $html .= "<input type='hidden' name='busi_partner' value='" . $post_data_t["busi_partner"]. "'/>";  //商户业务类型
        $html .= "<input type='hidden' name='no_order'     value='" . $post_data_t["no_order"]    . "' />"; //商户唯一订单号
        $html .= "<input type='hidden' name='dt_order'     value='" . $post_data_t["dt_order"]    . "' />"; //商户订单时间
      //$html .= "<input type='hidden' name='name_goods'   value='" . $post_data["name_goods"]  . "' />"; //商品名称
      //$html .= "<input type='hidden' name='info_order'   value='" . $post_data["info_order"]  . "' />"; //订单描述
        $html .= "<input type='hidden' name='money_order'  value='" . $post_data_t["money_order"] . "' />"; //交易金额
        $html .= "<input type='hidden' name='notify_url'   value='" . $post_data_t["notify_url"]  . "' />"; //服务器异步通知地址
        $html .= "<input type='hidden' name='url_return'   value='" . $post_data_t["url_return"]  . "' />"; //支付结束回显url
        $html .= "<input type='hidden' name='userreq_ip'   value='" . $post_data_t["userreq_ip"]  . "' />"; //用户端申请IP
      //$html .= "<input type='hidden' name='url_order'    value='" . $post_data["url_order"]   . "' />"; //订单地址
      //$html .= "<input type='hidden' name='valid_order'  value='" . $post_data["valid_order"] . "' />"; //订单有效时间
        $html .= "<input type='hidden' name='bank_code'    value='" . $post_data_t["bank_code"]   . "' />"; //指定银行网银编号
      //$html .= "<input type='hidden' name='pay_type'     value='" . $post_data["pay_type"]    . "' />"; //支付方式
      //$html .= "<input type='hidden' name='risk_item'    value='" . $post_data["risk_item"]   . "' />"; //风险控制参数
      //$html .= "<input type='hidden' name='no_agree'     value='" . $post_data["no_agree"]    . "' />"; //签约协议号
        $html .= "</form>";
        $html .=  '<script>document.getElementById("lianlian").submit();</script>';
        return $html;
	}
    
    public function mobileForm(&$formData){
		
		$buildformData = array();
        $post_data = array(
            //"version"      => "1.0",
            "oid_partner"  => $formData['memberID'],
//            "user_id"      => substr($formData["tradeNo"],9),
            'user_id'      => $formData['user_id'],
            'id_no'        => $formData['id_no'],
            'acct_name'    => $formData['realname'],
            "busi_partner" => "101001",
            "no_order"     => $formData["tradeNo"],
            //"timestamp"    => date("YmdHis"),
            "dt_order"     => date("YmdHis"),
//            "name_goods"    => "ITZ充值功能",
            //"info_order" =>  "",
            "money_order"  => strval(round($formData["orderAmount"], 2)),
            "notify_url"   => $formData['notifyUrl'],
            //"url_return"   => $formData['returnUrl'],
            //"userreq_ip"   => $formData['clientIp'],
            //"url_order"  => "",
            "valid_order"=> '10080',
            'info_order' => '',
            //"bank_code"    => $formData["bankCode"],
            "pay_type"   => '2',
            //"risk_item"  => "",
            "no_agree"   => $formData['no_agree'],
            "partner_sign_type"    => "MD5", 
        );
//        $post_data_order = $post_data;
        $post_data_order = array(
            "oid_partner"  => $post_data['oid_partner'],
            "busi_partner" => $post_data['busi_partner'],
            "no_order"     => $post_data['no_order'],
            "dt_order"     => $post_data['dt_order'],
            "money_order"  => $post_data['money_order'],
            "notify_url"   => $post_data['notify_url'],
            "valid_order"=> $post_data['valid_order'],
            'info_order' => $post_data['info_order'],
            "sign_type"    => $post_data['partner_sign_type'],
        );
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
        return $post_data;
	}
	
	/**
	 * 获取支付异步通知结果
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
	 * 获取支付同步回调结果
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
                if (!$this->getSignVeryfy($parameter, trim($_POST['sign' ]))) {
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
	 * 获取支付异步通知参数
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
	
	/**
	 * 获取支付同步回调参数
	 * @return boolean
	 */
	public function &getReturnData() {
		if(!empty($this->_returnData)) {
			return $this->_returnData;
		}
		$this->_returnData = array();
		$this->_returnData['oid_partner'] = $_POST["oid_partner"];
		$this->_returnData['sign_type']   = $_POST["sign_type"];
		$this->_returnData['sign']        = $_POST["sign"];
		$this->_returnData['dt_order']    = $_POST["dt_order"];
		$this->_returnData['no_order']    = $_POST["no_order"];
		$this->_returnData['oid_paybill'] = $_POST["oid_paybill"];
		$this->_returnData['money_order'] = $_POST["money_order"];
		$this->_returnData['result_pay']  = $_POST["result_pay"];
		$this->_returnData['settle_date'] = isset($_POST["settle_date"])?$_POST["settle_date"]:"";
		$this->_returnData['info_order']  = isset($_POST["info_order"])?$_POST["info_order"]:"";
		$this->_returnData['pay_type']    = isset($_POST["pay_type"])?$_POST["pay_type"]:"";
		$this->_returnData['bank_code']   = isset($_POST["bank_code"])?$_POST["bank_code"]:"";
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
