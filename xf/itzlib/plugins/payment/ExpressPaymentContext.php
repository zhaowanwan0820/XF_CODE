<?php
/**
 * 快捷支付-充值管理类
 * 
 * 此类的功能：
 * 1.判断本次最大充值限额
 * 2.充值时选择最优的通道并操作
 * 3.绑卡并充值时选择最优的通道并操作
 */
Yii::import('itzlib.plugins.payment.ExpressPaymentFactory');//导入工厂类
Yii::import('itzlib.plugins.payment.BankModel');//导入工厂类
class ExpressPaymentContext {

    public $userInfo = array();
    public $safeCard = array();

    public function setResult($code,$msg,$data='')
    {
        return array('code'=>$code,'msg'=>$msg,'data'=>$data);
    }

    #其他充值（微信、）
    public function otherPay($data){
    	//屏蔽
    	return $this->setResult(1,'系统繁忙，请稍后重试');
    	
        $data['money'] = round($data['money'],2);
        if($data['money']<=0){
            return $this->setResult(1,'充值金额必须大于0');
        }

        #生成订单号
        $trade_no = $this->getTradeNo($data['user_id']);

        #第一步：写入数据库
        $db = new BankModel();
        $rechargeInfo = array(
            'trade_no'          =>$trade_no,
            'user_id'           =>$data['user_id'],
            'money'             =>$data['money'],
            'type'              =>$data['os_id'],
            'payment'           =>57,#微信支付
            'api_once_return'   =>'',
            'card_number'       =>'',
            'bank_id'           =>0,
            'app_version'       =>empty($_SERVER["HTTP_CLIENT_VERSION"]) ? '' : $_SERVER["HTTP_CLIENT_VERSION"],
            'device_model'      =>empty($_REQUEST["device_model"]) ? '' : $_REQUEST["device_model"]
            );
        $r = $db->addAccountRecharge($rechargeInfo);

        if(!$r)
        {
            return $this->setResult(1,'系统异常，请重试！[addAccountRecharge error]');
        }

        #第二步：调用微信支付
        Yii::import("itzlib.plugins.payment.otherpay.Weixin");
        $pay = new Weixin();
        $r = $pay->getPayInfo($rechargeInfo);
        
        $data = array(
            'pay_type'  =>$data['pay_type'],
            'trade_no'  =>$trade_no,
            'data'      =>$r['data']
            );
        return $this->setResult($r['code'],$r['msg'],$data);
    }

    #其他充值（微信、）
    public function otherPayResult($trade_no){
    	//屏蔽
    	return $this->setResult(1,'系统繁忙，请稍后重试');
    	
        if(empty($trade_no)){
            return $this->setResult(1,'订单号不能为空！');
        }

        #第二步：调用微信支付
        Yii::import("itzlib.plugins.payment.otherpay.Weixin");
        $pay = new Weixin();
        $r = $pay->getPayResult($trade_no);
        return $this->setResult($r['code'],$r['msg'],$r['data']);
    }

    #关闭微信订单
    public function closeRecharge($user_id,$payment){
    	//屏蔽
    	return $this->setResult(1,'系统繁忙，请稍后重试');
    	
        #查询所有未支付订单
        $list = BaseCrudService::getInstance()->get(
            "AccountRecharge",
            "`status`=0 and `payment`='".$payment."' and user_id='".$user_id."'",
            0,
            "ALL",
            "",
            '',
            '',
            '',
            '',
            array('trade_no'));
        if(empty($list))
        {
            return 0;
        }


        #调用微信支付
        Yii::import("itzlib.plugins.payment.otherpay.Weixin");
        $pay = new Weixin();

        foreach($list as $v)
        {
            #查询第三方状态
            $result = $pay->getPayResult($v['trade_no']);
            if($result['code']!=0)
            {
                continue;
            }

            $close = false;
            if(in_array($result['data']['trade_state'],array(2,4)))
            {
                $close = true;
            }
            elseif($result['data']['trade_state']==1)
            {
                #通知微信关闭订单
                $r = $pay->closeOrder($v['trade_no']);
                if($r['code']==0)
                {
                    $close = true;
                }
            }
            if($close)
            {
                BaseCrudService::getInstance()->update('AccountRecharge', array(
                    'status'        =>2,
                    'verify_time'   =>time(),
                    'verify_remark' =>'重新支付，自动关闭[仅微信支付]',
                    'trade_no'      =>$v['trade_no']
                    ), 'trade_no');
            }
        }
    }

    //给连连单独一个查寻充值限额的接口
    public function getLianlianRechargeLimit($uid){
        /*
        $safeCardInfo=ItzSafeCard::model()->findByAttributes(array('user_id'=>$uid,'status'=>2));
        if(empty($safeCardInfo)){
            $result=array(
                'code'=>1,
                'msg'=>'用户无可用快捷卡',
                'data'=>'',
            );
            return $result;
        }
        #var_dump($safeCardInfo->getAttributes());die;
        $sql='select a.every_limit from itz_expresspayment_banklimit  as a
              left join itz_expresspayment on a.expresspayment_id = itz_expresspayment.id
              left join itz_safe_card_expresspayment on a.expresspayment_id = itz_safe_card_expresspayment.expresspayment_id
              left join dw_payment on itz_expresspayment.payment_id = dw_payment.id
              where dw_payment.nid = "lianlianpay" and itz_safe_card_expresspayment.safe_card_id=:safe_card_id and a.bank_id=:bank_id and itz_expresspayment.state=1 ';
        $limit=ItzExpresspaymentBanklimit::model()->findBySql($sql,array(':safe_card_id'=>$safeCardInfo['id'],':bank_id'=>$safeCardInfo['bank_id']));
        if($limit){
            return $limit->every_limit;
        }else{
            return 0;
        }
         */
    }

    //返回给app本次支持的最大金额,计算出每个通道支持的最大投资金额，并且返回其中最大的一个
    /**
     * 入参：$uid
     * 返回：
     * 
     */

    public function getRechargeLimit($uid,$device=0,$card_number=''){
        $db = new BankModel();
        return $db->getRechargeLimit($uid,$device,$card_number);
    }
    /**
     * 入参：$uid
     * 返回：
     * 
     */
    public function getRechargeLimitError($uid,$device=0,$card_number=''){
        $errors = array(
            1=>'该银行维护中，请使用电脑端网银充值',
            2=>'本月快捷充值额度已经用完，请使用电脑端网银充值',
            3=>'本日快捷充值额度已经用完，请使用电脑端网银充值'
        );
        $db = new BankModel();
        $status = $db->getRechargeLimitError($uid,$device,$card_number);
        if(is_array($status)){
        	return $status['info'];
        }else if(isset($errors[$status])){
            return $errors[$status];
        }
        return $errors[1];
    }

	/**
	 * app新提示 20181212
	 * 入参：$uid
	 * 返回：
	 *
	 */
	public function getRechargeLimitError2($uid,$device=0,$card_number='',$maxmoney,$money){

		//pc文案
		if($device==6){
			$errors = array(
				0=>'银行卡信息有误，请联系客服',
				1=>'该银行维护中，请使用电脑端网银充值',
				2=>'您绑定的银行卡单月限额%s元，本月剩余充值额度为%s元，您可以通过网银充值后再出借！',
				3=>'您绑定的银行卡单日限额%s元，今日剩余充值额度为%s元，您可以通过网银充值后再出借！',
				4=>'充值金额已超出您绑定银行卡的单笔限额%s元，您可以通过网银充值后再出借！',
			);
		}else{
			$errors = array(
				0=>'银行卡信息有误，请联系客服',
				1=>'该银行维护中，请使用电脑端网银充值',
				2=>'您绑定的银行卡单月限额%s元，本月剩余充值额度为%s元，您可以在登录官网xxx.com选择网银充值！',
				3=>'您绑定的银行卡单日限额%s元，今日剩余充值额度为%s元，您可以在登录官网xxx.com选择网银充值！',
				4=>'充值金额已超出您绑定银行卡的单笔限额%s元，您可以在登录官网xxx.com选择网银充值！',
			);
		}
		$db = new BankModel();
		$result = $db->getRechargeLimitError2($uid,$device,$card_number,$money);
		if(in_array($result['type'],[0,1])){
			return $errors[$result['type']];
		}
		$error = $errors[$result['type']];
		return sprintf($error,number_format($result['limit']?:$maxmoney,2),number_format($maxmoney,2));

	}

    /**
     * 获得指定通道的充值金额
     */
    public function getPaymentRechargeLimit($uid){
        
    }
    
    public function LianlianRecharge($uid,$money,$device){
    	//屏蔽
    	return array('code'=>1,'msg'=>'系统繁忙，请稍后重试','data'=>'');
    	
        if(!is_numeric($money)){
            return array('code'=>1,'msg'=>'请输入正确金额','data'=>'');
        }
            
        $money=round($money,2);
        $trade_no=$this->getTradeNo($uid);
        $factory=new ExpressPaymentFactory;
        $userInfo=User::model()->findByPk($uid);
        $cardInfo=ItzSafeCard::model()->findByAttributes(array('user_id'=>$uid,'status'=>2));//获取快捷卡信息
        
        if(empty($cardInfo)){
            return array('code'=>1,'msg'=>'请设置快捷卡','data'=>'');
        }
        
        $nid='lianlianpay';
        $payment=$factory->getPayment($nid);
        //获取通道信息
        $SafeCardExpresspayment_info=ItzSafeCardExpresspayment::model()->findByAttributes(array('user_id'=>$uid,'nid'=>$nid,'state'=>'2'));
        if(empty($SafeCardExpresspayment_info)){
            return array('code'=>1,'msg'=>'请先绑定银行卡','data'=>'');
        }
        #var_dump($SafeCardExpresspayment_info);die;
        //轮询通道，有能正常充值的，就超写入account_recharge
        $accountRecharge=new AccountRecharge();
        $accountRecharge->trade_no=$trade_no;
        $accountRecharge->user_id=$uid;
        $accountRecharge->status=0;
        $accountRecharge->money=$money;
        $accountRecharge->type=$device;
        $accountRecharge->card_number   = $cardInfo['card_number'];
        $accountRecharge->bank_id       = $cardInfo['bank_id'];
        $accountRecharge->app_version   = empty($_SERVER["HTTP_CLIENT_VERSION"]) ? '' : $_SERVER["HTTP_CLIENT_VERSION"];
        $accountRecharge->device_model  = empty($_REQUEST["device_model"]) ? '' : $_REQUEST["device_model"];

        
        $paymentInfo=BaseCrudService::getInstance()->get("Payment","",0,1,"",array("nid"=>'lianlianpay', 'status' => '1'));
        $paymentInfo=$paymentInfo[0];
        $paymentInfo['configAttrs'] = unserialize($paymentInfo['config']);
        $paymentData = array();
        $paymentData['user_id'] = $uid;
        $paymentData['id_no'] = $userInfo['card_id'];
        $paymentData['realname'] = $userInfo['realname'];
        $paymentData['no_agree'] = $SafeCardExpresspayment_info['no_agree'];
        $paymentData["tradeNo"] = $trade_no;
        $paymentData["orderAmount"] = $money;
        $paymentData['productName'] = "用户充值";
        $paymentData['productDesc'] = "用户充值";
        $paymentData['returnUrl'] = $this->createUrl('/newuser/PaymentReturn/lianlianpay');
        $paymentData['notifyUrl'] = $this->createUrl('/newuser/paymentNotify/lianlianpay');
        $paymentData['memberID'] = $paymentInfo['configAttrs']['member_id'];
        $paymentData['privateKey'] = $paymentInfo['configAttrs']['PrivateKey'];
        
        $accountRecharge->api_once_return='';//sdk方式无此字段
        $r2=$payment->mobileForm($paymentData);
        $accountRecharge->payment=$paymentInfo['id'];
        //给手机号加*隐藏
        $userPhone=$cardInfo->phone;
        if(strlen($userPhone)>=11){
            $userPhone[3]='*';
            $userPhone[4]='*';
            $userPhone[5]='*';
            $userPhone[6]='*';
        }
        
        $result['code']=0;
        $result['msg']='';
        $result['data']=array('trade_no'=>$trade_no,'amount'=>$money,'phoneNumber'=>$userPhone,'type'=>'llpay');
        $result['data']['post_data']=$r2;     
        
        if($accountRecharge->save()){
            $return=array('code'=>'0','msg'=>'充值成功','data'=>$result['data']);
        }else{
            Yii::log('APP>>>insert dw_account_recharge ERROR!!! uid='.$uid.'  db info :'.json_encode($accountRecharge->getErrors()). ' money='.$money);
            $return=array('code'=>'1','msg'=>'充值异常， 请联系客服或者使用PC充值','data'=>'');
        }
        return $return;
    }
    
    
    /*
     * 功能：
     * 查询银行卡
     * 
     */
    public function getBankId($card_number){
        $strlen = strlen($card_number);
        if($strlen<3 || $strlen>50){
            return 0;
        }
        
        #数据库操作
        $db = new BankModel();
        #查寻银行卡接口
        return $db->getBankId(array('card'=>$card_number));
    }

    /**
     * 功能：
     * 选取最优的渠道进行充值  -- 新网 银行存管
     * @param unknown $uid
     * @param unknown $money
     * @param unknown $device
     * @param string $card_number
     * @param string $phone_number
     * @param string $borrow_id 项目ID
     * @return multitype:unknown string 
     */
    public function bestRecharge($uid,$money,$device,$card_number='',$phone_number='',$borrowNo=''){

        #生成订单号
        if($borrowNo){
        	$trade_no = 'BAG'.$borrowNo;
        }else{
        	$trade_no = FunctionUtil::getRequestNo('KRE');
        }
        
        $money=round($money,2);
        if($money<=0){
        	return $this->setResult(1,'充值金额必须大于零');
        }

        #主库
        Yii::app()->db->switchToMaster();

        #设置用户信息
        $this->userInfo = UserService::getInstance()->getUser($uid);
        
        #检测用户信息
        $error_info = $this->checkUserInfo($this->userInfo);
        if($error_info){
        	return $this->setResult(1,$error_info);
        }
        
        #判断是否设置快捷卡
        $this->safeCard = ItzSafeCard::model()->findByAttributes(array('user_id'=>$uid,'status'=>2));
        if(empty($this->safeCard)){
        	return array('code'=>'1','msg'=>'您暂未绑定银行卡，请先绑定银行卡','data'=>'');
        }
        
        #判断金额是否过大
        $maxmoney=$this->getRechargeLimit($uid,$device,$card_number);
//        if($maxmoney==0){
//        	return array('code'=>'1','msg'=>$this->getRechargeLimitError($uid,$device,$card_number),'data'=>'');
//        }
        
        if($money>round($maxmoney,2)){
        	return array('code'=>'2','msg'=>$this->getRechargeLimitError2($uid,$device,$card_number,$maxmoney,$money),'data'=>'');
        }
        
        // 民生银行快捷充值限制提示（临时性）6100 特殊意义code 注意
        /* if ($this->safeCard->bank_id == 6) {
         return array('code'=>'6100','msg'=>'爱亲，民生银行快捷充值暂不支持，建议您更换银行卡或者使用网银充值','data'=>'');
        } */
        
        #数据库操作
        $db = new BankModel();

        #保存到数据库
        $add_status = $db->addAccountRecharge(array(
            'trade_no'          =>$trade_no,
            'user_id'           =>$uid,
            'money'             =>$money,
            'type'              =>$device,
            'card_number'       =>$this->safeCard->card_number,
            'bank_id'           =>$this->safeCard->bank_id,
            'app_version'       =>empty($_SERVER["HTTP_CLIENT_VERSION"]) ? '' : $_SERVER["HTTP_CLIENT_VERSION"],
            'device_model'      =>empty($_REQUEST["device_model"]) ? '' : $_REQUEST["device_model"]
        ));
        if(!$add_status){
            return $this->setResult(1,'充值异常，请联系客服或者使用'.$this->getHintMsg($device).'充值');
        }
        
        #获得可用通道
        $data=$this->getAviableRechargePayment_easy($uid,$money,$device);
        if(empty($data)){
            return array('code'=>'1','msg'=>'该银行维护中，请使用'.$this->getHintMsg($device).'充值','data'=>'');
        }
       
        #充值
        return $this->pollPaymentRecharge($uid,$money,$trade_no,$data,$device);
    }

    /**
     * 功能：
     * 申请充值后发送短信
     * 入参：
     * $data['trade_no'] //dw_account_recharge中的流水号
     * $data['user_id']
     * $data['verify_code']//验证码
     */
    public function bestRechargeVerify($data){
    	//屏蔽
    	return $this->setResult(1,'系统繁忙，请稍后重试');
    	
    	
        #基础参数判断
        if(empty($data['trade_no'])){
            return $this->setResult(1,'订单号不能为空');
        }
        if(empty($data['verify_code'])){
            return $this->setResult(1,'验证码不能为空');
        }

        Yii::app()->db->switchToMaster();

        #交易判断
        $recharge=AccountRecharge::model()->with('paymentInfo')->findByAttributes(array('user_id'=>$data['user_id'],'trade_no'=>$data['trade_no']));
        if(empty($recharge)){
            return $this->setResult(1,'此交易不存在');
        }

        #设置用户信息
        $this->userInfo = UserService::getInstance()->getUser($data['user_id']);

        $this->safeCard = $cardInfo=ItzSafeCard::model()->findByAttributes(array('card_number'=>$recharge->card_number,'user_id'=>$data['user_id']));

        #选择通道
        $nid=$recharge->paymentInfo->nid;
        $factory=new ExpressPaymentFactory;
        $payment=$factory->getPayment($nid);
        
        $payment_id=$recharge->payment;

        $sql='
        select * from itz_safe_card_expresspayment 
        left join itz_expresspayment on itz_safe_card_expresspayment.expresspayment_id=itz_expresspayment.id
        where itz_safe_card_expresspayment.state=2 and itz_safe_card_expresspayment.user_id=:uid and itz_expresspayment.payment_id=:payment_id';
        $safeCardExpressment=ItzSafeCardExpresspayment::model()->findBySql($sql,array(':uid'=>$data['user_id'],':payment_id'=>$payment_id));
        if($safeCardExpressment){
            $verifyResult=$payment->rechargeVerify($data,$this->safeCard,$this->userInfo);
            if($verifyResult['code']==0){
                $result=array('code'=>'0','msg'=>'充值成功','data'=>'');
            }else{
                $result=array('code'=>'1','msg'=>$verifyResult['msg'],'data'=>'');
            }
        }else{
            $verifyResult=$payment->bindAndRechargeVerify($data,$this->safeCard,$this->userInfo);
            if($verifyResult['code']==0){

                #设置快捷卡
                $setInfo = $this->setSafeCard($data['user_id'],$recharge->card_number);
                if( $setInfo['code']==0 || $setInfo['code']==10 )
                {
                    if($nid=='baofupay')
                    {
                        $expresspayment=ItzExpresspayment::model()->findByAttributes(array('payment_id'=>$payment_id));

                        #更新数据
                        $safe_card_ext = ItzSafeCardExpresspayment::model()->findByAttributes(array('safe_card_id'=>$this->safeCard->id,'expresspayment_id'=>$expresspayment['id']));
                        $safe_card_ext->state=2;
                        if(!$safe_card_ext->save())
                        {
                            Yii::log('APP>>>ItzSafeCardExpresspayment insert error .'.json_encode($safe_card_ext->getErrors()),'error');
                        }
                    }
                    else
                    {
                        $expresspayment=ItzExpresspayment::model()->findByAttributes(array('payment_id'=>$payment_id));
                        //插入快捷通道表
                        $safeCardExpressment=new ItzSafeCardExpresspayment;
                        $safeCardExpressment->safe_card_id=$this->safeCard->id;
                        $safeCardExpressment->state=2;
                        $safeCardExpressment->no_agree=$verifyResult['data']['usr_pay_agreement_id'];
                        $safeCardExpressment->expresspayment_id=$expresspayment['id'];
                        $safeCardExpressment->bind_result=$recharge->api_once_return;//api方式的绑卡结果，就是写入流水表时的结果
                        $safeCardExpressment->verify_result=json_encode($verifyResult['data']);
                        $safeCardExpressment->addtime=time();
                        $safeCardExpressment->nid=$nid;
                        $safeCardExpressment->user_id=$data['user_id'];
                        if(!$safeCardExpressment->save())
                        {
                            Yii::log('APP>>>ItzSafeCardExpresspayment insert error .'.json_encode($safeCardExpressment->getErrors()),'error');
                        }
                    }
                }

                $result=array('code'=>0,'msg'=>'充值成功','data'=>'');
            }else{
                $result=array('code'=>1,'msg'=>$verifyResult['msg'],'data'=>'');
            }
        }

        #需要补单的订单加入缓存
        if($verifyResult['code']=='111')
        {
            #将需要补单的订单写入缓存
            $extremely_list = Yii::app()->dcache->get('recharge.extremely.list');
            $extremely_list['no'.$data['trade_no']] = array(
                'trade_no'  =>$data['trade_no'],    #订单号
                'add_time'  =>time(),               #添加到缓存时间
                'run_number'=>0                     #执行次数
            );
            Yii::app()->dcache->set('recharge.extremely.list',$extremely_list,864000);
        }

        $db = new BankModel();

        #2实际充值（充值第二步，既用户已经输入验证码）
        $db->updateAccountRecharge($data['trade_no'],array('step'=>2));

        $_POST['nid'] = $nid;
        $_POST['info_result'] = $verifyResult;
        
        #可以直接返回成功
        if($verifyResult['code']==0)
        {
            if($nid =='ebatong')
            {
                if($verifyResult['data']['result'] == 'T')
                {
                    $_inData                = array();
                    $_inData['money']       = $recharge->money;
                    $_inData['trade_no']    = $data['trade_no'];
                    $_inData['request']     = $verifyResult['data'];

                    #改变状态
                    AccountService::getInstance()->OnlineReturn( $_inData );
                }
            }
            if($nid =='kuaiqianpay' || $nid =='baofupay')
            {
                $_inData                = array();
                $_inData['money']       = $recharge->money;
                $_inData['trade_no']    = $data['trade_no'];
                $_inData['request']     = $verifyResult['data'];

                #改变状态
                AccountService::getInstance()->OnlineReturn( $_inData );
            }
        }

        return $result;
    }
    
    /**
     * 功能：
     * 选取最优的渠道进行绑卡并充值
     */
    public function bestBindAndRecharge(){
        //简单的判断逻辑，1.通道可用，2通道有绑卡并充值的方法，3按优先级排序轮询
        
    }

    #检测用户信息是否支持帮卡要求
    public function checkUserInfo($userInfo)
    {
    	if($userInfo['xw_open'] != 2){
    		return '请您先开户或激活，再进行操作';
    	}
    	if($userInfo['safecard_status'] != 2){
    		return '请您先绑定银行卡，再进行操作';
    	}
        if($userInfo['real_status'] != '1'){
            return '请您先实名认证，再进行操作';
        }
        if($userInfo['phone_status'] != '1'){
            return '请您先手机认证，再进行操作';
        }
        /* if(empty($userInfo['paypassword'])){
            return '请您先设置支付密码，再进行绑卡操作';
        } */
    }

    #帮卡
    /*
        $result=$pay->bestBind(array(
            'user_id'   =>$this->user_id,
            'phone'     =>$_POST["phone_number"],
            'card'      =>$_POST["card_number"],
            'bank_id'   =>$_POST["bank_id"],
            #'nid'       =>'lianlianpay',
            'device'    =>$this->getDeviceOsId()
        ));    
    */
    public function bestBind($param){
    	//屏蔽
    	return $this->setResult(1,'系统繁忙，请稍后重试');
    	
        if(!is_numeric($param['card']))
        {
            return $this->setResult(1,'请填写您的银行卡号码');
        }
        if(strlen($param['phone'])!=11)
        {
            return $this->setResult(1,'请填写银行卡预留手机号');
        }

        #主库
        Yii::app()->db->switchToMaster();

        #设置用户信息
        $this->userInfo = UserService::getInstance()->getUser($param['user_id']);

        #检测用户信息
        $error_info = $this->checkUserInfo($this->userInfo);
        if($error_info)
        {
            return $this->setResult(1,$error_info);
        }
        
        #获取快捷卡信息
        $cardInfo=ItzSafeCard::model()->findByAttributes(array('user_id'=>$param['user_id'],'status'=>2));
        if($cardInfo)
        {
            return $this->setResult(1,'您已绑定过快捷卡，无需重复绑定！');
        }

        $db = new BankModel();

        #检测卡状态
        $safeCard = $db->checkCardStatus($param['card'],$param['is_new']);
        if(!empty($safeCard)){
            
            $msg = '此卡已被您绑定，无需重复绑定！';
            
            if($safeCard->user_id!=$param['user_id'])
            {
                $msg = '此卡已被其他用户绑定，请重新输入';
            }
            return $this->setResult(1,$msg);
        }

        #查寻银行卡接口
        $bank_id = $db->getBankId($param);
        if($bank_id==0){
            return $this->setResult(1,'暂不支持此银行');
        }

        #判断用户输入的银行卡号是否与选择的一致
        if(!empty($param['bank_id']) && $param['bank_id']!=$bank_id)
        {
            return $this->setResult(1,'银行名称与银行卡号不匹配');
        }

        $param['bank_id'] = $bank_id;

        #获取可用通道
        $nids = $db->selectPayment($param);
        #PC 充值 去除 连连
        if($param['device']==6 && in_array('lianlianpay',$nids))
        {
            unset($nids[array_keys($nids,'lianlianpay')[0]]);
        }

        if(empty($nids))
        {
            return $this->setResult(1,'此银行目前不可用，请更换银行或者使用'.$this->getHintMsg($param['device']).'充值');
        }

        #解绑帮过的
        if($this->versionControl('1.5.1'))
        {
            #获取快捷卡信息
            $cardInfo_status=ItzSafeCard::model()->findByAttributes(array('user_id'=>$param['user_id'],'card_number'=>$param['card']));
            if($cardInfo_status->status==1)
            {
                $this->bankCardUnbinds($param['user_id'],$param['card']);
            }
        }

        #开始绑卡
        $factory=new ExpressPaymentFactory;
        $pollBindFlag=false;
        foreach($nids as $nid)
        {
            $payment = $factory->getPayment($nid);

            #解绑
            #$r=$payment->bankCardUnbind('','','');
            #print_r($r);exit;

            #绑卡
            $bind_result=$payment->bindCard($param,$this->userInfo);

            #绑卡成功
            if($bind_result['code']==0){
                $_POST['nid']   = $nid;
                $_POST['phone'] = $param["phone"];
                $_POST['card']  = $param["card"];
                $_POST['bank_id']   = $param["bank_id"];
                $_POST['info_result']   = $bind_result;
                $pollBindFlag=true;//做一个轮询成功的标记
                break;
            }

            #审计日志
            AuditLog::getInstance()->method('add', array(
                "user_id"   => $param['user_id'],
                "system"    => 'app/'.$this->getDeviceOs(),
                "action"    => 'bindcard',
                "resource"  => 'user/app_bindcard/step1',
                "status"    => ($bind_result['code']==0) ? 'success' : 'fail',#success|fail
                "parameters"=> array(
                    'app_version'   =>$_SERVER["HTTP_CLIENT_VERSION"],
                    'nid'           =>$nid,
                    'phone'         =>$param["phone"],
                    'card'          =>$param["card"],
                    'bank_id'       =>$param["bank_id"],

                    'code'          =>$bind_result['code'],
                    'info'          =>$bind_result['msg'],
                    'info_result'   =>$bind_result
                )
            ));

        }

        #如果轮询最后一次也是 失败
        if($pollBindFlag==false)
        {
            return $this->setResult(1,$bind_result['msg']);
        }

        #查询银行是否存在
        $safeCard=ItzSafeCard::model()->findByAttributes(array('card_number'=>$param['card'],'user_id'=>$param['user_id']));
        
        if(empty($param['union_bank_id'])) {
            $param['union_bank_id'] = '';
        }
        if(empty($param['bank_branch'])) {
            $param['bank_branch'] = '';
        }
        Yii::app()->db->beginTransaction();
        if(empty($safeCard)){
            $safeCard=new ItzSafeCard;
            $safeCard->user_id      =$param['user_id'];
            $safeCard->type         =$nid;
            $safeCard->card_number  = $param['card'];
            $safeCard->bank_id      = $bank_id;//银行卡号暂时写空
            $safeCard->status       = 0;//未确认短信钱，是0
            $safeCard->phone        = $param['phone'];
            $safeCard->device       = $param['device'];
            
            $safeCard->province     = (int)$param['province'];
            $safeCard->city         = (int)$param['city'];
            $safeCard->union_bank_id= $param['union_bank_id'];
            $safeCard->bank_branch  = $param['bank_branch'];

            $safeCard->addtime      = time();
            $safeCard->modtime      = time();
            $dbr1=$safeCard->save();
        }
        else
        {
            $safeCard->phone        = $param['phone'];
            $dbr1=$safeCard->save();
        }

        $safeCardExpresspayment=new ItzSafeCardExpresspayment;
        $safeCardExpresspayment->safe_card_id=$safeCard->id;
        $safeCardExpresspayment->expresspayment_id=$payment->getExpresspaymentId();
        $safeCardExpresspayment->state=0;
        $safeCardExpresspayment->no_agree='';
        $safeCardExpresspayment->bind_result=json_encode($bind_result['data']['data']);
        $safeCardExpresspayment->addtime=time();
        $safeCardExpresspayment->bind_token=$bind_result['data']['bind_no'];
        $safeCardExpresspayment->nid=$nid;
        $safeCardExpresspayment->user_id=$param['user_id'];
        $dbr2=$safeCardExpresspayment->save();

        if(!empty($dbr1) && !empty($dbr2)){
            Yii::app()->db->commit();
        }else{
            Yii::app()->db->rollback();
            return $this->setResult(1,'网络异常请重试');
        }
        return $this->setResult(0,'',array('bind_no'=>$bind_result['data']['bind_no']));
    }

    /**
     * 功能：用户绑卡时，确认验证码
     *   $data=array(
           'user_id' =>''，
           'verify_code'=>''
     *     'bind_no'=>''//ItzSafeCardExpresspayment表的主键，确认订单唯一性的东西
          );
     */
    public function bestBindVerify($data){
    	//屏蔽
    	return $this->setResult(1,'系统繁忙，请稍后重试');
    	
    	
        if(empty($data['verify_code']))
        {
            return $this->setResult(1,'验证码不能为空');
        }
        if(empty($data['bind_no']))
        {
            return $this->setResult(1,'协议号不能为空');
        }

        #主库
        Yii::app()->db->switchToMaster();

        #检查是否有过绑卡
        $safeCardExpresspayment = ItzSafeCardExpresspayment::model()->findByAttributes(array('bind_token'=>$data['bind_no'],'user_id'=>$data['user_id']));
        if(empty($safeCardExpresspayment))
        {
            return $this->setResult(1,'系统错误！找不到帮卡信息记录');
        }

        #快捷卡信息
        $safeCard=ItzSafeCard::model()->findByPk($safeCardExpresspayment->safe_card_id);

        #根据银行查询可用支持的通道
        $cdb=new CDbCriteria;
        $cdb->condition='`status`=1 and bank_id='.$safeCard->bank_id.' and expresspayment_id='.$safeCardExpresspayment->expresspayment_id;
        $bank_limit =ItzExpresspaymentBanklimit::model()->find($cdb);

        #查出可用的通道
        $cdb=new CDbCriteria;
        $cdb->condition='bindlevel > -1 and rechargelevel > -1 and `state`=1 and id='.$safeCardExpresspayment->expresspayment_id;
        $cdb->order='bindlevel';
        $expresspayment = ItzExpresspayment::model()->find($cdb);

        if(empty($bank_limit) || empty($expresspayment))
        {
            $db = new BankModel();
            $paymentList = $db->selectPayment(array('bank_id'=>$safeCard->bank_id));
            if(empty($paymentList))
            {
                return $this->setResult(1,'该银行维护中，请更换银行再次绑卡');
            }
            else
            {
                return $this->setResult(1,'网络异常，请重试');
            }
        }

        #选择通道
        $factory=new ExpressPaymentFactory;
        $payment=$factory->getPayment($safeCardExpresspayment->nid);
        if($payment==null)
        {
            return $this->setResult(1,'系统错误！[getPayment null]');
        }

        #绑卡确认
        $r=$payment->bindVerfy($data);
        $_POST['info_result'] = $r;
        if($r['code']=='0'){
            $safeCard=ItzSafeCard::model()->findByPk($safeCardExpresspayment->safe_card_id);
            if($safeCard->status==0 || $safeCard->status==9 ){//如果status为0，则更新status，1,2情况则不处理
                Yii::app()->db->beginTransaction();
                $safeCard->status=1;
                $dbr1=$safeCard->save();
            }
            $safeCardExpresspayment->state=2;
            $safeCardExpresspayment->no_agree = !empty($r['no_agree']) ? $r['no_agree'] : $r['data']['no_agree'];
            $safeCardExpresspayment->verify_result=json_encode($r['data']);
            $dbr2=$safeCardExpresspayment->save();
            
            if(isset($dbr1)){//如果有dbr1，则要进入事务
                if($dbr1 && $dbr2){
                  Yii::app()->db->commit();
                }else{
                  Yii::app()->db->rollback();
                  return $this->setResult(1,'网络异常请重试');
                }
            }

            //绑卡成功后，查询银行的逻辑
            $cards = BaseCrudService::getInstance()->get('ItzSafeCard','',0,1,'',array('id'=>$safeCardExpresspayment->safe_card_id), null, array('bankInfo'));
            $userInfo=User::model()->findByPk($safeCardExpresspayment->user_id);
            $data2=array('card'=>$cards[0],'needVerifyPhone'=>($userInfo->phone==$cards[0]['phone']?0:1));
            return $this->setResult(0,'绑卡成功',$data2);
        }else{
            return $this->setResult(1,$r['msg']);
        }
    }

    /**
     * 计算全部可用于充值的通道，并根据手续费和充值优先级排序,返回排序后的数组
     * 
     * 【此函数逻辑复杂，暂时先用easy版】
     * 
     */ 
    private function getAviableRechargePayment($uid,$money){
    	//屏蔽
    	return array('code'=>1,'info'=>'系统繁忙，请稍后重试');
    	
    	
        $expressPayment=ItzExpresspayment::model()->findAll('rechargelevel>-1 and state=1');
        $result=array('code'=>'','info'=>'','data'=>'');
        if(empty($expressPayment)){
            return array('code'=>1,'info'=>'暂无可用充值通道');
        }else{
            $userPaymentInfo=array();//用户在每个通道的充值信息
            $availablePaymentId=array();//全部可用的paymentid
            
            //通过此遍历，和后面的便利啊，计算出用户在各个通道冲了多少钱
            foreach ($expressPayment as $key => $value) {
                $availablePaymentId[]=$value->payment_id;
                $userPaymentInfo[$value->payment_id]['money']=$money;
                $userPaymentInfo[$value->payment_id]['daily']=$money;
                $userPaymentInfo[$value->payment_id]['month']=$money;
            }
            //1获取全部可用通道的已充值金额
            $aviablePayment=array();
             //当日各通道的充值总和

            $cdb=new CDbCriteria;
            $cdb->select='SUM(money) as money , payment';
            $cdb->condition='user_id='.$uid.' and addtime >'.strtotime(date('Y-m-d 0:0:0')).' and addtime <'.time();
            $cdb->addInCondition('payment', $availablePaymentId);
            $cdb->group='payment';
           
            
            $dailyRecharge=AccountRecharge::model()->findAll($cdb);
            
            if(!empty($dailyRecharge)){
                foreach($dailyRecharge as $v){
                    $userPaymentInfo[$v->payment]['daily']+=$v->money;
                }
            }

            
            //当月各个通道的充值总额
            $cdb=new CDbCriteria;
            $cdb->select='SUM(money) as money , payment';
            $cdb->condition='user_id='.$uid.' and addtime >'.strtotime(date('Y-m-1 0:0:0')).' and addtime <'.time();
            $cdb->addInCondition('payment', $availablePaymentId);
            $cdb->group='payment';
            
            $monthRecharge=AccountRecharge::model()->findAll($cdb);
            if(!empty($monthRecharge)){
                foreach($monthRecharge as $v){
                    $userPaymentInfo[$v->payment]['month']+=$v->money;
                }
            }
            //拿出全部快捷通道的限额
            $safeCard=ItzSafeCard::model()->findByAttributes(array('user_id'=>$uid,'status'=>2));
            if($safeCard){
                $bank_id=$safeCard->bank_id;
           
                $cdb=new CDbCriteria;
                $cdb->condition='bank_id = '.$bank_id. ' and `status` =1';
                $expressPayment=ItzExpresspaymentBanklimit::model()->with('expresspayment')->findAll($cdb);
                foreach($expressPayment as $k=>$v){
                    if(!in_array($v->expresspayment->payment_id, $availablePaymentId)){
                        unset($expressPayment[$k]);
                    }
                }
                $resultPayment=array();//最后返回的payment数组，全部符合条件的payment都放到这里
                foreach($expressPayment as $v){
                    if(
                    $v->every_limit>=$userPaymentInfo[$v->expresspayment->payment_id]['money'] && //每笔
                    $v->daily_limit>=$userPaymentInfo[$v->expresspayment->payment_id]['daily'] && //每日
                    $v->monthly_limit>=$userPaymentInfo[$v->expresspayment->payment_id]['month']    //每月
                    ){
                        $resultPayment[$v->expresspayment_id]='';//此数组键名为expressPayment_id 键值为实际费用
                        $tmpArr[]=$v->expresspayment_id;//用于临时存放得到的id,给后面限额表用
                    }
                }
                //计算费率
                $cdb=new CDbCriteria;
                $cdb->condition='`start`<'.$money.' and end>='.$money;
                $cdb->addInCondition('expresspayment_id', $tmpArr);
                $formula=ItzExpresspaymentFormula::model()->findAll($cdb);
                
                $tmpArr=array();//此临时变量用于存放expressPayment_id与formula的对应关系
                foreach($formula as $v){
                    $tmpArr[$v->expresspayment_id]=$v->formula;
                }
               
                foreach($resultPayment as $k=>$v){
                    $forumlaStr=$tmpArr[$k];
                    $newStr=str_replace(array('%','x'), array('*0.01',$money), $forumlaStr);
                    $newStr='$cost = '.$newStr.';';
                    eval($newStr);
                    $resultPayment[$k]=$cost;
                }
                asort($resultPayment);
                $result['code']=0;
                $result['msg'] = '成功';
                $result['data']=$resultPayment;
            }else{
                $result['code']=1;
                $result['msg']='用户无可用的银行卡';
            }
        }
        return $result;
    } 
    
    //获取可用充值通道，简化版
    private function getAviableRechargePayment_easy($uid,$money,$device){

        #PC 支付 去除 连连
        $ext_where = '';
        if($device==6 || $this->safeCard['status']!=2){
            $ext_where = " and e.payment_id!='45'";
        }

        #获得可用通道（没有超过单笔限额）
        $sql='select e.id,e.payment_id,l.type_id,l.every_limit,l.daily_limit,l.monthly_limit from itz_expresspayment_banklimit as l
              inner join itz_expresspayment as e on l.expresspayment_id = e.id and e.rechargelevel > -1 and e.state=1
              where  l.status = 1 and l.bank_id='.$this->safeCard->bank_id.' and l.every_limit>='.$money.$ext_where;
        $r = Yii::app()->db->createCommand($sql)->queryAll();
        if(empty($r)){
            return array();
        }

        $db = new BankModel();
        //$pays = $db->checkPays($uid);
        //$kq_pid = $db->getKqPid();
        $ids = array();
        foreach($r as $v){
        	/* if ($pays == 1) {
        		if( $v['type_id'] == 0 && $v['payment_id'] == $kq_pid ){
        			continue;
        		}
        	}else{
        		if( $v['type_id'] == 1 && $v['payment_id'] == $kq_pid ){
        			continue;
        		}
        	} */
        	
            #获得充值记录总金额
            $sum_money = $db->getRechargeSum($v['payment_id'],$this->safeCard);

            #计算可充金额（单笔限额，（单日限额-今天充值金额），（单月限额-本月充值金额））
            $min_money = min($v['every_limit'],($v['daily_limit']-$sum_money['day']),($v['monthly_limit']-$sum_money['month']));
            if(round($min_money,2)>=round($money,2)){
                $ids[] = $v['id'];
            }
        }
        if(empty($ids)){
            return array();
        }

        #根据费率排序
        $cdb=new CDbCriteria;
        $cdb->condition=' `status`=0  and  `start`<'.$money.' and `end`>='.$money;
        $cdb->addInCondition('expresspayment_id', $ids);
        $formula=ItzExpresspaymentFormula::model()->findAll($cdb);
        if(empty($formula)){
            return array();
        }

        $result=array();
        foreach($formula as $v){
            $newStr=str_replace(array('%','x'), array('*0.01',$money), $v->formula);
            eval('$cost = '.$newStr.';');
            $result[$v->expresspayment_id]=$cost;
        }
        asort($result);
        $result = array_keys($result);
        return $result;
    }
    
    /**
     * 计算全部可用于绑卡并充值的通道，并根据手续费和充值优先级排序，返回排序后的数组
     * 
     */
     private function getAviableBindAndRechargePayment($uid,$money,$card){
         
     }
    
     /**
      * 轮询全部可用的通道 -- 单快捷充值
      * @param unknown $uid
      * @param unknown $money
      * @param unknown $trade_no 为我们自己生成的流水号
      * @param unknown $expressPayments
      * @param unknown $device 
      * @return multitype:string |multitype:number string multitype:string unknown  |multitype:number string Ambigous <>
      */
    private function pollPaymentRecharge($uid,$money,$trade_no,$expressPayments,$device,$borrow_id){
        #给手机号加*隐藏
        $userPhone=$this->safeCard->phone;
        if(strlen($userPhone)>=11){
            $userPhone[6] = $userPhone[5] = $userPhone[4] = $userPhone[3] = '*';
        }
        
        #判断是否绑卡使用
        $SafeCard = ItzSafeCard::model()->findByAttributes(array('user_id'=>$uid, 'id'=>$this->safeCard->id, 'status'=>'2'));
        if ( empty($SafeCard) ) {
        	return array('code'=>'1','msg'=>'充值异常， 请联系客服或者使用'.$this->getHintMsg($device).'充值','data'=>'');
        }
       
        //绑卡银行 id
        $bank_id = $SafeCard->bank_id;
        $db = new BankModel();
        #调用可用通道循环充值（成功为止）
        foreach($expressPayments as $v){
            $result=array('code'=>1,'message'=>'','data'=>array());

            #百分比分配
            if($money>=1000000){
                $v = $db->randPayment($v,$expressPayments);
            }
            #获得通道信息
            $r = ItzExpresspayment::model()->with('payment')->findByPk($v);
            $payment_nid = $r->payment->nid;
            $payment_id  = $r->id;
            
            #获取银行代码 bank_id
            $bankInfo = ExpresspaymentBanklimit::model()->findByAttributes(array('expresspayment_id'=>$payment_id, 'bank_id'=>$bank_id, 'status'=>1));
			//拼接数据
            $recharge_uid = 'r'.$uid;
			$requiredData = $paymentData = array();
			$requiredData['serviceName'] = 'RECHARGE';
			$requiredData['userDevice']  = in_array($device, array(1,6)) ? 'PC' : 'MOBILE';
			//reqData 参数
			$paymentData["platformUserNo"] = $recharge_uid; // 290
			$paymentData["requestNo"] = $trade_no; // RE149449027659141CA439AE07661604
			$paymentData["amount"] = strval($money);
			$paymentData['expectPayCompany'] = strtoupper($payment_nid);		// 第三方支付公司
			$paymentData['rechargeWay'] = "SWIFT";					// 支付方式，支持网银（WEB）、快捷支付（SWIFT）
			$paymentData['bankcode'] = strtoupper($bankInfo->bank_code);	// 银行编码
			//$paymentData['payType'] = "B2C";						// 个人银行
			$paymentData['expired'] = date('YmdHis',time()+600);	// 页面超时时间 10分钟
			if($requiredData['userDevice']  == 'PC'){
				$paymentData['redirectUrl'] = $this->createUrl('/user/PaymentReturn/quickPayment');	  // PC同步回调
			}else{
				$paymentData['redirectUrl'] = $this->createUrl('/user/PaymentReturn/wapQuickPayment');	  // H5同步回调
			}
			$paymentData['callbackMode'] = "DIRECT_CALLBACK";
			$requiredData['reqData'] = $paymentData;
			if(empty($paymentData['bankcode'])){
				$result['code'] = 100;
				$result['message'] = '充值异常， 请联系客服或者使用'.$this->getHintMsg($device).'充值';
				$result['data'] = array();
			}else{
				//调取java接口
				$result = CurlService::getInstance()->gateway($requiredData);
			}
			$_POST['nid']           = $payment_nid;
			$_POST['recharge_info'] = $requiredData;
			$_POST['info_result']   = $result;
			$_POST['trade_no']      = $trade_no;

			#更新订单信息
			$add_status = $db->updateAccountRecharge($trade_no,array(
                    'payment'           =>$r->payment_id,
                    'api_once_return'   =>json_encode($result)
			));

			if($add_status===false){
            	return array('code'=>'1','msg'=>'充值异常， 请联系客服或者使用'.$this->getHintMsg($device).'充值','data'=>'');
			}
			#成功
			if($result['code']===0){
				return array(
					'code'=>0,
					'msg'=>'充值链接获取成功',
					'data'=>array(
							'url'			=>$result['data']['url'],
							'trade_no'      =>$trade_no,
							'amount'        =>$money,
							'phoneNumber'   =>$userPhone,
							'type'          =>'ServerSidePay'
					)
				);
			}else{
                    #审计日志 充值
                    AuditLog::getInstance()->method('add', array(
                        "user_id"   => $uid,
                        "system"    => 'app/'.$this->getDeviceOs(),
                        "action"    => 'recharge',
                        "resource"  => 'user/app_recharge',
                        "status"    => ($result['code']===0) ? 'success' : 'fail',#success|fail
                        "parameters"=> array(
                            'app_version'   =>$_SERVER["HTTP_CLIENT_VERSION"],
                            'nid'           =>$payment_nid,
                            'trade_no'      =>$trade_no,
                            'money'         =>$money,
                            'recharge_info' =>$requiredData,
                            'code'          =>$result['code'],
                            'info'          =>$result['message'],
                            'info_result'   =>$result
                        )
                    ));
                    #失败
                    $result=array('code' =>1,'msg' =>'充值异常， 请联系客服或者使用'.$this->getHintMsg($device).'充值','data' =>array());
			}
		}
        return $result;
    }

    /**
     * 跟上面的功能类似，但是是绑卡并充值
     */
    private function pollPaymentBindAndRecharge(){
        
    }
    
    //获得交易流水号
    private function getTradeNo($uid){
        return time().$uid.rand(1,9);
    }
    
    //创造url
    private function createUrl($route,$params=array(),$ampersand='&')
    {
        if($route==='')
            $route=$this->getId().'/'.$this->getAction()->getId();
        elseif(strpos($route,'/')===false)
            $route=$this->getId().'/'.$route;
        if($route[0]!=='/' && ($module=$this->getModule())!==null)
            $route=$module->getId().'/'.$route;
        return Yii::app()->createUrl(trim($route,'/'),$params,$ampersand);
    }
    
    
    //发送短信
    /**
     * $data=array('user_id'=>'','trade_no'=>'')
     * 
     * 要判断是绑卡并充值的发短信，还是单独绑卡的发短信
     */
    public function rechargeSms($data){
    	//屏蔽
    	return $this->setResult(1,'系统繁忙，请稍后重试');
    	
    	
        if(empty($data['trade_no']))
        {
            return $this->setResult(1,'订单号不能为空');
        }

        #主库
        Yii::app()->db->switchToMaster();

        #判断订单是否存在
        $recharge=AccountRecharge::model()->with('paymentInfo')->findByAttributes(array('user_id'=>$data['user_id'],'trade_no'=>$data['trade_no']));
        if(empty($recharge)){
            return $this->setResult(1,'充值订单不存在');
        }

        #选择通道
        $factory=new ExpressPaymentFactory;
        $payment=$factory->getPayment($recharge->paymentInfo->nid);

        #获取快捷卡信息
        $this->safeCard = ItzSafeCard::model()->findByAttributes(array('user_id'=>$data['user_id'],'card_number'=>$recharge->card_number));
        $this->userInfo = UserService::getInstance()->getUser($data['user_id']);

        if(empty($this->safeCard)){
            $_POST['nid'] = $recharge->paymentInfo->nid;
            return array(
                'code'=>'1',
                'msg'=>'用户无快捷卡',
                'data'=>'',
            );
        }

        $data['payment_id']=$recharge->paymentInfo['id'];

        #开始发短信
        $sql='select * from itz_safe_card_expresspayment as a left join itz_expresspayment as b on a.expresspayment_id = b.id where a.safe_card_id=:safe_card_id and b.payment_id=:payment_id and a.state=2';
        $SafeCardExpresspayment_info=ItzSafeCardExpresspayment::model()->findBySql($sql,array(':safe_card_id'=>$this->safeCard->id,':payment_id'=>$recharge->paymentInfo->id));
        
        if(!empty($SafeCardExpresspayment_info)){
            $r=$payment->rechargeSms($data,$this->safeCard,$this->userInfo);
        }else{
            $r=$payment->bindAndRechargeSms($data,$this->safeCard,$this->userInfo);
        }


        #审计日志 充值
        if($r['code']!='0')
        {
            $_POST['nid'] = $recharge->paymentInfo->nid;
        }

        return $r;
    }
    
    //确认短信
    /**
     * 
     */
    public function rechargeVerify($uid,$trade_no,$verifyCode){
        
    }

    #解绑
    public function bankCardUnbinds( $user_id, $card_number=''){

        if(empty($user_id))
        {
            return false;
        }

        $errors = array();

        $db = new BankModel();

        #获取需要解绑的其他银行卡
        $ItzSafeCards = $db->getSafeCard($user_id, $card_number);
        if($ItzSafeCards){
            $safe_card_ids = array();
            foreach($ItzSafeCards as $v)
            {
                $safe_card_ids[] = $v['id'];
            }
            #获取扩展表的数据
            $SafeCardExt = $db->getSafeCardExt($safe_card_ids);
            if($SafeCardExt)
            {
                $safe_card_ext_ids = array();
                #加载充值类
                foreach($SafeCardExt as $v)
                {
                    $safe_card_ext_ids[] = $v['id'];

                    #解绑
                    $info = $this->bankCardUnbind($v);
                    if($info['code']!='0')
                    {
                        Yii::log('APP>>>ExpressPaymentContext>bankCardUnbinds:'.json_encode($info));
                        Yii::log('APP>>>ExpressPaymentContext>bankCardUnbinds:'.$info['msg']);
                        $errors[] = $info['msg'];
                    }
                }
                Yii::app()->db->beginTransaction();
                $Transaction = 1;

                #改变 扩展表绑定 状态
                $result = ItzSafeCardExpresspayment::model()->updateAll(
                    array('state'=>0),
                    "`id` in (".implode(',',$safe_card_ext_ids).")");
                if(!is_int($result))
                {
                    $Transaction = 0;
                }

                $where = 'user_id = :user_id';
                if($card_number)
                {
                    $where .= " AND card_number = '".$card_number."'";
                }
                $result = ItzSafeCard::model()->updateAll(
                    array('status'=>9),
                    $where,
                    array(':user_id'=>$user_id));
                if(!is_int($result))
                {
                    $Transaction = 0;
                }

                $result = AccountBank::model()->updateAll(
                    array('bank_status'=>0),
                    'user_id = :user_id AND bank_status = :bank_status',
                    array(':user_id'=>$user_id,':bank_status'=>1));
                if(!is_int($result))
                {
                    $Transaction = 0;
                }

                if(!is_int(User::model()->updateByPk($user_id,array('safecard_status'=>9))))
                {
                    $Transaction = 0;
                }

                if($Transaction)
                {
                    Yii::app()->db->commit();
                }
                else
                {
                    Yii::app()->db->rollback();
                    return false;
                }
            }
        }

        if($errors)
        {
            #return implode('<br>',$errors);
        }
        return true;
    }

    #解绑
    public function bankCardUnbind( $Expresspayment ){
   		//屏蔽
    	return array('code'=>1,'info'=>'系统繁忙，请稍后重试','data'=>array());
    	
    	
        #用户信息
        $userInfo = User::model()->findByPk($Expresspayment['user_id']);

        #快捷卡信息
        $safe_card = ItzSafeCard::model()->findByAttributes(array('id'=>$Expresspayment['safe_card_id']));

        $factory=new ExpressPaymentFactory;
        $payment=$factory->getPayment($Expresspayment['nid']);
        return $payment->bankCardUnbind($Expresspayment,$userInfo,$safe_card);
    }

    #查询 用户绑定的银行卡列表
    public function getBindCardList($user_id=0){
        $result = array();

        #user 不能为空
        if(empty($user_id))
        {
            return $result;
        }
        
        #用户信息
        $userInfo = User::model()->findByPk($user_id);
        if(empty($userInfo))
        {
            return $result;
        }

        #查询已绑卡的渠道
        $criteria = new CDbCriteria();
        $criteria->condition="`user_id`='".$user_id."'";
        $criteria->group = 'nid';
        $ItzSafeCardExpresspayment=ItzSafeCardExpresspayment::model()->findAll($criteria);
        if(empty($ItzSafeCardExpresspayment))
        {
            return $result;
        }

        #循环查询
        $factory=new ExpressPaymentFactory;
        foreach($ItzSafeCardExpresspayment as $v)
        {
            #选择通道
            $payment=$factory->getPayment($v->nid);
            $bank_tmp = $payment->getBindCrasList($v,$userInfo);
            if(!empty($bank_tmp))
            {
                $result = array_merge($result,$bank_tmp);
            }
        }
        return $result;
    }

    #获得可用银行列表
    public function getOnBankList($bank_id=0,$device=0,$quota=false,$uid=0){

        #获得银行名称
        $db = new BankModel();
        $banks = $db->getBankList();

        $list = array();

        #根据 bank_id 查询
        $where = '';
        if($bank_id)
        {
            $where .= " and l.bank_id=".$bank_id;
        }
        if($device==6)
        {
            $where .= " and e.payment_id!='45'";
        }

        #查询可用的银行
        $sql='select l.*,e.payment_id from itz_expresspayment_banklimit as l
              inner join itz_expresspayment as e on l.expresspayment_id = e.id and e.bindlevel > -1 and e.rechargelevel > -1 and e.state=1
              where  l.status = 1'.$where;
        if($quota)
        {
            #去掉绑卡 and e.bindlevel > -1
            $sql='select l.*,e.payment_id from itz_expresspayment_banklimit as l
                  inner join itz_expresspayment as e on l.expresspayment_id = e.id and e.rechargelevel > -1 and e.state=1
                  where  l.status = 1'.$where;
        }
        $r = Yii::app()->db->createCommand($sql)->queryAll($sql);
        if($r){
        	//$pays = $db->checkPays($uid);
        	//$kq_pid = $db->getKqPid();
            foreach($r as $v){
            	/* if ($pays == 1) { 
            		if( $v['type_id'] == 0 && $v['payment_id'] == $kq_pid ){
            			continue;
            		}
            	} else {
            		if( $v['type_id'] == 1 && $v['payment_id'] == $kq_pid ){
            			continue;
            		}
            	}  */
            	
                if(!isset($list[$v['bank_id']]))
                {
                    $list[$v['bank_id']] = array(
                        'bank_id'           =>$v['bank_id'],
                        'bank_name'         =>$banks[$v['bank_id']],
                        'money_once_limit'  =>$v['every_limit'],
                        'money_day_limit'   =>$v['daily_limit'],
                        'money_month_limit' =>$v['monthly_limit']
                        );
                }
                else
                {
                    $list[$v['bank_id']]['money_day_limit']+=$v['daily_limit'];
                    $list[$v['bank_id']]['money_month_limit']+=$v['monthly_limit'];
                }

                #大金额 替换小金额
                if($list[$v['bank_id']]['money_once_limit'] < $v['every_limit'])
                {
                    $list[$v['bank_id']]['money_once_limit'] = $v['every_limit'];
                }
            }
            usort($list, "self::moneySort");
        }
        
        #根据 bank_id 查询
        if($bank_id && $list)
        {
            $list = $list[0];
        }

        return $list;
    }
    static function moneySort($a,$b){
        if ($a['money_once_limit'] == $b['money_once_limit']) {
            return 0;
        }
        return ($a['money_once_limit'] > $b['money_once_limit']) ? -1 : 1;
    }

    //获取客户端系统
    protected function getDeviceOs(){
        if(strripos($_SERVER['HTTP_USER_AGENT'],'Volley')){
            return 'android';
        }elseif(strripos($_SERVER['HTTP_USER_AGENT'],'CFNetwork')){
            return 'ios';
        }elseif(!empty($_SESSION['wapapi'])){
            return 'wap';
        }else{
            return '';
        }
    }

    #设置快捷卡
    public function setSafeCard($user_id,$card_number){
    	//屏蔽
    	return $this->setResult(1,'系统繁忙，请稍后重试');
    	
        if(empty($user_id))
        {
            return $this->setResult(1,'请先登录');
        }
        if(empty($card_number))
        {
            return $this->setResult(1,'卡号不能为空');
        }

        $safeCardCount = BaseCrudService::getInstance()->count('ItzSafeCard', '', 0, 'ALL', '', array('user_id'=>$user_id,'status'=>2));
        if($safeCardCount > 0)
        {
            return $this->setResult(10,'您已有快捷卡，不能再添加');
        }
        
		$safeCards = BaseCrudService::getInstance()->get('ItzSafeCard', '', 0, 1, '', array('user_id'=>$user_id, 'card_number'=>$card_number));
        if(empty($safeCards))
        {
            return $this->setResult(1,'快捷卡不存在');
        }
        
        $result = BaseCrudService::getInstance()->update('ItzSafeCard', array('status' =>2,'successtime'=>time(),'id'=>$safeCards[0]['id']), 'id');
        if($result){
            User::model()->updateByPk($user_id,array('safecard_status'=>1));

            #获取需要解绑的其他银行卡
            $ItzSafeCard = new ItzSafeCard;
            $CDbCriteria = new CDbCriteria;
            $CDbCriteria->condition = "`user_id`='".$user_id."' and card_number !='".$safeCards[0]['card_number']."'";
            $ItzSafeCards = $ItzSafeCard->findAllByAttributes(array(),$CDbCriteria);
            if($ItzSafeCards){
                $ids = array();
                foreach($ItzSafeCards as $v)
                {
                    $ids[] = $v['id'];
                }
                
                $ItzSafeCardExpresspayment = new ItzSafeCardExpresspayment;
                $CDbCriteria = new CDbCriteria;
                $CDbCriteria->condition = "`safe_card_id` in (".implode(',',$ids).") and state=2";
                $Expresspayments = $ItzSafeCardExpresspayment->findAllByAttributes(array(),$CDbCriteria);
                if($Expresspayments)
                {
                    foreach($Expresspayments as $v)
                    {
                        #解绑
                        $this->bankCardUnbind($v);
                        ItzSafeCardExpresspayment::model()->updateAll(array('state'=>0), ' id = :id ',array(':id'=>$v['id']));
                    }
                }
            }

            #其他银行卡全部取消绑定
            ItzSafeCard::model()->updateAll(
                array('status'=>9),
                'user_id = :user_id AND card_number != :card_number',
                array(':user_id'=>$user_id,':card_number'=>$safeCards[0]['card_number'])
            );

            AccountBank::model()->updateAll(
                array('bank_status'=>0),
                'user_id = :user_id AND bank_status = :bank_status',
                array(':user_id'=>$user_id,':bank_status'=>1)
            );

            #获得银行信息
            $bankInfo = BaseCrudService::getInstance()->get('ItzBank', '', 0, 1, '', array('bank_id'=>$safeCards[0]['bank_id']));

            #发送短信
            $remind = array(
                'sent_user'     =>0,        #发送者
                'receive_user'  =>$safeCards[0]['user_id'],     #接受者
                'status'        =>0,
                'nid'           =>'bindCard',
                'type'          =>'bindCard',
                'mtype'         =>'bindCard',
                'data'          =>array(
                    'withdate'  =>date('Y-m-d H:i:s'),
                    'account'   =>substr($safeCards[0]['card_number'],-4,4),
                    'bank'      =>$bankInfo[0]['bank_name']
                )
                );
            NewRemindService::getInstance()->SendToUser($remind,false,false,true);
            return $this->setResult(0,'快捷卡设置成功');
        }
        else
        {
            return $this->setResult(1,'快捷卡设置失败');
        }
    }

    private function getHintMsg($device){
        return $device==6 ? '网银' : 'PC';
    }

    #版本控制
    public function versionControl( $version, $type=true )
    {
        if(empty($_SERVER["HTTP_CLIENT_VERSION"]) || empty($version))
        {
            return false;
        }

        #将版本号 处理成整数
        $v          = $this->formatVersion($_SERVER["HTTP_CLIENT_VERSION"]);
        $version    = $this->formatVersion($version);

        if($type)
        {
            if($v>=$version)
            {
                return true;
            }
        }
        else
        {
            if($v<=$version)
            {
                return true;
            }
        }
        return false;
    }

    #格式化版本号
    public function formatVersion($varsion){
        $arr = explode('.',$varsion);
        if(empty($arr[0])) $arr[0] = 0;
        if(empty($arr[1])) $arr[1] = 0;
        if(empty($arr[2])) $arr[2] = 0;

        $new_version  = intval($arr[0]);
        $new_version .= substr(str_pad(intval($arr[1]), 3, 0,STR_PAD_LEFT),0,3);
        $new_version .= substr(str_pad(intval($arr[2]), 3, 0,STR_PAD_LEFT),0,3);
        return intval($new_version);
    }

    #查询第三方状态
    public function rechargeRepair($trade_no = ''){
        if(!is_numeric($trade_no))
        {
            return $this->setResult(1,'订单号格式错误！');
        }

        #查询订单信息
        $recharge=AccountRecharge::model()->with('paymentInfo')->findByAttributes(array('trade_no'=>$trade_no));
        if(empty($recharge) || empty($recharge->paymentInfo->nid))
        {
            return $this->setResult(1,'订单不存在ITZ数据库！');
        }
        if($recharge->status!=0)
        {
            return $this->setResult(1,'订单状态不是处理中。（status='.$recharge->status.'）');
        }

        #选择通道
        $factory=new ExpressPaymentFactory;        
        $payment=$factory->getPayment($recharge->paymentInfo->nid);

        #查询状态
        $result = $payment->orderInfoQuery($recharge);
        if($result['code']==0)
        {
            #订单成功
            $_inData                = array();
            $_inData['money']       = $recharge->money;
            $_inData['trade_no']    = $recharge->trade_no;
            $_inData['request']     = array();
            AccountService::getInstance()->OnlineReturn( $_inData );
        }
        else
        {
            if(!isset($result['msg']))
            {
                $result['msg'] = '未知错误！';
            }

            $result['msg'] = '补单失败（'.$result['msg'].'）';
        }
        return $result;
    }


	/**
	 * pc省心债权确认页充值提示
	 * @param $uid
	 * @param $needMoney
	 * @param int $device
	 * @param string $card_number
	 * @return array
	 */
	public function getCurrentRechargeInfo($uid,$needMoney,$device=6,$card_number=''){
		#判断金额是否过大
		$maxmoney=$this->getRechargeLimit($uid,$device,$card_number);
//		if($maxmoney==0){
//			return array('code'=>'1','msg'=>$this->getRechargeLimitError($uid,$device,$card_number),'data'=>'');
//		}

		if($needMoney>round($maxmoney,2)){
			return array('code'=>'2','msg'=>$this->getRechargeLimitError2($uid,$device,$card_number,$maxmoney,$needMoney),'data'=>'');
		}
		return array('code'=>'0','msg'=>'success','data'=>'');

	}

}
?>