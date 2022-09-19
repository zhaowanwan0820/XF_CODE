<?php
/**
 * 联动支付 
 */
Yii::import('itzlib.plugins.payment.AbstractExpressPaymentClass');//引如抽象类
Yii::import('itzlib.plugins.payment.interface.*');
//由于联动支付时api方式，所以需要实现ApiInterface接口
class LiandongpayClass extends AbstractExpressPaymentClass implements ApiInterface{
    protected $paymentNid= 'liandongpay';//联动支付在ITZ的nid
    //存放配置
    public $_paymentConfig=array(
          'charset' =>  'UTF-8' ,
          'mer_id' =>  '9843' ,#商户号
          'sign_type' =>  'RSA', 
          'notify_url' =>  'https://www.xxx.com/newuser/paymentNotify/liandongpay',#充值回调地址
          'res_format' =>  'HTML' ,
          'version' =>  '4.0' ,
          'goods_id' =>  '' ,
          'goods_inf' =>  '' ,
          'amt_type' =>  'RMB' ,
          'mer_priv' =>  '' ,
          'expand' =>  '' ,
          'expire_time' =>  '' ,
          'risk_expand' =>  '' ,
    );
    
    //api请求url函数
    protected function request($path,$data) {
        include_once(WWW_DIR . "/itzlib/plugins/payment/include/liandong/mer2Plat.php");
        $map = new HashMap();
        //第一次加载固定配置的东西
        foreach($this->_paymentConfig as $key=>$value){
                $map->put($key,$value);
        }
        //第二次加载本次请求的额外数据
        foreach($data as $key => $value){
               $map->put($key,$value);
        }
        //第三次加载sercice地址
        $map->put('service', $path);
        $reqData = MerToPlat::makeRequestDataByGet($map);
        $url = $reqData->getUrl();
        $html=file_get_contents($url);//获得接口地址的返回结果
        //解析返回的结果得到数组
        $preg='/<META.*NAME="(.*)".*CONTENT="(.*)">/is';
        $c=preg_match($preg,$html,$match);
        $result=array();//最终的返回值
        $arr=explode('&',$match[2]);
        foreach($arr as $v){
            $tmpArr=explode('=',$v);
            $result[$tmpArr[0]]=$tmpArr[1];
        }
        return $result;
    }
    

    
    //绑卡并充值
    //主要作用为在联动平台上生成trade_no号
    /**
     * 入参:
     * $data['trade_no'] : account_recharge表中的流水号trade_no
     * $data['money'] : 充值金额，单位是分
     */
    public function bindAndRecharge($data,$cardInfo,$userInfo){

        //得到风控信息
        $risk=array();//风控信息
        $risk['A0003']='20';
        $risk['B0002']='01';
        $risk['B0003']=$userInfo['realname'];
        $risk['B0004']=$userInfo['phone'];
        $risk['B0005']=$userInfo['card_id'];
        $risk['B0006']=$userInfo['email'];
        $risk['D0001']=$data['trade_no'];
        $risk['D0002']=$_SERVER['REMOTE_ADDR'];
        $risk['D0003']=$userInfo['hash_id'];
        $risk['D0004']=date('YmdHis',$userInfo['addtime']);
        $risk['D0005']=$data['device']==4?2:1;
        $risk['D0006']=$_SERVER['HTTP_USER_AGENT'];
        $risk_tmp=array();
        foreach($risk as $k=>$v){
            $risk_tmp[]=$k.':'.$v;
        }
        $risk_str=implode('#',$risk_tmp);
        
        
        $request=array(
          'order_id' =>  $data['trade_no'],
          'mer_date' =>  date('Ymd'),
          'amount' =>  strval($data['money']*100),
          'risk_expand'=>$risk_str,//风控信息
        );
        $r=$this->request('pay_req_shortcut',$request);

        //如果返回码正确，则开始发短信
        if($r['ret_code']=='0000'){
             $request=array(
                'trade_no'=>$r['trade_no'],
                'media_id'=>$cardInfo['phone'],
                'media_type'=>'MOBILE',
                'pay_type'=>'DEBITCARD',//目前我们只支持储蓄卡类型
                'card_id'=>$cardInfo['card_number'],
                'identity_type'=>'IDENTITY_CARD',
                'identity_code'=>$userInfo['card_id'],
                'card_holder'=>$userInfo['realname']
            );
            #var_dump($request);die;
            $smsResult=$this->request('req_smsverify_shortcut', $request);
            #var_dump($smsResult);
            if($smsResult['ret_code']!=0){
                $request['send']=$request;
                $request['get'] =$smsResult;
                $result=array('code'=>$smsResult['ret_code'],'msg'=>$smsResult['ret_msg'],'data'=>$request);
            }else{
                $r['send']=$request;
                $r['get'] =$smsResult;
                $result=array('code'=>0,'msg'=>$smsResult['ret_msg'],'data'=>$r);
            }
        }else{
            $request['send']=$request;
            $request['get'] =$r;
            $result=array('code'=>$r['ret_code'],'msg'=>$r['ret_msg'],'data'=>$request);
        }
        #var_dump($result);die;
        return $this->result($result['code'], $result['msg'],$result['data']);
    }
    
    /**
     * 绑卡并充值，发送验证码
     * $trade_no.为bindAndRecharge方法返回的$data中的trade_no号
     * 
     */
    public function bindAndRechargeSms($data,$cardInfo,$userInfo){

        $uid=$data['user_id'];
        $recharge=AccountRecharge::model()->with('paymentInfo')->findByAttributes(array('user_id'=>$uid,'trade_no'=>$data['trade_no']));
        $return=json_decode($recharge->api_once_return,true);
        $trade_no=$return['trade_no'];

        $request=array(
            'trade_no'=>$trade_no,
            'media_id'=>$cardInfo['phone'],
            'media_type'=>'MOBILE',
            'pay_type'=>'DEBITCARD',//目前我们只支持储蓄卡类型
            'card_id'=>$cardInfo['card_number'],
            'identity_type'=>'IDENTITY_CARD',
            'identity_code'=>$userInfo['card_id'],
            'card_holder'=>$userInfo['realname']
        );
        $r=$this->request('req_smsverify_shortcut', $request);
        if($r['ret_code']=='0000'){
            $result=array(
                'code'=>0,
                'msg'=>'短信发送成功'
            );
        }else{
            $result=array(
                'code'=>$r['ret_code'],
                'msg'=>'短信发送失败'
            );
        }
        $result['data']['send'] = $request;
        $result['data']['get']  = $r;
        return $this->result($result['code'], $result['msg'],$result['data']);
    }
    
    //绑卡并充值，确认验证码
    /**
     * 入参：
     * $data['trade_no'] //dw_account_recharge中的流水号
     * $data['user_id']
     * $data['verify_code']//验证码
     */
    public function bindAndRechargeVerify($data,$cardInfo,$userInfo){
        $accountRecharge=AccountRecharge::model()->findByAttributes(array('trade_no'=>$data['trade_no']));
        $account_return=json_decode($accountRecharge->api_once_return,true);
        $trade_no=$account_return['trade_no'];

        $request=array(
            'trade_no'=>$trade_no,
            'verify_code'=>$data['verify_code'],
            'mer_cust_id'=>$userInfo['hash_id'],
            'card_id'=>$cardInfo['card_number'],
            'media_id'=>$cardInfo['phone'],
            'media_type'=>'MOBILE',
            'identity_type'=>'IDENTITY_CARD',
            'identity_code'=>$userInfo['card_id'],
            'card_holder'=>$userInfo['realname'],
        );
        $r=$this->request('first_pay_confirm_shortcut',$request);
        if($r['ret_code']=='0000'){
            $r['send']  = $request;
            $r['get']   = $r;
            $result=array('code'=>0,'msg'=>'成功','data'=>$r);
        }else{
            #第三方服务器返回异常
            if(!isset($r['ret_code'])){
                $code = 111;
            }else{
            	$code = $r['ret_code'];
            }
            
            $result=array('code'=>$code,'msg'=>$r['ret_msg'],'data'=>array('send'=>$request,'get'=>$r));
        }
        return $this->result($result['code'], $result['msg'],$result['data']);
    }
    
    
    //充值
    /**
     * 入参
     * $paymentData['trade_no'] '用户在account_recharge中的流水号trade_no';
       $paymentData['money']  '用户此次充值金额';
     */
    public function recharge($data,$cardInfo,$userInfo){
        
        //得到风控信息
        $risk=array();//风控信息
        $risk['A0003']='20';
        $risk['B0002']='01';
        $risk['B0003']=$userInfo['realname'];
        $risk['B0004']=$userInfo['phone'];
        $risk['B0005']=$userInfo['card_id'];
        $risk['B0006']=$userInfo['email'];
        $risk['D0001']=$data['trade_no'];
        $risk['D0002']=$_SERVER['REMOTE_ADDR'];
        $risk['D0003']=$userInfo['hash_id'];
        $risk['D0004']=date('YmdHis',$userInfo['addtime']);
        $risk['D0005']=$data['device']==4?2:1;
        $risk['D0006']=$_SERVER['HTTP_USER_AGENT'];
        
        $risk_tmp=array();
        foreach($risk as $k=>$v){
            $risk_tmp[]=$k.':'.$v;
        }
        $risk_str=implode('#',$risk_tmp);
        $request=array(
            'order_id'=>$data['trade_no'],
            'mer_date'=>date('Ymd'),
            'amount'=>strval($data['money']*100),
            'risk_expand'=>$risk_str,//风控信息
        );
        $r=$this->request('pay_req_shortcut',$request);
        if($r['ret_code']=='0000'){
            $cardInfo=ItzSafeCardExpresspayment::model()->findByAttributes(array('nid'=>$this->paymentNid,'user_id'=>$data['user_id'],'state'=>2));
            if(empty($userInfo)){
                return array('code'=>1,'msg'=>'用户不存在','data'=>'');
            }
            
            if(empty($cardInfo)){
                return array('code'=>1,'msg'=>'无可用快捷通道','data'=>'');
            }
            //如果成功，进入发短信步骤
            $request=array(
                'trade_no'=>$r['trade_no'],
                'mer_cust_id'=>$userInfo['hash_id'],//可以用user_hash代替usr_busi_agreement_id，否则，又要加字段
                'usr_pay_agreement_id'=>$cardInfo->no_agree,
            );
            $r2=$this->request('req_smsverify_shortcut', $request);
            if($r2['ret_code']=='0000'){
                $result['code']=0;
                $result['msg']=$r2['ret_msg'];
                $result['data']=$r;
                $result['data']['type']='ServerSidePay';
            }else{
                $result['code']=$r2['ret_code'];
                $result['msg']=$r2['ret_msg'];
                $result['data']=$request;
            }
            $result['data']['send'] =$request;
            $result['data']['get']  =$r2;
        }else{
            $result['code']=$r['ret_code'];
            $result['msg']=$r['ret_msg'];
            $result['data']=$request;
            $result['data']['send'] =$request;
            $result['data']['get']  =$r;
        }
        return $this->result($result['code'], $result['msg'],$result['data']);
    }
    
    
    //充值，确认验证码
    /** 入参：
     * $data['trade_no'] //dw_account_recharge中的流水号
     * $data['user_id']
     * $data['verify_code']//验证码
     * 
     */
    public function rechargeVerify($data,$safeCard,$userInfo){
        $accountRecharge=AccountRecharge::model()->findByAttributes(array('trade_no'=>$data['trade_no']));
        #var_dump($accountRecharge);die;
        $account_return=json_decode($accountRecharge->api_once_return);
        $trade_no=$account_return->trade_no;
        
        $sql='select a.no_agree from itz_safe_card_expresspayment as a 
            left join itz_expresspayment b on a.expresspayment_id = b.id
            where a.user_id=:uid and b.payment_id=:payment_id and a.state=2
            ';
            
        $SafeCardExpresspayment=ItzSafeCardExpresspayment::model()->findBySql($sql,array(':uid'=>$data['user_id'],':payment_id'=>$accountRecharge->payment));
        #var_dump($SafeCardExpresspayment);die;
        $no_agree=$SafeCardExpresspayment->no_agree;
        
        $request=array(
            'trade_no'=>$trade_no,
            'verify_code'=>$data['verify_code'],
            'mer_cust_id'=>$userInfo['hash_id'],
            'usr_pay_agreement_id'=>$no_agree,
        );
        #var_dump($request);die;
        $result=$this->request('agreement_pay_confirm_shortcut',$request);
        #var_dump($result);
        if($result['ret_code']=='0000'){
            $r=array('code'=>0,'msg'=>$result['ret_msg'],'data'=>array('send'=>$request,'get'=>$result));
        }else{
            #第三方服务器返回异常
            if(!isset($result['ret_code'])){
                $code = 111;
            }else{
            	$code = $result['ret_code'];
            }

            $r=array('code'=>$code,'msg'=>$result['ret_msg'],'data'=>array('send'=>$request,'get'=>$result));
        }
        return $this->result($r['code'], $r['msg'],$r['data']);
    }
    
    //充值发送验证短信
    /**
     * $data=array('user_id'=>'','trade_no'=>'');
     * 
     *
     */
    public function rechargeSms($data,$cardInfo,$userInfo){

        $uid=$data['user_id'];
        $recharge=AccountRecharge::model()->with('paymentInfo')->findByAttributes(array('user_id'=>$uid,'trade_no'=>$data['trade_no']));
        if(empty($recharge)){
            $result['code']=1;
            $result['msg']='订单不存在';
            $result['data']=''; 
            return $result;
        }
        $return=json_decode($recharge->api_once_return,true);
        $trade_no=$return['trade_no'];
         
        $payment_id=$recharge->paymentInfo->id;

        $sql='select a.no_agree from itz_safe_card_expresspayment as a left join itz_expresspayment as b on a.expresspayment_id = b.id where a.safe_card_id=:safe_card_id and b.payment_id=:payment_id and a.state=2';
        $SafeCardExpresspayment_info=ItzSafeCardExpresspayment::model()->findBySql($sql,array(':safe_card_id'=>$cardInfo->id,':payment_id'=>$payment_id));
        if(empty($SafeCardExpresspayment_info)){
            $result['code']=1;
            $result['msg']='充值通道未启用';
            $result['data']=''; 
            return $result;
        }
        $request=array(
            'trade_no'=>$trade_no,
            'mer_cust_id'=>$userInfo['hash_id'],//可以用user_hash代替usr_busi_agreement_id，否则，又要加字段
            'usr_pay_agreement_id'=>$SafeCardExpresspayment_info->no_agree,
        );
        $r=$this->request('req_smsverify_shortcut', $request);
        if($r['ret_code']=='0000'){
            $result['code']=0;
            $result['msg']='验证码发送成功';
            $result['data']['send'] =$request;
            $result['data']['get']  =$r;
        }else{
            $result['code'] = $r['ret_code'];
            $result['msg']='验证码发送失败';
            $result['data']['send'] =$request;
            $result['data']['get']  =$r;
        }
        return $this->result($result['code'], $result['msg'],$result['data']);
    }
    
    //绑卡
    /**
     * 入参$data['user_id'] $data['phone']  $data['card']
     * 
     * 返回：
     * array(
     *  'code'=>
     *  'msg'=>
     *  'data'=>array('bind_no'=>)   此bind_no为第三方响应的绑卡流水号
     * )
     */
    public function bindCard($data,$userInfo){
        $request=array(
            'card_id'=>$data['card'],//银行卡号
            'media_id'=>$data['phone'],//手机号
            'media_type'=>'MOBILE',
            'identity_type'=>'IDENTITY_CARD',
            'identity_code'=>$userInfo['card_id'],//身份证号
            'card_holder'=>$userInfo['realname'],//银行卡所有人姓名
        );
        $r=$this->request('req_bind_verify_shortcut', $request);
        if($r['ret_code']=='0000'){
            $result=array('code'=>0,'msg'=>$r['ret_msg'],'data'=>array('bind_no'=>$r['bind_no'],'data'=>$r,'send'=>$request,'get'=>$r));
        }else{
            $result=array('code'=>$r['ret_code'],'msg'=>$r['ret_msg'],'data'=>array('send'=>$request,'get'=>$r));
        }
        return $this->result($result['code'], $result['msg'],$result['data']);
    }
    
    //绑卡确认
    /**
     *  入参：
     *   $data=array(
           'user_id' =>''，
           'verify_code'=>''
     *     'bind_no'=>''//ItzSafeCardExpresspayment表的主键，确认订单唯一性的东西
          );
     * 返回：
     * $data=array(
     *  'code'=>
     *  'msg'=>
     *  'data'=>
     *  'no_agree'=>
     * )
     */
    public function bindVerfy($data){
        $userInfo=User::model()->findByPk($data['user_id']);
        $sql='
            select card_number,phone
            from itz_safe_card 
            left join  itz_safe_card_expresspayment
                on itz_safe_card.id = itz_safe_card_expresspayment.safe_card_id
            where itz_safe_card_expresspayment.bind_token = :token and itz_safe_card_expresspayment.user_id=:uid
            ';
        $safeCard=ItzSafeCard::model()->findBySql($sql,array(':token'=>$data['bind_no'],':uid'=>$data['user_id']));
        if(empty($safeCard))
        {
            return array('code'=>1,'msg'=>'获取银行卡信息失败','data'=>array());
        }
        $request=array(
            'bind_no'=>$data['bind_no'],
            'verify_code'=>$data['verify_code'],
            'mer_cust_id'=>$userInfo->hash_id,
            'card_id'=>$safeCard->card_number,
            'media_id'=>$safeCard->phone,
            'media_type'=>'MOBILE',
            'identity_type'=>'IDENTITY_CARD',
            'identity_code'=>$userInfo->card_id,
            'card_holder'=>$userInfo->realname,
        );
        $r=$this->request('req_bind_confirm_shortcut', $request);
        if($r['ret_code']=='0000'){
            $result=array('code'=>0,'msg'=>$r['ret_msg'],'data'=>array(
                'send'  =>$request,
                'get'   =>$r,
            	'no_agree'=>$r['usr_pay_agreement_id']
                ),'no_agree'=>$r['usr_pay_agreement_id']);
        }else{
            $result=array('code'=>$r['ret_code'],'msg'=>$r['ret_msg'],'data'=>array(
                'send'  =>$request,
                'get'   =>$r
                ));
        }
        return $this->result($result['code'], $result['msg'],$result['data']);
    }
    
    #解绑
    public function bankCardUnbind($data,$userInfo,$safe_card){
        $userInfo=User::model()->findByPk($data['user_id']);
        $request=array(
            'mer_cust_id'           =>$userInfo->hash_id,
            'usr_pay_agreement_id'  =>$data['no_agree']
        );
        $r = $this->request('unbind_mercust_protocol_shortcut', $request);
        if($r['ret_code']=='0000'){
            $result=array('code'=>0,'msg'=>$r['ret_msg'],'data'=>$r);
        }else{
            $result=array('code'=>$r['ret_code'],'msg'=>$r['ret_msg'],'data'=>$request);
        }
        return $this->result($result['code'], $result['msg'],$result['data']);
    }

    public function userBankCard($user_id){
        
    }
    
    public function bankCardQuery($card_number){
        
    }
    
    
    public function noticeResult($data){
        
    }
    
    public function returnResult($data){
        
    }
    
    #返回结果
    public function result( $code, $msg, $data = array()){
    	if($code){ // 替换msg
    		$exception = array('111');
    		$info = ReturnService::getInstance()->getReturn('liandongpay',$code);
    		$msg = empty($info) ? $msg : $info;
    		if(in_array($code, $exception)){
    			$code = 111; #系统处理中
    		}else{
    			$code = 100;
    		}
    	}
    	return array(
    			'code'  =>$code,
    			'msg'   =>$msg,
    			'data'  =>$data
    	);
    }
    
    //成功充值后，第三方平台异步回调的函数
    public function getNoticeData(){
        #$url='http://124.193.127.130/index.php?amount=1&amt_type=RMB&card_holder=hWOc8AxzsgyZ9DwZ3MBA3RM4Bb7qWX2lZ4KXebH%2F7HQpXgCE3malC7j2hYQZWG9JLh1g6boErc9keyCezmLq%2F0NTVag1wpbgJodbR58B%2FLvsKsb8A4uWsyBJhzy4KxrX5DjlFxWFsfTOrvYkufMFqX%2FFu6n3jrg%2BYUY9zH0S3Ho%3D&charset=UTF-8&error_code=0000&identity_code=WlompGgAUyn%2Bzb%2FYpnO7agK%2BYSaU6CQZnGaPBbplriGtVjh8esHJ%2BVxuh2H4plSdoVfptdSym5yJLCrxvwJKUXpYpWtbJBdmIG2b2LcxoIpkJyo1YIgRq%2BF2HGByxLwM3vyYQi45NhETl%2FCBjnGS4blqCjUPdrcrpk4jccG0RbQ%3D&identity_type=IDENTITY_CARD&media_id=15201354573&media_type=MOBILE&mer_date=20150529&mer_id=9843&order_id=12089314328912589&pay_date=20150529&pay_seq=002004529765&pay_type=DEBITCARD&service=pay_result_notify&settle_date=20150529&trade_no=1505291720199205&trade_state=TRADE_SUCCESS&version=4.0&sign=sCP56rTyaLs6fxulQ%2BVAkNGmpqBy0wZmoWkXyB7bL3bLM%2FWTzzvFBwMJ4zIXKDmqjmq%2BA%2FK1Liu2Q2%2FtfG05vHiyFBlHq2Ll0RSU3e94F3hn7yO0I5N5S3BEC%2B%2F3v9dOt3KCaVIIEAq%2BliNkvnMhOz2iaZPeGES5W%2BL7rLUhkrs%3D&sign_type=RSA';
        $url=  "http://".$_SERVER ['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER["QUERY_STRING"];
        $returnData=array();//此变量用于返回数据 
        require_once (WWW_DIR."/itzlib/plugins/payment/include/liandong/plat2Mer.php");
        require_once (WWW_DIR."/itzlib/plugins/payment/include/liandong//mer2Plat.php");
        $htmlarr = explode("?",$url);
        $tmpArr=explode('&', $htmlarr[1]);
        foreach($tmpArr as $v){
            $tmpArr2=explode('=', $v);
            $returnData['data'][$tmpArr2[0]]=$tmpArr2[1];
        }
        $ht = new HashMap ();
        foreach(explode("&",$htmlarr[1]) as $u){
            $v=explode("=",$u);
            $ht->put ( $v[0],urldecode($v[1]) );
        }
        
        $resData = new HashMap ();
        try{
            $reqData = PlatToMer::getNotifyRequestData ( $ht );
            $resData->put("ret_code","0000");
        } catch (Exception $e){
            $resData->put("ret_code","1111");
            return false;
        } 
        $resData->put ( "mer_id", $ht->get ( "mer_id" ) );
        $resData->put ( "sign_type", $ht->get ( "sign_type" ) );
        $resData->put ( "version", $ht->get ( "version" ) );
        $resData->put ( "ret_msg", "success" );

        $data = MerToPlat::notifyResponseData ( $resData );
       
        $returnData['response']='<META NAME="MobilePayPlatform" CONTENT="'.$data.'" />';
        //响应平台的结果
        return $returnData;
        
    }
    //获得本通道在dw_payment表中的主键
    public function getPaymentId(){
        $r=Payment::model()->findByAttributes(array('nid'=>$this->paymentNid));
        return $r->id;
    }
    
    #用户签约信息查询API接口
    public function getBindCrasList($info,$userInfo) {

        $banks = array(
            'CCB'   =>'建设银行',
            'ABC'   =>'农业银行',
            'BOC'   =>'中国银行',
            'CITIC' =>'中信银行',
            'CEB'   =>'光大银行',
            'CIB'   =>'兴业银行',
            'SPDB'  =>'浦发银行'
            );

        $request=array(
            'pay_type'      =>'DEBITCARD',//目前我们只支持储蓄卡类型
            'mer_cust_id'   =>$userInfo->hash_id
        );
        $result = $this->request('query_mercust_bank_shortcut', $request);

        $bank_list = array();
        if($result['ret_code']=='0000')
        {
            if(!empty($result['user_bank_list']))
            {
                $list = explode('|',$result['user_bank_list']);
                foreach($list as $val)
                {
                    $bank = explode(',',$val);
                    if(!empty($bank[2]))
                    {
                        $bank_name = isset($banks[$bank[0]]) ? $banks[$bank[0]] : $bank[0];

                        $bank_list[] = array(
                            'channel_name'  =>'联动支付',
                            'bank_name'     =>$bank_name,
                            'card_no'       =>'****'.$bank[2],
                            'no_agree'      =>$bank[1],
                            'tel'           =>$bank[3]
                            );
                    }
                }

            }
        }
        else
        {
            Yii::log('LiandongpayClass>getBindCrasList>ret_code!=000:'.json_encode($result),'error');
        }
        return $bank_list;
    }

    #订单信息查询
    public function orderInfoQuery($recharge){
        $data=array(
            'order_type'    =>'1',
            'order_id'      =>$recharge->trade_no,
            'mer_date'      =>date('Ymd',$recharge->addtime)
            );
        $result=$this->request('mer_order_info_query',$data);
        if($result['trade_state']=='TRADE_SUCCESS')
        {
            return array('code'=>0,'msg'=>$result['trade_state'],'data'=>array());
        }
        else
        {
            return array('code'=>1,'msg'=>$result['trade_state'],'data'=>array());
        }
    }
}
?>