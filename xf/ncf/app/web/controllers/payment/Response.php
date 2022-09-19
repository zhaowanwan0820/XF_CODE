<?php

/**
 * 支付回调
 * @author yangqing<yangqing@ucfgroup.com>
 */

namespace web\controllers\payment;

use libs\web\Form;
use web\controllers\BaseAction;

class Response extends BaseAction {

    public function init() {
    }

    public function invoke() {
        \libs\utils\PaymentApi::log('PayException. 该充值回调接口已弃用. params:'.json_encode($_REQUEST));

        return false;

        //支付跳转返回页
        if ($GLOBALS['pay_req']['class_name'])
            $_REQUEST['class_name'] = $GLOBALS['pay_req']['class_name'];

        $class_name = addslashes(trim($_REQUEST['class_name']));
        $payment_info = $this->rpc->local('PaymentService\getPaymentByClassname',array($class_name));
        if ($payment_info) {
            \FP::import("libs.payment." . $payment_info['class_name'] . "_payment");
            $payment_class = $payment_info['class_name'] . "_payment";
            $payment_object = new $payment_class();
            adddeepslashes($_REQUEST);
            $payment_code = $payment_object->response($_REQUEST);
        } else {
            return $this->show_error($GLOBALS['lang']['PAYMENT_NOT_EXIST']);
        }
        
    }
}
