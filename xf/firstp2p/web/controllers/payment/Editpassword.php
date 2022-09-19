<?php

namespace web\controllers\payment;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\web\Url;
use core\service\PaymentService;

class Editpassword  extends BaseAction {

    public function init() {
        return $this->check_login();
    }


    public function invoke() {
        if(app_conf('PAYMENT_ENABLE') == '0'){
            showErr('系统维护中，请稍后再试', 0,'/account' , 0 );
        }


        //如果未绑定手机
        if(intval($GLOBALS['user_info']['mobilepassed'])==0 || intval($GLOBALS['user_info']['idcardpassed'])==0){
            showErr('请先填写身份证信息', 0,'/account/addbank' , 0 );
            return;
        }

        //增加支付平台开户check
        if(empty($GLOBALS['user_info']['payment_user_id'])){
            //showErr('无法进行投保');
            showErr('无法修改支付密码',0,'/account',0);
        }

        // 用户的支付ID
        $payment_user_id =  $GLOBALS['user_info']['payment_user_id'];
        // 生成Form表单
        $payment_code = PaymentApi::instance()->getGateway()->getForm('pwdupdate', array('userId'=>$payment_user_id), 'redirect_form', false);

        $this->tpl->assign("payment_code",$payment_code);
        $this->tpl->assign("payment_title",'正在跳转到修改支付密码页面');
        $this->tpl->assign("payment_tip",'正在跳转到修改支付密码页面，请稍等....');
        $this->tpl->assign("inc_file","web/views/payment/startpay.html");
        $this->template = "web/views/account/frame.html";

    }

    
}
