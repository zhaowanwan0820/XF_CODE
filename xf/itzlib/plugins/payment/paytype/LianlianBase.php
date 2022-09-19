<?php
/**
 * 连连签约认证处理的基类
 */
class LianlianBase {
	public $name = 'lianlianzhifu';#x
	public $logo = 'hnapay';#x
	public $description = "连连支付";
    public $paymentNid = 'lianlianpay';
    private $_paymentConfig;

    protected $_signType = 'MD5';
	public $_errorInfo = NULL;
	public $charset = 'UTF-8';
	
	public $noticeSuccessCode = "";// notify 通知成功返回数据
	
	public $noticeFailCode = 'fail';// notify 通知失败返回数据
	
	public $debug = false;
	
	protected $_noticeData = array();
	protected $_returnData = array();
    
    protected static $requestHost = 'https://yintong.com.cn';
	protected static $qarequestHost = 'https://test.yintong.com.cn';//测试地址
    protected static $retcode = array(
        '0000' => array('info'=>'交易成功','show'=>true),
        '1000' => array('info'=>'支付已超时，请重新支付','show'=>true),
        '1002' => array('info'=>'支付服务超时，请重新支付','show'=>true),
        '1003' => array('info'=>'该笔交易高风险，终止支付，请联系客服','show'=>true),
        '1005' => array('info'=>'支付处理失败','show'=>true),
        '1006' => array('info'=>'用户中途取消支付操作','show'=>true),
        '1007' => array('info'=>'网络连接繁忙','show'=>true),
        '1014' => array('info'=>'日累计金额或笔数超限','show'=>true),
        '1019' => array('info'=>'单笔金额超限','show'=>true),
        '1100' => array('info'=>'您输入的卡号无效，请重新输入','show'=>true),
        '1101' => array('info'=>'您输入的卡号无效，请重新输入','show'=>true),
        '1102' => array('info'=>'您输入的卡号无效，请重新输入','show'=>true),
        '1103' => array('info'=>'您的卡已过期或者您输入的有效期不正确','show'=>true),
        '1104' => array('info'=>'银行卡密码错误','show'=>true),
        '1105' => array('info'=>'您输入的卡号无效','show'=>true),
        '1106' => array('info'=>'允许的输入PIN次数超限','show'=>true),
        '1107' => array('info'=>'您的银行卡暂不支持在线支付业务','show'=>true),
        '1108' => array('info'=>'您输入的证件号、姓名或手机号有误','show'=>true),
        '1109' => array('info'=>'卡号和证件号不符','show'=>true),
        '1110' => array('info'=>'银行卡状态异常','show'=>true),
        '1111' => array('info'=>'交易异常，支付失败','show'=>true),
        '1112' => array('info'=>'证件号有误','show'=>true),
        '1113' => array('info'=>'持卡人姓名有误','show'=>true),
        '1114' => array('info'=>'手机号有误','show'=>true),
        '1115' => array('info'=>'该卡未预留手机号','show'=>true),
        '1900' => array('info'=>'验证码校验错误','show'=>true),
        '1901' => array('info'=>'验证码已失效','show'=>true),
        '2004' => array('info'=>'签约处理中','show'=>true),
        '2005' => array('info'=>'原交易已在进行处理，请勿重复提交','show'=>true),
        '2006' => array('info'=>'交易已过期','show'=>true),
        '2007' => array('info'=>'交易已支付成功','show'=>true),
        '2008' => array('info'=>'交易处理中','show'=>true),
        '3001' => array('info'=>'该卡不支持，请使用如下银行储蓄卡支付：农业银行，工商银行，招商银行，中国银行，建设银行，光大银行，华夏银行，平安银行，浦发银行','show'=>true),
        '3002' => array('info'=>'该卡不支持，请使用如下银行储蓄卡支付：农业银行，工商银行，招商银行，中国银行，建设银行，光大银行，华夏银行，平安银行，浦发银行','show'=>true),
        '3003' => array('info'=>'签约失败','show'=>true),
        '3004' => array('info'=>'解约失败','show'=>true),
        '3005' => array('info'=>'该卡不支持，请使用如下银行储蓄卡支付：农业银行，工商银行，招商银行，中国银行，建设银行，光大银行，华夏银行，平安银行，浦发银行','show'=>true),
        '3006' => array('info'=>'无效的银行卡信息','show'=>true),
        '3007' => array('info'=>'用户信息查询失败','show'=>true),
        '4000' => array('info'=>'解约失败，请联系发卡行','show'=>true),
        '5001' => array('info'=>'银行卡bin校验失败','show'=>true),
        '5002' => array('info'=>'原始交易不存在','show'=>true),
        '5003' => array('info'=>'退款金额错误','show'=>true),
        '5005' => array('info'=>'退款失败，请重试','show'=>true),
        '5007' => array('info'=>'累计退款金额大于原交易金额','show'=>true),
        '5008' => array('info'=>'原交易未成功','show'=>true),
        '5502' => array('info'=>'信用卡不支持提现','show'=>true),
        '6001' => array('info'=>'卡余额不足','show'=>true),
        '6002' => array('info'=>'该卡号未成功进行首次验证','show'=>true),
        '8000' => array('info'=>'用户信息不存在','show'=>true),
        '8001' => array('info'=>'用户状态异常','show'=>true),
        '8888' => array('info'=>'交易申请成功,需要再次进行验证','show'=>true),
        '9091' => array('info'=>'创建支付失败，请稍后重试','show'=>true),
        '9093' => array('info'=>'无对应的支付单信息，请稍后重试。','show'=>true),
        '9094' => array('info'=>'请求银行扣款失败','show'=>true),
        '9700' => array('info'=>'短信验证码错误','show'=>true),
        '9701' => array('info'=>'短信验证码和手机不匹配','show'=>true),
        '9702' => array('info'=>'验证码错误次数超过最大次数,请重新获取进行验证','show'=>true),
        '9703' => array('info'=>'短信验证码失效,请重新获取','show'=>true),
        '9704' => array('info'=>'短信发送异常,请稍后重试','show'=>true),
        '9910' => array('info'=>'您的支付状态异常，请稍后再试','show'=>true),
        '9911' => array('info'=>'金额超过指定额度','show'=>true),
        '9912' => array('info'=>'该卡不支持，请使用如下银行储蓄卡支付：农业银行，工商银行，招商银行，中国银行，建设银行，光大银行，华夏银行，平安银行，浦发银行','show'=>true),
        '9913' => array('info'=>'该卡已签约成功','show'=>true),
        '9970' => array('info'=>'银行系统繁忙，请稍后重试','show'=>true),
        '9990' => array('info'=>'银行交易出错，请稍后重试','show'=>true),
        '9907' => array('info'=>'选择的银行与卡所属银行不一致','show'=>true),
        '9000' => array('info'=>'银行维护中，请稍后再试','show'=>true),
        '1200' => array('info'=>'该卡不支持，请使用如下银行储蓄卡支付：农业银行，工商银行，招商银行，中国银行，建设银行，光大银行，华夏银行，平安银行，浦发银行','show'=>true),
        '1016' => array('info'=>'交易金额超限','show'=>true),
        '1001' => array('info'=>'商户请求签名错误','show'=>false),
        '1004' => array('info'=>'商户请求参数校验错误','show'=>false),
        '1008' => array('info'=>'商户请求IP错误','show'=>false),
        '1009' => array('info'=>'暂停商户支付服务，请联系连连银通客服','show'=>false),
        '5004' => array('info'=>'商户状态异常','show'=>false),
        '5006' => array('info'=>'商户账户余额不足','show'=>false),
        '5501' => array('info'=>'大额行号查询失败','show'=>false),
        '8901' => array('info'=>'没有记录','show'=>false),
        '8911' => array('info'=>'没有风控记录','show'=>false),
        '9901' => array('info'=>'请求报文非法','show'=>false),
        '9902' => array('info'=>'请求参数缺失{0}','show'=>false),
        '9903' => array('info'=>'请求参数错误{0}','show'=>false),
        '9904' => array('info'=>'支付参数和原创建支付单参数不一致','show'=>false),
        '9092' => array('info'=>'业务信息非法','show'=>false),
        '9902' => array('info'=>'接口调用异常','show'=>false),
        '9999' => array('info'=>'系统错误','show'=>false),
    );
    
    // 获取第三方支付配置数据
    public function getPaymentConfig(){
        if(empty($this->_paymentConfig)){            
            $paymentRecord = Payment::model()->findByAttributes(array('nid' => $this->paymentNid));
            if(empty($paymentRecord)){
                Yii::log('LianlianBase GetInfoByNid error:'.$this->paymentNid,'error');
            }else{
                $paymentConfig = unserialize($paymentRecord->config);
                $this->_paymentConfig = array(
                    'memberId' => $paymentConfig['member_id'],
                    'privateKey' => $paymentConfig['PrivateKey'],
                );
            }
        }
        return $this->_paymentConfig;
        
    }
    
    //数据进行签名
    public function sign($data,$privateKey) {
		$signStr = array();
		foreach ($data as $key => $value) {
			$signStr[$key] = "$key=$value";
		}
		ksort($signStr);
		$signStr["key"] = "key=".$privateKey;
		$str = implode("&", $signStr);
		$data["sign"] = md5($str);
		return $data;
	}
    
    public function request($path, $data) {
		$context = array('http' => array(
                'method' => 'POST',  
                'header'  => 'Content-type: application/json',
                'content' => $data
            )
        );  
 		$context  = stream_context_create($context);
        $requestUrl = self::$requestHost . $path;
		$retjson = file_get_contents($requestUrl,false,$context);
        $retobj = json_decode($retjson);
        if(empty($retobj) || $retobj->ret_code != '0000'){
            Yii::log('lianlianBaseError:'.$requestUrl.' '.$data.' '.$retjson, 'error');
        }
		$retobj->ret_msg = $this->codetoString($retobj->ret_code, $retobj->ret_msg);
		return $retobj;
	}
    
    public function codetoString($code,$msg){
        if(isset(self::$retcode[$code])){
            if(self::$retcode[$code]['show']){
                return self::$retcode[$code]['info'].'['.$code.']';
            }else{
                return '爱亲，操作未成功，请稍后重试。['.$code.']';
            }
        }else{
            return '爱亲，操作未成功，请稍后重试。['.$code.']';
        }
    }
    
    //用户绑卡
    public function bindCard($card,$phone,$user_id) {
        $paymentConfig = $this->getPaymentConfig();
		$userInfo = UserService::getInstance()->getUser($user_id);
		$data = array(
			"oid_partner"=> $paymentConfig['memberId'],
			"card_no"    =>$card,
			"acct_name"  => $userInfo["realname"],
			"bind_mob"   =>$phone,
			"id_type"    =>"0",
			"id_no"      =>$userInfo["card_id"],
			"pay_type"	=> "D",
			"sign_type" =>"MD5",
			"user_id" => $user_id,
			"api_version" => "2.1",
		);
		$data = $this->sign($data,$paymentConfig['privateKey']);
		$data = json_encode($data);  //把参数转换成URL数据  

 		return $this->request("/traderapi/bankcardbind.htm", $data);
	}
	
	
	
	//绑卡确认
	public function bindVerfy($vcode, $token, $user_id) {
        $paymentConfig = $this->getPaymentConfig();
		$data = array(
			"oid_partner" => $paymentConfig['memberId'],
			"token"    => $token,
			"sign_type" =>"MD5",
			"user_id" => $user_id,
			"verify_code" => $vcode,
		); 
		
		$data = $this->sign($data,$paymentConfig['privateKey']);
		$data = json_encode($data);

 		return $this->request("/traderapi/bankcardbindverfy.htm", $data);
	}
	
    //解绑
	public function bankCardUnbind($no_agree, $user_id) {
        $paymentConfig = $this->getPaymentConfig();
		$data = array(
			"oid_partner" => $paymentConfig['memberId'],
			"no_agree"    => $no_agree,
			"sign_type" =>"MD5",
			"pay_type" => "D",
			"user_id" => $user_id
		);
		$data = $this->sign($data,$paymentConfig['privateKey']);
		$data = json_encode($data);
 		return $this->request("/traderapi/bankcardunbind.htm", $data);
	}
	
    //此函数返回用户在连连绑了多少张卡，但是我们的网站只允许用户绑定一张银行卡
	public function userBankCard($user_id) {
        $paymentConfig = $this->getPaymentConfig();
		$data = array(
			"oid_partner" => $paymentConfig['memberId'],
			"sign_type" =>"MD5",
			"user_id" => $user_id,
			"pay_type" => "D",
			"offset" => 1
		); 
		
		$data = $this->sign($data,$paymentConfig['privateKey']);
		$data = json_encode($data);
 		return $this->request("/traderapi/userbankcard.htm", $data);
	}
	
    //
	public function bankCardQuery($card_number) {
        $paymentConfig = $this->getPaymentConfig();
		$data = array(
			"oid_partner" => $paymentConfig['memberId'],
			"sign_type" =>"MD5",
			"card_no" => $card_number
		); 
		
		$data = $this->sign($data,$paymentConfig['privateKey']);
		$data = json_encode($data);

 		$result = $this->request("/traderapi/bankcardquery.htm", $data);
		
		return $result;
	}
}
