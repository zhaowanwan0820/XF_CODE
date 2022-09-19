<?php
/**
 * 连连快捷支付
 */
Yii::import('itzlib.plugins.payment.AbstractExpressPaymentClass');//引如抽象类
Yii::import('itzlib.plugins.payment.interface.*');//引入接口类

//由于连连支付时通过sdk方式，所以要实现sdkinterface接口
class LianlianpayClass extends  AbstractExpressPaymentClass implements SdkInterface{
    protected  $requestHost = 'https://yintong.com.cn';
    protected  $qarequestHost = 'https://yintong.com.cn';
    
    public $name= 'lianlianzhifu';
    public $logo = 'hnapay';
    public $description = "连连支付";
    public $paymentNid= 'lianlian'; //原来为lianlianpay，新网为lianlian
    public $_paymentConfig;

    protected $_signType = 'MD5';
    public $_errorInfo = NULL;
    public $charset = 'UTF-8';
    
    public $noticeSuccessCode = "";
    public $noticeFailCode = 'fail';
    
    protected   $retcode = array(
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
    //数据进行签名
    protected function sign($data,$privateKey) {
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
    
    //将返回的代号换成中文
    protected function codetoString($code,$msg){
        if(isset($this->retcode[$code])){
            if($this->retcode[$code]['show']){
                return $this->retcode[$code]['info'].'['.$code.']';
            }else{
                return '爱亲，操作未成功，请稍后重试。['.$code.']';
            }
        }else{
            return '爱亲，操作未成功，请稍后重试。['.$code.']';
        }
    }
    
    //api请求url函数
    protected function request($path, $data) {
        $context = array('http' => array(
                'method' => 'POST',  
                'header'  => 'Content-type: application/json',
                'content' => $data
            )
        );  
        $context  = stream_context_create($context);
        $requestUrl = $this->requestHost . $path;
        $retjson = file_get_contents($requestUrl,false,$context);
        $retobj = json_decode($retjson);
        if(empty($retobj) || $retobj->ret_code != '0000'){
            Yii::log($this->name.' BaseError:'.$requestUrl.' '.$data.' '.$retjson, 'error');
        }
        $retobj->ret_msg = $this->codetoString($retobj->ret_code, $retobj->ret_msg);
        return $retobj;
    }    
    
    //用户绑卡 
    public function bindCard($data,$userInfo) {
        $card=$data['card'];
        $phone=$data['phone'];
        $user_id=$data['user_id'];
        
        $paymentConfig = $this->getPaymentConfig();
        #var_dump($paymentConfig);die;

        $data = array(
            "oid_partner"=> $paymentConfig['memberId'],
            "card_no"    =>$card,
            "acct_name"  => $userInfo["realname"],
            "bind_mob"   =>$phone,
            "id_type"    =>"0",
            "id_no"      =>$userInfo["card_id"],
            "pay_type"  => "D",
            "sign_type" =>"MD5",
            "user_id" => $user_id,
            "api_version" => "2.1",
        );
        #var_dump($data);die;
        $data = $this->sign($data,$paymentConfig['privateKey']);

        $data = json_encode($data);  //把参数转换成URL数据  
        $retobj=$this->request("/traderapi/bankcardbind.htm", $data);
        if($retobj->ret_code=='0000'){
            $result=array('code'=>0,'msg'=>$retobj->ret_msg,'data'=>array('bind_no'=>$retobj->token,'data'=>$data,'send'=>$data,'get'=>$retobj));
        }else{
            $result=array('code'=>1,'msg'=>$retobj->ret_msg,'data'=>array('send'=>$data,'get'=>$retobj));
        }
        return $result;
    }
    
    
    
    //绑卡确认 
    public function bindVerfy($data) {
        $vcode=$data['verify_code'];
        $token=$data['bind_no'];
        $user_id=$data['user_id'];
        
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
        $r=$this->request("/traderapi/bankcardbindverfy.htm", $data);
        if($r->ret_code=='0000'){
            $result=array('code'=>0,'msg'=>$r->ret_msg,'data'=>array(
                'send'  =>$data,
                'get'   =>$r
                ),'no_agree'=>$r->no_agree);
        }else{
            $result=array('code'=>1,'msg'=>$r->ret_msg,'data'=>array(
                'send'  =>$data,
                'get'   =>$r
                ));
        }
        return $result;
    }
    
    //解绑
    //测试ok
    public function bankCardUnbind($data,$userInfo,$safe_card) {

        $paymentConfig = $this->getPaymentConfig();
        
        $data = array(
            "oid_partner"   => $paymentConfig['memberId'],
            "no_agree"      => $data['no_agree'],
            "sign_type"     =>"MD5",
            "pay_type"      => "D",
            "user_id"       => $data['user_id']
        );
        $data = $this->sign($data,$paymentConfig['privateKey']);
        $retobj = $this->request("/traderapi/bankcardunbind.htm", json_encode($data));
        if($retobj->ret_code=='0000'){
            $result=array('code'=>0,'msg'=>$retobj->ret_msg,'data'=>$retobj);
        }else{
            $result=array('code'=>1,'msg'=>$retobj->ret_msg,'data'=>$data);
        }
        return $result;
    }
    
    //用户签约信息查询API接口
    //测试ok
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
    
    //用户签约信息查询API接口
    //测试ok
    public function getBindCrasList($info,$userInfo) {
        $paymentConfig = $this->getPaymentConfig();

        $data = array(
            "oid_partner"   => $paymentConfig['memberId'],
            "sign_type"     => "MD5",
            "user_id"       => $userInfo->user_id,
            "pay_type"      => "D",
            "offset"        => 1
        );
        $data = $this->sign($data,$paymentConfig['privateKey']);
        $data = json_encode($data);
        $result = $this->request("/traderapi/userbankcard.htm", $data);
        $bank_list = array();
        if($result->ret_code=='0000')
        {
            if($result->agreement_list)
            {
                foreach($result->agreement_list as $v)
                {
                    $bank_list[] = array(
                        'channel_name'  =>'连连支付',
                        'bank_name'     =>$v->bank_name,
                        'card_no'       =>'****'.$v->card_no,
                        'no_agree'      =>$v->no_agree,
                        'tel'           =>empty($v->bind_mobile) ? '不支持查询' : $v->bind_mobile
                        );
                }
            }
        }
        else
        {
            Yii::log('LianlianpayClass>getBindCrasList>ret_code!=000:'.json_encode($result),'error');
        }
        return $bank_list;
    }
    
    //用于api请求url函数,lianlianpay域名变更接口地址更新(更换于20170427)
    public function requestQuery($path, $data) {
    	$context = array('http' => array(
    			'method' => 'POST',
    			'header'  => 'Content-type: application/json',
    			'content' => $data
    	)
    	);
    	$context  = stream_context_create($context);
    	$requestUrl = 'https://queryapi.lianlianpay.com' . $path;
    	$retjson = file_get_contents($requestUrl,false,$context);
    	$retobj = json_decode($retjson);
    	if(empty($retobj) || $retobj->ret_code != '0000'){
    		Yii::log('lianlianBaseError:'.$requestUrl.' '.$data.' '.$retjson, 'error');
    	}
    	$retobj->ret_msg = $this->codetoString($retobj->ret_code, $retobj->ret_msg);
    	return $retobj;
    }
    
    //银行卡卡BIN查询API接口,绑定快捷卡时使用
    public function bankCardQuery($card_number) {
        $paymentConfig = $this->getPaymentConfig();
        $data = array(
            "oid_partner" => $paymentConfig['memberId'],
            "sign_type" =>"MD5",
            "card_no" => $card_number
        ); 
        
        $data = $this->sign($data,$paymentConfig['privateKey']);
        $data = json_encode($data);
        
        $result = $this->requestQuery("/bankcardbin.htm", $data);
        
        return $result;
    }
    
    /**
     * 移动端充值操作
     * @param type $formData
     * @return type
     * 
     * 返回包装的数组
     */
    public function mobileForm($formData){
        
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
            "name_goods"    => '',
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
            "partner_sign_type"    => "RSA", 
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
        #$param    = substr($param, 0, -1). '&key=' . $formData['privateKey'];
        #$post_data["sign"]    = md5($param);    //签名字符串 不可空

        #RSA
        $priKey = file_get_contents(WWW_DIR . "/itzlib/plugins/payment/include/lianlianpay/key/rsa_private_key.pem");

        //转换为openssl密钥，必须是没有经过pkcs8转换的私钥
        $res = openssl_get_privatekey($priKey);

        //调用openssl内置签名方法，生成签名$sign
        openssl_sign(substr($param, 0, -1), $sign, $res,OPENSSL_ALGO_MD5);

        //释放资源
        openssl_free_key($res);

        //base64编码
        $post_data["sign"] = base64_encode($sign);

        return $post_data;
    }
    
    
    /**
     * 获取支付通知结果
     * @param array $data
     *      memberID: 商户ID
     *      privateKey: 私钥
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
        if($this->_errorInfo) Yii::log("thirdpay error info :lianlianmobpay notice : ".$this->_errorInfo." noticedata :".print_r($noticeData,true)
        ." src :".$src." md5 src:".md5($src),"error");
        return $payResult;
    }
    
    /**
     * 获取支付回调结果
     * @param array $data
     *      memberID: 商户ID
     *      privateKey: 私钥
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
        if($this->_errorInfo) Yii::log("thirdpay error info :lianlianmobile return".$this->_errorInfo." noticedata :".print_r($noticeData,true)
        ." src :".$src." md5 src:".md5($src),"error");
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
    
    //连连支付，获取商户信息
    protected function getPaymentConfig(){
        if(empty($this->_paymentConfig)){            
            $paymentRecord = Payment::model()->findByAttributes(array('nid' => $this->paymentNid));
            if(empty($paymentRecord)){
                Yii::log($this->name.' GetInfoByNid error:'.$this->paymentNid,'error');
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
}

?>