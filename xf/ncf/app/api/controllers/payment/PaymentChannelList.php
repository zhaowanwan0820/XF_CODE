<?php

/**
 * 获取当前可用的支付方式列表-接口-APP
 *
 * @author 郭峰<guofeng3@ucfgroup.com>
 */

namespace api\controllers\payment;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\PaymentApi;
use core\service\user\BankService;

/**
 *
 * 获取当前可用的支付方式列表
 * @package api\controllers\payment
 */
class PaymentChannelList extends AppBaseAction {

    const STATUS_BINDED = 1;    // 已绑卡

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'os' => array('filter' => 'string', 'option' => array('optional' => true)),
            'ver' => array('filter' => 'string', 'option' => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->user;
        // 是否强制切换支付B计划
        $isForceChangePlanB = false;

        // 获取用户绑卡信息
        $userBankCardInfo = BankService::getNewCardByUserId($loginUser['id'], '*');
        if (!empty($userBankCardInfo) && $userBankCardInfo['status'] == self::STATUS_BINDED) {
            // 获取先锋支付需要更换支付渠道的银行列表
            $changeChannelBankListString = app_conf('XFZF_CHANGECHANNEL_BANKLIST');
            $changeChannelBankList = explode(',', $changeChannelBankListString);
            if (!empty($changeChannelBankList)) {
                foreach ($changeChannelBankList as $bankId) {
                    if (!empty($bankId) && intval($bankId) === intval($userBankCardInfo['bank_id'])) {
                        $isForceChangePlanB = true;
                        break;
                    }
                }
            }
        }
        $paymentChannelList = array();
        // 获取当前可用的支付方式
        $paymentChannelListConfig = PaymentApi::getPaymentChannel();
        if (!empty($paymentChannelListConfig))
        {
            // 2018-01-04 屏蔽app 普惠app中的易宝充值功能
            if ($isForceChangePlanB && 100 != \libs\utils\Site::getId()) {
                $paymentChannelList[] = array('paymentMethod'=>PaymentApi::PAYMENT_SERVICE_YEEPAY, 'paymentName'=>'易宝支付', 'paymentH5ForAPP'=>'/payment/start', 'paymentToast'=>'');
            }else{
                $paymentChannelCount = count($paymentChannelListConfig);
                foreach ($paymentChannelListConfig as $paymentMethod => $paymentName)
                {
                    // 点击进入支付方式前，toast提示消息
                    $paymentH5ForAPP = $paymentToast = '';
                    if ($paymentMethod === PaymentApi::PAYMENT_SERVICE_YEEPAY)
                    {
                        // 点击支付方式后，需要跳转的地址
                        $paymentH5ForAPP = '/payment/start';
                        $paymentToast = $paymentChannelCount == 1 ? PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_UCFPAY)->getGateway()->getConfig('common', 'CREATE_ORDER_TIPS') : '';
                    }
                    $paymentChannelList[] = array('paymentMethod'=>$paymentMethod, 'paymentName'=>$paymentName, 'paymentH5ForAPP'=>$paymentH5ForAPP, 'paymentToast'=>$paymentToast);
                }
            }
        }
        $this->json_data = array('paymentList'=>$paymentChannelList);
        return true;
    }
}
