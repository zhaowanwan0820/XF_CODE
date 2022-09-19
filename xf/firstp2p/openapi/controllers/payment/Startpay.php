<?php

/**
 * Startpay.php
 * 
 * Filename: Startpay.php
 * Descrition: 第三方web充值接口
 * Author: yutao@ucfgroup.com
 * Date: 16-3-2 下午6:14
 */

namespace openapi\controllers\payment;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\dao\PaymentNoticeModel;
use core\dao\PaymentModel;
use core\service\PaymentService;
use core\dao\DealOrderModel;
use libs\utils\PaymentApi;

class Startpay extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            'id' => array('filter' => 'int'),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }

        $result = array();
        $id = $this->form->data['id'];
        if (empty($id)) {
            $this->setErr('ERR_PAYMENT_ID_NULL');
            return false;
        }
        $paymentNotice = PaymentNoticeModel::instance()->find($id);
        if ($paymentNotice['user_id'] != $userInfo->userId) {
            $this->setErr('ERR_USER_DIFF');
            return false;
        }

        //增加支付平台单笔交易查询判断
        $status = $this->rpc->local('PaymentService\chargeStatusByPaymentNoticeNo', array($paymentNotice['notice_sn']));
        if ($paymentNotice['is_paid'] == 0 && $status == PaymentService::ERROR_PAYMENT_ORDER_NOTEXITS) {
            //是否可信
            $isCredible = $this->rpc->local('UserCreditService\isCredible', array($userInfo->userId));

            $torechargeParam['amount'] = $paymentNotice['money'] * 100; //单位为分
            $torechargeParam['curType'] = 'CNY';
            $torechargeParam['userId'] = $userInfo->userId; //$GLOBALS ['user_info'] ['payment_user_id']
            $torechargeParam['outOrderId'] = $paymentNotice['notice_sn'];
            $torechargeParam['fastPayFlag'] = $isCredible ? '1' : '0';

            $paymentCode = PaymentApi::instance()->getGateway()->getForm('torecharge', $torechargeParam, 'redirect_form', false);
            if (empty($paymentCode)) {
                $this->setErr('ERR_PAYMENT_CODE');
                return false;
            }
            $result['payment_code'] = $paymentCode;
        } else if ($paymentNotice['is_paid'] == 2 || $status == PaymentService::CHARGE_PENDING) {
            $this->setErr('ERR_CHARGE_PENDING');
            return false;
        } else if ($paymentNotice['is_paid'] == 1 && $status == PaymentService::CHARGE_SUCCESS) {
            $this->setErr('ERR_CHARGE_DONE');
            return false;
        } else if ($status == PaymentService::CHARGE_FAILURE) {
            $this->setErr('ERR_CHARGE_FAILED');
            return false;
        } else {
            $this->setErr('ERR_CHARGE');
            return false;
        }
        $this->json_data = $result;
        return true;
    }

}
