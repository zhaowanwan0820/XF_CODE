<?php
/**
 * 贝付
 */
class EbatongClass{
    protected $paymentNid   = 'ebatong';

    #key
    protected $publicKey        = '';
    protected $input_charset    = 'utf-8';
    protected $sign_type        = 'MD5';

    #商户号
    protected $partner          = '';

    #ebatong url
    protected $request_url      = 'https://www.ebatong.com';

    #回调url
    protected $notify_url       = 'https://www.xxx.com/newuser/paymentNotify/Ebatong';

	public function __construct() {

        #设置配置信息
        $this->getPaymentConfig();
    }

    #充值
    public function recharge($data,$cardInfo,$userInfo){

        $params = array(

            #基础参数
            'service'       =>'ebatong_mp_dyncode',
            'partner'       =>$this->partner,#商户号
            'input_charset' =>$this->input_charset,
            'sign_type'     =>$this->sign_type,

            #业务参数
            'customer_id'   =>$userInfo['hash_id'],#客户号
            'out_trade_no'  =>$data['trade_no'],#外部请求编号
            'amount'        =>$data['money'],#金额（元，精度两位）

            #绑卡才需要
            'card_no'                   =>$cardInfo['card_number'],#卡号
            'real_name'                 =>$userInfo['realname'],#用户名字
            'cert_no'                   =>$userInfo['card_id'],#证件号码
            'cert_type'                 =>'01',#证件类
            'bank_code'                 =>$this->getBankCode($cardInfo['card_number']),#银行代码(见附录一.银行列表)
            'card_bind_mobile_phone_no' =>$cardInfo['phone']#卡绑定手机号
            );
        $result = $this->request('/mobileFast/getDynNum.htm',$params);
        if(!empty($result['code']))
        {
            $result['data']['send'] = $params;
            $result['data']['get']  = $result;
            return $result;
        }

        #成功
        if($result['result']=='T')
        {
            $result['send'] = $params;
            $result['get']  = $result;
            return $this->returnSuccess($result);
        }

        #失败
        if(empty($result['error_message']))
        {
            $result['error_message'] = '系统异常，请重新充值';
        }
        
        $params['send'] = $params;
        $params['get']  = $result;
        return $this->returnError($result['error_message'],$params);
    }

    #充值确认
    public function rechargeVerify($data,$cardInfo,$userInfo){

        #获取交易 token
        $dynamic_code_token = '';
        $recharge=AccountRecharge::model()->findByAttributes(array('user_id'=>$data['user_id'],'trade_no'=>$data['trade_no']));
        if(empty($recharge)){
            return $this->returnError('此交易不存在');
        }

        if(!empty($recharge['api_once_return']))
        {
            $return = json_decode($recharge['api_once_return'],true);
             $dynamic_code_token = $return['token'];
        }

        $params = array(

            #基础参数
            'service'       =>'create_direct_pay_by_mp',
            'partner'       =>$this->partner,#商户号
            'input_charset' =>$this->input_charset,
            'sign_type'     =>$this->sign_type,
            'notify_url'    =>$this->notify_url,

            #业务参数
            'customer_id'               =>$userInfo['hash_id'],#客户号
            'dynamic_code_token'        =>$dynamic_code_token,#令牌（请求编号）
            'dynamic_code'              =>$data['verify_code'],#验证码
            'out_trade_no'              =>$data['trade_no'],#外部请求编号
            'total_fee'                 =>$recharge['money'],#交易金额(与验证码填写的金额一致)

            #身份信息
            'bank_card_no'              =>$cardInfo['card_number'],#卡号
            'real_name'                 =>$userInfo['realname'],#用户名字
            'cert_no'                   =>$userInfo['card_id'],#证件号码
            'cert_type'                 =>'01',#证件类型
            'card_bind_mobile_phone_no' =>$cardInfo['phone'],#卡绑定手机号
            'default_bank'              =>$this->getBankCode($cardInfo['card_number']),#默认网银(银行列表)

            #商品信息
            'subject'                   =>'ITZ充值',#商品名称
            'body'                      =>'ITZ充值',#商品描述
            'show_url'                  =>'https://www.xxx.com/',#商品展示网址
            'pay_method'                =>'',#默认支付方式
            'exter_invoke_ip'           =>$_SERVER['REMOTE_ADDR'],#订单IP
            'anti_phishing_key'         =>'',#防钓鱼时间戳，调用时间戳查询接口获取
            'extra_common_param'        =>'',#公用回传参数
            'extend_param'              =>'',#公用业务扩展参数
            );

        #防钓鱼时间戳，调用时间戳查询接口获取
        $anti_phishing_key = $this->getPhishingKey();
        if(empty($anti_phishing_key))
        {
            return $this->returnError('贝付：获取时间戳失败');
        }
        $params['anti_phishing_key'] = $anti_phishing_key;

        #支付确认
        $result = $this->request('/mobileFast/pay.htm',$params);
        if(!empty($result['code']))
        {
            $result['data']['send'] = $params;
            $result['data']['get']  = $result;
            return $result;
        }

        #成功
        if($result['result']=='T' || $result['result']=='P')
        {
            $result['usr_pay_agreement_id']='';
            $result['send'] = $params;
            $result['get']  = $result;
            return $this->returnSuccess($result);
        }

        #失败
        if(empty($result['error_message']))
        {
            $result['error_message'] = '系统异常，请重新充值';
        }
        $params['send'] = $params;
        $params['get']  = $result;
        return $this->returnError($result['error_message'],$params);
    }

    #绑卡并充值
    public function bindAndRecharge($data,$safeCard,$userInfo){
        return $this->recharge($data,$safeCard,$userInfo);
    }
    
    #绑卡并充值确认
    public function bindAndRechargeVerify($data,$safeCard,$userInfo){
        return $this->rechargeVerify($data,$safeCard,$userInfo);
    }
    
    #绑卡并充值_重新发送验证码
    public function bindAndRechargeSms($data,$safeCard,$userInfo){
        $data['send'] = '';
        $data['get'] = '';
        return $this->returnSuccess($data);
    }

    #充值_重新发送验证码
    public function rechargeSms($data,$safeCard,$userInfo){
        $data['send'] = '';
        $data['get'] = '';
        return $this->returnSuccess($data);
    }

    //用户绑卡
    public function bindCard($data,$userInfo){
        return $this->returnError('贝付不支持绑卡');
    }
    
    //绑卡确认
    public function bindVerfy($data){
        return $this->returnError('贝付不支持绑卡');
    }
    
    //解绑
    public function bankCardUnbind($data,$userInfo,$safe_card){

        $params = array(

            #基础参数
            'service'       =>'ebatong_mp_unbind',
            'partner'       =>$this->partner,#商户号
            'input_charset' =>$this->input_charset,
            'sign_type'     =>$this->sign_type,
            'notify_url'    =>'',

            #业务参数
            'customer_id'   =>$userInfo['hash_id'],#客户号
            'out_trade_no'  =>'ub'.time().rand(10000,99999),#外部请求编号

            #绑卡才需要
            'bank_card_no'              =>$safe_card['card_number'],#卡号
            'subject'                   =>'',#描述信息
            'card_bind_mobile_phone_no' =>''#卡绑定手机号
            );
        $result = $this->request('/mobileFast/unbind.htm',$params);
        if(!empty($result['code']))
            return $result;

        #成功
        if($result['result']=='T')
        {
            return $this->returnSuccess($result);
        }

        #失败
        if(empty($result['error_message']))
        {
            $result['error_message'] = '系统异常，请重新充值';
        }
        $result['send'] = $params;
        $result['get']  = $result;
        return $this->returnError($result['error_message']);
    }

    //获取支付通知结果
    public function noticeResult($data){}
    
    //获取支付回调结果
    public function returnResult($data){}
    
    //获取支付通知参数
    public function getNoticeData(){
        
        $str = file_get_contents("php://input");
        $val = json_decode($str,true);
        $sign = $val['sign'];
        unset($val['sign']);
        $params = $this->addSign($val);
        if($sign!=$params['sign'])
        {
            Yii::log('Ebatong_call_back_POST_check_sign_error:'.$str,'error');
            exit('check sign error');
        }
        return $val;
    }

    #失败
    public function returnError($msg,$data = array()){
        return array(
            'code'  =>100,
            'msg'   =>$msg,
            'data'  =>$data
            );
    }

    #成功
    public function returnSuccess($data){
        return array(
            'code'  =>0,
            'msg'   =>'操作成功',
            'data'  =>$data
            );
    }

    #获得银行编码
    public function getBankCode($card_number){

        $factory=new ExpressPaymentFactory;

        #查寻银行卡接口
        $is_yeepay = '2';
        if($is_yeepay=='1')
        {
            //查寻银行卡接口（怡宝）
            $yeepay=$factory->getPayment('yeepay');#ebatong
            $bank_name =$yeepay->bankcardCheck($card_number);
        }
        else
        {
            //查寻银行卡接口（连连）
            Yii::import('itzlib.plugins.payment.*');
            $lianlian=$factory->getPayment('lianlianpay');
            $abc=$lianlian->bankCardQuery($card_number);
            if($abc->ret_code == '0000'){
                $bank_name['bankname'] = $abc->bank_name;
            }
        }
        $bank_code = '';
        if(strpos('.'.$bank_name['bankname'],'招'))
        {
            $bank_code = 'CMB_D_B2C';
        }
        if(strpos('.'.$bank_name['bankname'],'工商'))
        {
            $bank_code = 'ICBC_D_B2C';
        }
        if(strpos('.'.$bank_name['bankname'],'农业'))
        {
            $bank_code = 'ABC_D_B2C';
        }
        if(strpos('.'.$bank_name['bankname'],'建设'))
        {
            $bank_code = 'CCB_D_B2C';
        }
        if(strpos('.'.$bank_name['bankname'],'民生'))
        {
            $bank_code = 'CMBCD_D_B2C';
        }
        if(strpos('.'.$bank_name['bankname'],'中国银行'))
        {
            $bank_code = 'BOCSH_D_B2C';
        }
        if(strpos('.'.$bank_name['bankname'],'兴业'))
        {
            $bank_code = 'CIB_D_B2C';
        }
        if(strpos('.'.$bank_name['bankname'],'光大'))
        {
            $bank_code = 'CEB_D_B2C';
        }
        if(strpos('.'.$bank_name['bankname'],'广'))
        {
            $bank_code = 'GDB_D_B2C';
        }
        return $bank_code;
    }

    #获得时间戳
    public function getPhishingKey(){
        $params = array(

            #基础参数
            'service'       =>'query_timestamp',
            'partner'       =>$this->partner,#商户号
            'input_charset' =>$this->input_charset,
            'sign_type'     =>$this->sign_type,
            );
        return $this->request('/gateway.htm',$params,false);
    }
    
    #签名
    public function addSign($data){

        ksort($data);
        $data['sign'] = md5(urldecode(http_build_query($data)).$this->publicKey);
        return $data;

    }

    //传入请求地址函数，用于向指定url传入数据
    public function request($path, $params,$json = true){

        if(empty($path) || empty($params))
        {
            return $this->returnError('请求贝付前参数错误');
        }

        #添加签名
        $params = $this->addSign($params);

        if($json)
        {
            $params = json_encode($params);
        }

        #开始请求
        $curl = curl_init(); 
        curl_setopt($curl, CURLOPT_URL, $this->request_url.$path); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);

        #贝付如果 404
        if (curl_errno($curl)) {
            return $this->returnError('贝付:'.curl_error($curl));
        }

        #结束请求
        curl_close($curl);


        #处理结果

        #json
        if($result)
        {
            if(is_array(json_decode($result,true)))
            {
                return json_decode($result,true);
            }
            else
            {
                $xml = simplexml_load_string($result);
                if(!empty($xml->response->timestamp->encrypt_key))
                {
                    return strval($xml->response->timestamp->encrypt_key);
                }
            }
            return $this->returnError('贝付:异常'.$result);
        }
        else
        {
            return $this->returnError('贝付:系统异常');
        }
    }
    
    #获取商户信息
    protected function getPaymentConfig(){
        if(empty($this->partner)){            
            $paymentRecord = Payment::model()->findByAttributes(array('nid' => $this->paymentNid));
            if(empty($paymentRecord)){
                Yii::log('ebatong GetInfoByNid error:'.$this->paymentNid,'error');
            }else{
                $config = unserialize($paymentRecord->config);
                $this->publicKey    = $config['publicKey'];
                $this->partner      = $config['partner'];
            }
        }
    }

    #用户签约信息查询API接口
    public function getBindCrasList($info,$userInfo) {

        #快捷卡信息
        $cardInfo = ItzSafeCard::model()->findByAttributes(array('user_id'=>$userInfo['user_id']));
        if(empty($cardInfo))
        {
            return array();
        }
        
        #获取银行信息
        $bank = ItzBank::model()->findByAttributes(array("bank_id"=>$cardInfo['bank_id']));
        if(empty($bank['bank_name']))
        {
            return array();
        }

        $bank_list = array();
        $params = array(

            #基础参数
            'service'       =>'query_bind_card_info',
            'partner'       =>$this->partner,#商户号
            'input_charset' =>$this->input_charset,
            'sign_type'     =>$this->sign_type,

            #业务参数
            'customer_id'   =>$userInfo['hash_id']#客户号
            );
        $result = $this->request('/mobileFast/queryCardInfo.htm',$params);
        if($result['result']=='T' && !empty($result['card_bind_list']))
        {
            #一个用户只能绑定一张银行卡
            $info = explode('^',$result['card_bind_list']);
            $bank_list[] = array(
                'channel_name'  =>'贝付支付',
                'bank_name'     =>$bank['bank_name'],
                'card_no'       =>$info['0'],
                'no_agree'      =>'无',
                'tel'           =>$info['2']
                );
        }
        return $bank_list;
    }
}