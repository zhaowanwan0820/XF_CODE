<?php
/**
 * 宝付
 */
Yii::import('itzlib.plugins.payment.AbstractExpressPaymentClass');//引如抽象类
Yii::import('itzlib.plugins.payment.interface.*');//引入接口类
Yii::import('itzlib.plugins.payment.include.baofupay.BAOFOOSDK.*');

#include_once(WWW_DIR . "/itzlib/plugins/payment/include/liandong/mer2Plat.php");
class BaofupayClass extends  AbstractExpressPaymentClass implements ApiInterface{
    protected $paymentNid= 'baofupay';//在ITZ的nid

    protected $member_id = "100000276";	//商户号

    protected $version = "4.0.0.0";//版本号
    protected $terminal_id = "100000993";	//终端号
    protected $data_type="json";//加密报文的数据类型（xml/json）
    protected $txn_type = "0431";//交易类型
    protected $private_key = "123456";	//商户私钥证书密

    protected $biz_type = "0000";//接入类型
    protected $id_card_type="01";//证件类型固定01（身份证） 
    protected $acc_pwd="";//银行卡密码（传空）
    protected $valid_date = "";//卡有效期 （传空）
    protected $valid_no ="";//卡安全码（传空）
    protected $additional_info="附加字段";//附加字段
    protected $req_reserved="保留";//保留
    //protected $rquest_url = "https://vgw.baofoo.com/cutpayment/api/backTransRequest"; //测试环境请求地址
    protected $rquest_url = "https://public.baofoo.com/cutpayment/api/backTransRequest"; //正式环境请求地址

    protected $cer = "/itzlib/plugins/payment/include/baofupay/CER/baofoo_pub.cer";
    protected $pfx = "/itzlib/plugins/payment/include/baofupay/CER/m_pri.pfx";

	public function __construct() {
        $this->cer = WWW_DIR.$this->cer;
        $this->pfx = WWW_DIR.$this->pfx;

        #获取配置信息
        $this->getPaymentConfig();

        #商户号
        $this->member_id   = $this->config['member_id'];

        #商户密码
        $this->private_key = $this->config['private_key_password'];

        #终端号码
        $this->terminal_id   = $this->config['terminal_id'];
    }

    #初始化数据
    private function initData($data){

        //====================系统动态生成值=======================================
        $trans_serial_no = "TSN".time().mt_rand(00000,99999);	//商户流水号
        $trade_date = date('YmdHis');	//订单日期
        //================报文组装=================================


        $data_content_parms = array('biz_type' =>$this->biz_type,
                                    'terminal_id' =>$this->terminal_id,
                                    'member_id' =>$this->member_id,
                                    'trans_serial_no' =>$trans_serial_no,
                                    'trade_date' =>$trade_date,
                                    'additional_info' =>$this->additional_info,
                                    'req_reserved' =>$this->req_reserved)+$data;
        return $data_content_parms;
    }

    #获取商户信息
    protected function getPaymentConfig(){
        if(empty($this->config)){            
            $paymentRecord = Payment::model()->findByAttributes(array('nid' => $this->paymentNid));

            if(empty($paymentRecord)){
                Yii::log('yeepay GetInfoByNid error:'.$this->paymentNid,'error');
            }else{
                $this->config = unserialize($paymentRecord->config);
            }
        }
    }

    public function rechargeVerify($data,$ItzSafeCard,$userInfo){

        #充值订单信息
        $accountRecharge=AccountRecharge::model()->findByAttributes(array('trade_no'=>$data['trade_no']));
        
        $sql='select a.no_agree,a.id from itz_safe_card_expresspayment as a 
            left join itz_expresspayment b on a.expresspayment_id = b.id
            where a.safe_card_id=:safe_card_id and b.payment_id=:payment_id
            order by a.id desc
            ';
        #绑卡信息
        $SafeCardExpresspayment=ItzSafeCardExpresspayment::model()->findBySql($sql,array(':safe_card_id'=>$ItzSafeCard['id'],':payment_id'=>$accountRecharge->payment));

        $txn_amt = $accountRecharge['money'];//交易金额额
        $txn_amt *=100;//金额以分为单位（把元转换成

        $data_content_parms["trans_id"] =$data['trade_no'];
        $data_content_parms["bind_id"] =$SafeCardExpresspayment->no_agree ;
        $data_content_parms["txn_amt"] =$txn_amt ;
        $data_content_parms["sms_code"] =$data['verify_code'] ;
        $data_content_parms["txn_sub_type"]='04';

        $data = $this->initData($data_content_parms);
        $r = $this->sendData($data);
        if($r['resp_code']=='0000'){
            return $this->result(0,'',$r);
        }else{
            $code = $r['resp_code'];
            return $this->result($code,'充值失败['.$r['resp_msg'].']',array(
                'send'      =>$data,
                'get'       =>$r
            ) );
        }
    }

    #快捷支付手机动态鉴权
    public function recharge($data,$ItzSafeCard,$userInfo){

        if(empty($data['expresspayment_id']))
        {
            $expresspayment=ItzExpresspayment::model()->findByAttributes(array('payment_id'=>$data['payment_id']));
            $data['expresspayment_id'] = $expresspayment['id'];
        }

        if(empty($_POST['baofu_bind_no']))
        {
            $safe_card_ext = ItzSafeCardExpresspayment::model()->findByAttributes(array('safe_card_id'=>$ItzSafeCard->id,'expresspayment_id'=>$data['expresspayment_id']));

            $_POST['baofu_bind_no'] = $safe_card_ext['no_agree'];
        }
        if(empty($_POST['baofu_bind_no']))
        {
            return $this->result(1,'绑卡协议号为空');
        }

        $txn_amt = $data['money'];//交易金额额
        $txn_amt *=100;//金额以分为单位（把元转换成
        $data_content_parms = array();
        $data_content_parms["bind_id"] =$_POST['baofu_bind_no'] ;
        $data_content_parms["trans_id"] =$data['trade_no'];
        $data_content_parms["mobile"] =$ItzSafeCard->phone ;
        $data_content_parms["acc_no"] =$ItzSafeCard->card_number ;
        $data_content_parms["txn_amt"]=$txn_amt;
        $data_content_parms["next_txn_sub_type"]='04';
        $data_content_parms["txn_sub_type"]='05';

        $data = $this->initData($data_content_parms);
        $r = $this->sendData($data);
        if($r['resp_code']=='0000')
        {
            return $this->result(0,'',array(
                'send'      =>$data,
                'get'       =>$r,
                'data'      =>$data,
                'bind_no'   =>$r['bind_id']
                ));
        }
        else
        {
            return $this->result($r['resp_code'],'充值失败['.$r['resp_msg'].']',array(
                'send'      =>$data,
                'get'       =>$r
                ) );
        }
    }

    #绑卡
    public function bindCard($param,$userInfo){

        $banks = array(
            1=>'ICBC',//中国工商银行
            2=>'CCB',//中国建设银行
            3=>'ABC',//中国农业银行
            4=>'CMB',//招商银行
            5=>'PSBC',//中国邮政储蓄银行
            6=>'CMBC',//中国民生银行
            7=>'CITIC',//中信银行
            8=>'PAB',//平安银行
            9=>'BOC',//中国银行
            10=>'BCOM',//中国交通银行
            11=>'CIB',//兴业银行
            13=>'CEB',//中国光大银行
            14=>'GDB',//广发银行
            19=>'SPDB',//浦东发展银行
            23=>'SHB',//上海银行
            );
        #查寻银行卡接口
        $db = new BankModel();
        $bank_id = $db->getBankId(array(
            'card'      =>$param['card'],
            'phone'     =>$param['phone'],
            'user_id'   =>$userInfo['user_id']
            ));

        if(!isset($banks[$bank_id]))
        {
            return $this->result(100,'银行信息查询失败[宝付]');
        }

        $data = $this->initData(array(
            'txn_sub_type'  =>'01',
            'acc_no'        =>$param['card'],
            'trans_id'      =>time().($userInfo['user_id']%9).rand(10000,99999),
            'id_card_type'  =>'01',//证件类型固定01（身份证）
            'id_card'       =>$userInfo['card_id'],
            'id_holder'     =>$userInfo['realname'],
            'mobile'        =>$param['phone'],
            'acc_pwd'       =>'',
            'valid_date'    =>'',
            'valid_no'      =>'',
            'pay_code'      =>$banks[$bank_id],#银行编码，需要自动识别
            'sms_code'      =>'',
            ));
        $r = $this->sendData($data);
        if($r['resp_code']=='0000')
        {
            return $this->result(0,'',array(
                'send'      =>$data,
                'get'       =>$r,
                'data'      =>$data,
                'bind_no'   =>$r['bind_id']
                ));
        }
        else
        {
            return $this->result($r['resp_code'],'充值失败['.$r['resp_msg'].']',array(
                'send'      =>$data,
                'get'       =>$r
                ) );
        }
    }

    #绑卡
    public function bindVerfy($data){
        return $this->result(100,'宝付没有做单独绑卡');
    }

    #查绑卡信息
    public function bindCardGet($customerId){
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <MasMessage xmlns="http://www.99bill.com/mas_cnp_merchant_interface">
        <version>1.0</version>
        <PciQueryContent>
            <merchantId>'.$this->merchantId.'</merchantId>
            <customerId>'.$customerId.'</customerId>
            <cardType>0002</cardType>
        </PciQueryContent>
        </MasMessage>';
        return $this->sendTr1('https://mas.99bill.com/cnp/pci_query',$xml);

    }

    #绑卡并充值
    public function bindAndRecharge($data,$safeCard,$userInfo){

        $safe_card_ext = ItzSafeCardExpresspayment::model()->findByAttributes(array('safe_card_id'=>$safeCard->id,'expresspayment_id'=>$data['expresspayment_id']));

        #绑卡
        $r = $this->bindCard(array('card'=>$safeCard->card_number,'phone'=>$safeCard->phone),$userInfo);
        if($r['code'])
        {
            return $this->result($r['code'],$r['msg'],$r['data']);
        }
        if(empty($safe_card_ext))
        {
            #保存绑卡信息
            $safeCardExpressment=new ItzSafeCardExpresspayment;
            $safeCardExpressment->safe_card_id=$safeCard->id;
            $safeCardExpressment->state=0;
            $safeCardExpressment->no_agree=$r['data']['bind_no'];
            $safeCardExpressment->expresspayment_id=$data['expresspayment_id'];
            $safeCardExpressment->bind_result='';//api方式的绑卡结果，就是写入流水表时的结果
            $safeCardExpressment->verify_result=json_encode($r['data']);
            $safeCardExpressment->addtime=time();
            $safeCardExpressment->nid=$this->paymentNid;
            $safeCardExpressment->user_id=$data['user_id'];
            if($safeCardExpressment->save()!==true)
            {
                Yii::log('APP>>>ItzSafeCardExpresspayment insert error .'.json_encode($safeCardExpressment->getErrors()),'error');

                return $this->result(1,'系统繁忙请稍后再试！[db]');
            }
        }
        else
        {
            #保存绑卡信息
            $safe_card_ext->no_agree=$r['data']['bind_no'];
            $safe_card_ext->verify_result=json_encode($r['data']);
            $safe_card_ext->addtime=time();
            if($safe_card_ext->save()!==true)
            {
                Yii::log('APP>>>ItzSafeCardExpresspayment insert error .'.json_encode($safeCardExpressment->getErrors()),'error');

                return $this->result(1,'系统繁忙请稍后再试！[db]');
            }
        }

        #存储绑卡协议好
        $_POST['baofu_bind_no'] = $r['data']['bind_no'];
        return $this->recharge($data,$safeCard,$userInfo);
    }
    
    #绑卡并充值确认
    public function bindAndRechargeVerify($data,$safeCard,$userInfo){
        return $this->rechargeVerify($data,$safeCard,$userInfo);
    }
    
    #绑卡并充值_重新发送验证码
    public function bindAndRechargeSms($data,$safeCard,$userInfo){
        
        #获得绑卡信息
        $AccountRecharge=AccountRecharge::model()->find("`trade_no`='".$data['trade_no']."'");
        if($AccountRecharge)
        {
            $data['money'] = $AccountRecharge->money;
            $this->recharge($data,$safeCard,$userInfo);
        }
        return $this->result(0,'发送成功',array('send'=>'','get'=>''));
    }

    #充值_重新发送验证码
    public function rechargeSms($data,$safeCard,$userInfo){
        return $this->bindAndRechargeSms($data,$safeCard,$userInfo);
    }

    //解绑确认
    public function bankCardUnbind($data,$userInfo,$safe_card){
        $data = $this->initData(array(
            'txn_sub_type'  =>'02',
            'bind_id'       =>$data['no_agree']
            ));
        $r = $this->sendData($data);
        if($r['resp_code']=='0000')
        {
            return $this->result(0,$r['resp_msg']);
        }
        else
        {
            return $this->result(1,$r['resp_msg']);
        }
    }

    //获取支付通知结果
    public function noticeResult($data){}
    
    //获取支付回调结果
    public function returnResult($data){
        if(empty($data))
        {
            return array();
        }

        $baofoosdk = new BaofooSdk($this->member_id, $this->terminal_id, 'json', $this->pfx,$this->cer,$this->private_key); //实例化加密类。		  

        $r = $baofoosdk->decryptByPublicKey($data);
        if(empty($r))
        {
            return array();
        }
        
        return json_decode($r,true);
    }

    //获取支付通知参数
    public function getNoticeData(){}

    //传入请求地址函数，用于向指定url传入数据
    public function request($path, $params,$json = true){}

    public function sendData($data){

        $Encrypted_string = str_replace("\\/", "/",json_encode($data));//转JSON

        $baofoosdk = new BaofooSdk($this->member_id, $this->terminal_id, 'json', $this->pfx,$this->cer,$this->private_key); //实例化加密类。		  

        $data_content = $baofoosdk->encryptedByPrivateKey($Encrypted_string);	//RSA加密

        $return_string = $baofoosdk->post($data_content,$this->rquest_url,$this->txn_type,$this->version,$data['txn_sub_type']);
        
        $decrypt_return_string = $baofoosdk->decryptByPublicKey($return_string);
        $r = json_decode($decrypt_return_string,true);

        #异常处理
        if(empty($r))
        {
            $r = array(
                'resp_code' =>'BF00100',
                'resp_msg'  =>'系统繁忙请稍后再试！[宝付]'
                );
        }
        return $r;
    }

    #返回结果
    public function result( $code, $msg, $data = array()){
    	if($code && $code!='100'){ // 替换msg
    		$exception = array('BF00100','BF00112','BF00113','BF00115','BF00144','BF00202','BF00238','BF00254');
    		$info = ReturnService::getInstance()->getReturn('baofupay',$code);
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

        #查询
        $data = $this->initData(array(
            'txn_sub_type'  =>'03',
            'acc_no'        =>$cardInfo->card_number
            ));
        $r = $this->sendData($data);

        $bank_list = array();
        if(!empty($r['bind_id']))
        {
            $bank_list[] = array(
                'channel_name'  =>'宝付支付',
                'bank_name'     =>$bank['bank_name'],
                'card_no'       =>$cardInfo->card_number,
                'no_agree'      =>$r['bind_id'],
                'tel'           =>'不支持查询'
                );
        }
        return $bank_list;
    }

    #订单信息查询
    public function orderInfoQuery($recharge){
        $data_content_parms = array();
        $data_content_parms["orig_trans_id"]    = $recharge->trade_no;
        $data_content_parms["txn_sub_type"]     = '06';
        $data = $this->initData($data_content_parms);
        $r = $this->sendData($data);
        if($r['resp_code']=='0000')
        {
            return array('code'=>0,'msg'=>'','data'=>array());
        }
        else
        {
            return array('code'=>1,'msg'=>$r['resp_code'].$r['resp_msg'],'data'=>array());
        }
    }
}
