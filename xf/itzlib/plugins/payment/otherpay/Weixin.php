<?php
include_once(WWW_DIR . "/itzlib/plugins/payment/include/weixin/WxPay.Api.php");
include_once(WWW_DIR . "/itzlib/plugins/payment/include/weixin/WxPay.Config.php");
include_once(WWW_DIR . "/itzlib/plugins/payment/include/weixin/WxPay.Data.php");
include_once(WWW_DIR . "/itzlib/plugins/payment/include/weixin/WxPay.Exception.php");
include_once(WWW_DIR . "/itzlib/plugins/payment/include/weixin/WxPay.JsApiPay.php");
include_once(WWW_DIR . "/itzlib/plugins/payment/include/weixin/WxPay.MicroPay.php");
include_once(WWW_DIR . "/itzlib/plugins/payment/include/weixin/WxPay.NativePay.php");
class Weixin{
    public $paymentNid = 'weixinpay';
    public $Conf = array();

    #订单信息
    public $order = null;
    public function __construct($config){
        $this->setConf();

        WxPayConfig::$APPID = $this->Conf['appid'];
        WxPayConfig::$MCHID = $this->Conf['mchid'];
        WxPayConfig::$KEY   = $this->Conf['key'];
    }

    #获取商户信息
    protected function setConf(){

        if(empty($this->Conf)){            
            $paymentRecord = Payment::model()->findByAttributes(array('nid' => $this->paymentNid));
            if(empty($paymentRecord)){
                Yii::log('ebatong GetInfoByNid error:'.$this->paymentNid,'error');
            }else{
                $this->Conf = unserialize($paymentRecord->config);
            }
        }
    }

    #获得支付二维码
    public function getPayInfo($rechargeInfo){

        #配置支付参数
        $input = new WxPayUnifiedOrder();
        $input->SetBody('订单支付');#商品或支付单简要描述
        $input->SetOut_trade_no($rechargeInfo['trade_no']);#商户系统内部的订单号
        $input->SetTotal_fee($rechargeInfo['money']*100);#订单总金额
        $input->SetNotify_url('https://www.xxx.com/newuser/paymentNotify/Weixin');
        $input->SetTrade_type("APP");#原生扫码支付
        $input->SetTime_start(date("YmdHis"));#订单生成时间
        $input->SetTime_expire(date("YmdHis", time() + 86400));#订单失效时间
        $input->setVal('limit_pay','no_credit');
        $r = WxPayApi::unifiedOrder($input);
        if($r['return_code']=='FAIL')
        {
            return $this->setResult(1,'微信系统异常：'.$r['return_msg']);
        }
        if($r['result_code']=='FAIL')
        {
            return $this->setResult(1,'微信系统异常：'.$r['err_code_des'].'['.$r['err_code'].']');
        }
        #配置支付参数
        $input = new WxPayUnifiedOrder();
        $input->setVal('appid',WxPayConfig::$APPID);
        $input->setVal('partnerid',WxPayConfig::$MCHID);
        $input->setVal('prepayid',$r['prepay_id']);
        $input->setVal('package','Sign=WXPay');
        $input->setVal('noncestr',WxPayApi::getNonceStr());
        $input->setVal('timestamp',(String)time());
		$input->SetSign();
        $val = $input->GetValues();
        if(count($val)!=7)
        {
            return $this->setResult(1,'微信签名错误！');
        }

        return $this->setResult(0,'',$val);
    }

    #获得充值结果
    public function getPayResult($order_sn){

        $input = new WxPayOrderQuery();
        $input->SetOut_trade_no($order_sn);
        $r = WxPayApi::orderQuery($input);

        $trade_state = 3;
        if($r['result_code']=='SUCCESS' && $r['return_code']=='SUCCESS')
        {
            if($r['trade_state']=='SUCCESS')
            {
                $trade_state = 0;
            }
            if($r['trade_state']=='NOTPAY')
            {
                $trade_state = 1;
            }
            if($r['trade_state']=='CLOSED')
            {
                $trade_state = 2;
            }
            if($r['trade_state']=='PAYERROR')
            {
                $trade_state = 4;
            }
        }
        if($r['result_code']=='FAIL')
        {
            $trade_state = 1;
        }
        return $this->setResult(0,'',array('trade_state'=>$trade_state));
    }

    #关闭 订单
    public function closeOrder($order_sn){

        $input = new WxPayOrderQuery();
        $input->SetOut_trade_no($order_sn);
        $r = WxPayApi::closeOrder($input);
        if($r['result_code']=='SUCCESS' && $r['return_code']=='SUCCESS')
        {
            return $this->setResult(0,'','');
        }
        return $this->setResult(1,'','');
    }

    #处理异步通知结果
    public function getNotify(){
        try {

            //获取通知的数据
            $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
            if(empty($xml))
            {
                return $this->setResult(1,'支付失败');
            }
            $order = WxPayResults::Init($xml);
            if($order['result_code']=='SUCCESS' && $order['return_code']=='SUCCESS')
            {
                return $this->setResult(0,'',array(
                    'sn'    =>$order['out_trade_no'],
                    'money' =>round($order['total_fee']/100,2)
                ));
            }
            elseif($order['result_code']=='FAIL')
            {
                $error_msg = '';

                #支付失败
                if($order['err_code'])
                {
                    $error_msg .= $order['err_code'].'|';
                }
                if($order['err_code_des'])
                {
                    $error_msg .= $order['err_code_des'];
                }
                return $this->setResult(2,$error_msg);
            }
            elseif($order['return_code']=='FAIL')
            {
                return $this->setResult(1,$order['return_msg'],array('sn'=>$order['out_trade_no']));
            }

        } catch (Exception $e) {
            return $this->setResult(1,$e->getMessage());
        }
    }

    public function setNotifyResult($status = 0,$msg = 'ok'){
        $WxPayNotifyReply = new WxPayNotifyReply();
        $WxPayNotifyReply->SetReturn_code(($status==200) ? "SUCCESS" : 'FAIL');
        $WxPayNotifyReply->SetReturn_msg($msg);
        if($status==200)
        {
            $WxPayNotifyReply->SetSign();
        }
        WxpayApi::replyNotify($WxPayNotifyReply->ToXml());
    }

    public function setResult($code,$msg,$data='')
    {
        return array('code'=>$code,'msg'=>$msg,'data'=>$data);
    }

}