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
    public function getRechargeLimit($uid){
        
        $money = 9999999999;

        return $money;
    }

    /**
     * 获得指定通道的充值金额
     */
    public function getPaymentRechargeLimit($uid){
        
    }
    
    public function LianlianRecharge($uid,$money,$device){
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
    
    public function setResult($code,$msg,$data='')
    {
        return array('code'=>$code,'msg'=>$msg,'data'=>$data);
    }

    #其他充值（微信、）
    public function otherPay($data){

        $data['money'] = round($data['money'],2);
        if($data['money']<=0){
            return $this->setResult(1,'充值金额必须大于0');
        }

        #生成订单号
        $trade_no = $this->getTradeNo($uid);

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

        if(empty($trade_no)){
            return $this->setResult(1,'订单号不能为空！');
        }

        #第二步：调用微信支付
        Yii::import("itzlib.plugins.payment.otherpay.Weixin");
        $pay = new Weixin();
        $r = $pay->getPayResult($trade_no);
        return $this->setResult($r['code'],$r['msg'],$r['data']);
    }
    
    /*
     * 功能：
     * 选取最优的渠道进行充值
     * 
     */
    public function bestRecharge($uid,$money,$device,$card_number='',$phone_number=''){
        $money=floatval($money);
        $money=round($money,2);
        if($money<=0){
            return array('code'=>1,'msg'=>'充值金额必须大于0','data'=>'');
        }

        #判断是否设置快捷卡
        $this->safeCard = ItzSafeCard::model()->findByAttributes(array('user_id'=>$uid,'status'=>2));
        if(empty($this->safeCard))
        {
            if(!is_numeric($card_number))
            {
                return $this->setResult(1,'请填写您的银行卡号码');
            }
            if(strlen($phone_number)!=11)
            {
                return $this->setResult(1,'请填写银行卡预留手机号');
            }

            #查询银行是否存在
            $safeCard=ItzSafeCard::model()->findByAttributes(array('card_number'=>$card_number,'user_id'=>$uid));

            if(empty($safeCard)){
                $safeCard=new ItzSafeCard;
                $safeCard->user_id      =$uid;
                $safeCard->type         ='11';
                $safeCard->card_number  = $card_number;
                $safeCard->bank_id      = 4;//银行卡号暂时写空
                $safeCard->status       = 0;//未确认
                $safeCard->phone        = $phone_number;
                $safeCard->device       = $device;
                $safeCard->addtime      = time();
                $safeCard->modtime      = time();
                $dbr1=$safeCard->save();
            }
            else
            {
                $safeCard->phone        = $phone_number;
                $dbr1=$safeCard->save();
            }
            if($dbr1==false)
            {
                return $this->setResult(1,'系统异常！setSafeCard error');
            }
            $this->safeCard = ItzSafeCard::model()->findByAttributes(array('card_number'=>$card_number,'user_id'=>$uid));
        }

        #充值
        return $this->pollPaymentRecharge($uid,$money,$this->getTradeNo($uid),$data,$device);
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

        #基础参数判断
        if(empty($data['trade_no'])){

            return array('code'=>1,'msg'=>'交易流水号不能为空','data'=>'');
        }
        if(empty($data['verify_code'])){
            return array('code'=>1,'msg'=>'验证码不能为空','data'=>'');
        }
        $recharge=AccountRecharge::model()->with('paymentInfo')->findByAttributes(array('user_id'=>$data['user_id'],'trade_no'=>$data['trade_no']));
        if(empty($recharge)){
            return array('code'=>1,'msg'=>'此交易不存在');
        }


        $this->safeCard = ItzSafeCard::model()->findByAttributes(array('card_number'=>$recharge->card_number,'user_id'=>$data['user_id']));
        User::model()->updateByPk($data['user_id'],array('safecard_status'=>1));
        $safeCard=ItzSafeCard::model()->findByPk($this->safeCard->id);
        $safeCard->status=2;
        $safeCard->save();

        $_inData                = array();
        $_inData['money']       = $recharge->money;
        $_inData['trade_no']    = $data['trade_no'];
        $_inData['request']     = array();
        AccountService::getInstance()->OnlineReturn( $_inData );

       return array('code'=>0,'msg'=>'充值成功','data'=>'');
    }
    
    /**
     * 功能：
     * 选取最优的渠道进行绑卡并充值
     */
    public function bestBindAndRecharge(){
        //简单的判断逻辑，1.通道可用，2通道有绑卡并充值的方法，3按优先级排序轮询
        
    }
    
    /**
     * 功能：
     * 根据绑卡优先级轮询可用的通道并绑卡
     * 
     * 入参：
     * $data=array(
     *  'user_id' 
     *  'phone' #银行预留的手机号
     *  'card'  #银行卡号
     *  'nid'   #nid 代表通道的nid,如果传了此值，并且对应有通道的话，那么直接绑定此通道
     * )
     * 返回：
     * array(
     * 'code'=>,#0失败，1成功
     * 'msg'=>,#信息
     * 'nid'=>''  #返回通道对应的nid
     * )
     */
     public function bestBind($param){
        $phoneLen=strlen($param['phone']);
        if($phoneLen!=11){
            return array('code'=>1,'msg'=>'请输入正确手机号','data'=>'');
        }
        
        if(!is_numeric($param['card'])){
            return array('code'=>1,'msg'=>'请输入正确银行卡号','data'=>'');
        }

        $db = new BankModel();

        #检测卡状态
        $safeCard = $db->checkCardStatus($param['card']);
        if(!empty($safeCard)){
            
            $msg = '此卡已被您绑定';
            
            if($safeCard->user_id!=$param['user_id'])
            {
                $msg = '此卡已被其他用户绑定';
            }
            return array('code'=>1,'msg'=>$msg,'data'=>'');
        }

        #查寻银行卡接口
        $bank_id = 1;
        $param['bank_id'] = $bank_id;


        #用户信息
        $userInfo = User::model()->findByPk($param['user_id']);

        #查询银行是否存在
        $safeCard=ItzSafeCard::model()->findByAttributes(array('card_number'=>$param['card'],'user_id'=>$param['user_id']));
        
        Yii::app()->db->beginTransaction();
        if(empty($safeCard)){
            $safeCard=new ItzSafeCard;
            $safeCard->user_id      =$param['user_id'];
            $safeCard->type         ='yeepay';
            $safeCard->card_number  = $param['card'];
            $safeCard->bank_id      = $bank_id;//银行卡号暂时写空
            $safeCard->status       = 0;//未确认短信钱，是0
            $safeCard->phone        = $param['phone'];
            $safeCard->device       = $param['device'];
            $safeCard->addtime      = time();
            $safeCard->modtime      = time();
            $dbr1=$safeCard->save();
        }
        else
        {
            $safeCard->phone        = $param['phone'];
            $dbr1=$safeCard->save();
        }

        $bind_token = mt_rand(1000,9999).time();

        $safeCardExpresspayment=new ItzSafeCardExpresspayment;
        $safeCardExpresspayment->safe_card_id=$safeCard->id;
        $safeCardExpresspayment->expresspayment_id='11';
        $safeCardExpresspayment->state=0;
        $safeCardExpresspayment->no_agree='';
        $safeCardExpresspayment->bind_result='';
        $safeCardExpresspayment->addtime=time();
        $safeCardExpresspayment->bind_token=$bind_token;
        $safeCardExpresspayment->nid='yeepay';
        $safeCardExpresspayment->user_id=$param['user_id'];
        $dbr2=$safeCardExpresspayment->save();

        if(!empty($dbr1) && !empty($dbr2)){
            Yii::app()->db->commit();
        }else{
            Yii::app()->db->rollback();
            Yii::log('APP>>>insert itz_safe_card error uid='.$param['user_id']);
            return array('code'=>1,'msg'=>'网络异常请重试','data'=>'');
        }
        $result=array('code'=>0,'msg'=>' ','data'=>array('bind_no'=>$bind_token));
        return $result;
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

        $_SERVER["HTTP_CLIENT_VERSION"] = '1.5.0';

        #基本参数判断
        if(empty($data['user_id']) || empty($data['verify_code']) || empty($data['bind_no']))
        {
            return array('code'=>'434','msg'=>'系统错误！[434]','data'=>array());
        }

        #检查是否有过绑卡
        $safeCardExpresspayment = ItzSafeCardExpresspayment::model()->findByAttributes(array('bind_token'=>$data['bind_no'],'user_id'=>$data['user_id']));
        if(empty($safeCardExpresspayment))
        {
            return array('code'=>'440','msg'=>'系统错误！[440]','data'=>array());
        }


        #绑卡确认
        $r['code'] = 0;
        $r['no_agree'] = 1111;
        $r['data'] = 0;

        if($r['code']=='0'){
            
            User::model()->updateByPk($data['user_id'],array('safecard_status'=>1));
            $safeCard=ItzSafeCard::model()->findByPk($safeCardExpresspayment->safe_card_id);
            if($safeCard->status==0 || $safeCard->status==9 ){//如果status为0，则更新status，1,2情况则不处理
                Yii::app()->db->beginTransaction();
                $safeCard->status=2;
                $dbr1=$safeCard->save();
            }
            $safeCardExpresspayment->state=2;
            $safeCardExpresspayment->no_agree=$r['no_agree'];
            $safeCardExpresspayment->verify_result=json_encode($r['data']);
            $dbr2=$safeCardExpresspayment->save();
            
            if(isset($dbr1)){//如果有dbr1，则要进入事务
                if($dbr1 && $dbr2){
                  Yii::app()->db->commit();
                }else{
                  Yii::app()->db->rollback();
                  return array('code'=>'1','msg'=>'网络异常请重试','data'=>'');
                }
            }

            //绑卡成功后，查询银行的逻辑
            $cards = BaseCrudService::getInstance()->get('ItzSafeCard','',0,1,'',array('id'=>$safeCardExpresspayment->safe_card_id), null, array('bankInfo'));
            $userInfo=User::model()->findByPk($safeCardExpresspayment->user_id);
            $data2=array('card'=>$cards[0],'needVerifyPhone'=>($userInfo->phone==$cards[0]['phone']?0:1));
            $result=array('code'=>'0','msg'=>'绑卡成功','data'=>$data2);
        }else{
            $result=array('code'=>'1','msg'=>$r['msg'],'data'=>'');
            Yii::log('APP>>>bestBindVerify_ERROR:nid-'.$safeCardExpresspayment->nid.':'.json_encode($r),'error');
        }
        return $result;
    }
    
    
    /**
     * 计算全部可用于充值的通道，并根据手续费和充值优先级排序,返回排序后的数组
     * 
     * 【此函数逻辑复杂，暂时先用easy版】
     * 
     */ 
    private function getAviableRechargePayment($uid,$money){
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
    private function getAviableRechargePayment_easy($uid,$money){

        $cardInfo=ItzSafeCard::model()->findByAttributes(array('user_id'=>$uid,'status'=>2));

        #获得可用通道（没有超过单笔限额）
        $sql='select e.id from itz_expresspayment_banklimit as l
              inner join itz_expresspayment as e on l.expresspayment_id = e.id and e.rechargelevel > -1 and e.state=1
              where  l.status = 1 and l.bank_id='.$cardInfo->bank_id.' and l.every_limit>='.$money;
        $r=ItzExpresspaymentBanklimit::model()->findAllBySql($sql);
        if(empty($r))
        {
            return array();
        }

        $ids = array();
        foreach($r as $v)
        {
            $ids[] = $v->id;
        }

        #根据费率排序
        $cdb=new CDbCriteria;
        $cdb->condition=' `status`=0  and  `start`<'.$money.' and `end`>='.$money;
        $cdb->addInCondition('expresspayment_id', $ids);
        $formula=ItzExpresspaymentFormula::model()->findAll($cdb);
        if(empty($formula))
        {
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
    
    
    
    /*
     * 轮询全部可用的通道，尝试充值,但不发短信
     * 
     * $trade_no为我们自己生成的流水号
     */
    private function pollPaymentRecharge($uid,$money,$trade_no,$expressPayments,$device){

        #获取快捷卡信息
        $cardInfo=$this->safeCard;
        
        #给手机号加*隐藏
        $userPhone=$cardInfo->phone;
        if(strlen($userPhone)>=11)
        {
            $userPhone[6] = $userPhone[5] = $userPhone[4] = $userPhone[3] = '*';
        }

        $factory=new ExpressPaymentFactory;

        $db = new BankModel();

        #调用可用通道循环充值（成功为止）
        $v='11';

            $result=array();

            #获得通道信息
            $r=ItzExpresspayment::model()->with('payment')->findByPk($v);

            #选择通道类
            $payment=$factory->getPayment($r->payment->nid);

                $add_status = $db->addAccountRecharge(array(
                    'trade_no'          =>$trade_no,
                    'user_id'           =>$uid,
                    'money'             =>$money,
                    'type'              =>$device,
                    'payment'           =>$r->payment_id,
                    'api_once_return'   =>'',
                    'card_number'       =>$cardInfo->card_number,
                    'bank_id'           =>$cardInfo->bank_id,
                    'app_version'       =>'',
                    'device_model'      =>''
                    ));
                if($add_status)
                {
                    return array(
                        'code'  =>0,
                        'msg'   =>'',
                        'data'  =>array(
                            'trade_no'      =>$trade_no,
                            'amount'        =>$money,
                            'phoneNumber'   =>$userPhone,
                            'type'          =>'ServerSidePay'
                            )
                        );
                }
                $result=array('code'=>'1','msg'=>'充值异常， 请联系客服或者使用PC充值','data'=>'');

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

        #判断订单是否存在
        $recharge=AccountRecharge::model()->with('paymentInfo')->findByAttributes(array('user_id'=>$data['user_id'],'trade_no'=>$data['trade_no']));
        if(empty($recharge)){

            #审计日志 充值
            AuditLog::getInstance()->method('add', array(
                "user_id"   => $data['user_id'],
                "system"    => 'app/'.$this->getDeviceOs(),
                "action"    => 'recharge',
                "resource"  => 'user/app_recharge/step1',
                "status"    => 'fail',#success|fail
                "parameters"=> json_encode(array(
                    'app_version'   =>$_SERVER["HTTP_CLIENT_VERSION"],

                    'trade_no'      =>$data["trade_no"],
                    'info'          =>'充值订单不存在'
                ),JSON_UNESCAPED_UNICODE)
            ));

            return array(
                'code'=>'1',
                'msg'=>'充值订单不存在',
                'data'=>'',
            );
        }

        #选择通道
        $factory=new ExpressPaymentFactory;
        $payment=$factory->getPayment($recharge->paymentInfo->nid);

        #获取快捷卡信息
        $cardInfo=ItzSafeCard::model()->findByAttributes(array('user_id'=>$data['user_id'],'status'=>2));
        if(empty($cardInfo)){

            #审计日志 充值
            AuditLog::getInstance()->method('add', array(
                "user_id"   => $data['user_id'],
                "system"    => 'app/'.$this->getDeviceOs(),
                "action"    => 'recharge',
                "resource"  => 'user/app_recharge/step1',
                "status"    => 'fail',#success|fail
                "parameters"=> json_encode(array(
                    'app_version'   =>$_SERVER["HTTP_CLIENT_VERSION"],
                    'nid'           =>$recharge->paymentInfo->nid,

                    'trade_no'      =>$data["trade_no"],
                    'info'          =>'用户无快捷卡'
                ),JSON_UNESCAPED_UNICODE)
            ));

            return array(
                'code'=>'1',
                'msg'=>'用户无快捷卡',
                'data'=>'',
            );
        }

        #开始发短信
        $sql='select * from itz_safe_card_expresspayment as a left join itz_expresspayment as b on a.expresspayment_id = b.id where a.safe_card_id=:safe_card_id and b.payment_id=:payment_id and a.state=2';
        $SafeCardExpresspayment_info=ItzSafeCardExpresspayment::model()->findBySql($sql,array(':safe_card_id'=>$cardInfo->id,':payment_id'=>$recharge->paymentInfo->id));

        if(!empty($SafeCardExpresspayment_info)){
            $r=$payment->rechargeSms($data);
        }else{
            $r=$payment->bindAndRechargeSms($data);
        }


        #审计日志 充值
        AuditLog::getInstance()->method('add', array(
            "user_id"   => $data['user_id'],
            "system"    => 'app/'.$this->getDeviceOs(),
            "action"    => 'recharge',
            "resource"  => 'user/app_recharge/step1',
            "status"    => 'fail',#success|fail
            "parameters"=> json_encode(array(
                'app_version'   =>$_SERVER["HTTP_CLIENT_VERSION"],
                'nid'           =>$recharge->paymentInfo->nid,

                'trade_no'      =>$data["trade_no"],

                'code'          =>$r3['code'],
                'info'          =>$r3['msg'],
                'info_result'   =>$r3
            ),JSON_UNESCAPED_UNICODE)
        ));

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


        #用户信息
        $userInfo = User::model()->findByPk($Expresspayment['user_id']);

        #快捷卡信息
        $safe_card = ItzSafeCard::model()->findByAttributes(array('user_id'=>$Expresspayment['user_id'],'status'=>array(9,2)));

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
    public function getOnBankList(){

        #获得银行名称
        $db = new BankModel();
        $banks = $db->getBankList();

        $list = array();

        #查询可用的银行
        $sql='select l.* from itz_expresspayment_banklimit as l
              inner join itz_expresspayment as e on l.expresspayment_id = e.id and e.bindlevel > -1 and e.state=1
              where  l.status = 1';
        $r=ItzExpresspaymentBanklimit::model()->findAllBySql($sql);
        if($r)
        {
            foreach($r as $v)
            {
                if(!isset($list[$v->bank_id]))
                {
                    $list[$v->bank_id] = array(
                        'bank_id'           =>$v->bank_id,
                        'bank_name'         =>$banks[$v->bank_id],
                        'money_once_limit'  =>9999999999,
                        'money_day_limit'   =>9999999999
                        );
                }
                else
                {
                    $list[$v->bank_id]['money_day_limit']=9999999999;
                }

 
            }
            usort($list, "self::moneySort");
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
        }else{
            return '';
        }
    }
}
?>
