<?php

/**
 * 获取当前可用的支付方式列表-H5
 *
 * @author 郭峰<guofeng3@ucfgroup.com>
 */

namespace openapi\controllers\payment;

use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\PaymentApi;

/**
 *
 * 获取当前可用的支付方式列表
 * @package openapi\controllers\payment
 */
class PaymentChannelList extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'oauth_token'  => array('filter' => 'required', 'message' => 'oauth_token is required'),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $paymentChannelList = array();
        // 获取当前可用的支付方式
        $paymentChannelListConfig = PaymentApi::getPaymentChannel();
        if (!empty($paymentChannelListConfig))
        {
            foreach ($paymentChannelListConfig as $paymentMethod => $paymentName)
            {
                $paymentChannelList[] = array('paymentMethod'=>$paymentMethod, 'paymentName'=>$paymentName);
            }
        }
        $this->json_data = array('paymentList'=>$paymentChannelList);
        return true;
    }
}