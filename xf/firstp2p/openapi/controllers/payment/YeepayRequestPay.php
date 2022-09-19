<?php

/**
 * 易宝-充值成功页面-H5
 * 
 * @author 郭峰<guofeng3@ucfgroup.com>
 */

namespace openapi\controllers\payment;

use libs\web\Form;
use openapi\controllers\YeepayBaseAction;
use core\dao\PaymentNoticeModel;
use core\service\YeepayPaymentService;

/**
 * 易宝-充值成功页面-H5
 * 
 */
class YeepayRequestPay extends YeepayBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'pid' => array('filter' => 'required', 'message' => 'pid is required'),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR');
            $this->show_error($this->errorMsg);
        }
    }

    public function invoke() {
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }
        // 检查用户是否已在先锋支付开户
        if ($userInfo->paymentUserId <= 0)
        {
            $this->setErr('ERR_MANUAL_REASON', '您尚未开户无法进行充值，请稍后再试');
            return false;
        }

        // 获取支付Token并校验
        $pid = $this->getAsgnToken('openapi_pay_asgn');
        if (empty($pid) || strcmp($this->form->data['pid'], $pid) !== 0)
        {
            $this->setErr('ERR_MANUAL_REASON', '支付请求号校验失败');
            return false;
        }

        // 绑卡成功后，获取redis中的充值订单号、充值金额等
        $userOrderInfo = $this->getUserRedisOrderInfo();
        if (empty($userOrderInfo) || !isset($userOrderInfo['orderId']) || empty($userOrderInfo['orderId']))
        {
            $this->setErr('ERR_MANUAL_REASON', '充值订单不存在，请重新发起充值');
            return false;
        }

        // 用户ID
        $userId = $userInfo->userId;
        // 订单ID
        $orderId = $userOrderInfo['orderId'];
        // 充值跳转页面
        $returnUrl = isset($userOrderInfo['returnUrl']) ? $userOrderInfo['returnUrl'] : 'http://m.firstp2p.com';
        // 获取订单数据
        $paymentNotice = PaymentNoticeModel::instance()->getInfoByUserIdNoticeSn($userId, $orderId);
        if (empty($paymentNotice) || empty($paymentNotice['notice_sn'])) {
            $this->setErr('ERR_MANUAL_REASON', '充值订单不存在，请重新发起充值');
            return false;
        }

        // 载入支付完成后的页面
        $this->tpl->assign('returnUrl', $returnUrl); // 充值结束后，前端要跳转的地址;
        $this->template = 'openapi/views/payment/yeepay_request_pay_h5.html';
        return true;
    }

    public function _after_invoke() {
        if (!empty($this->template)) {
            $this->tpl->assign('errorCode', $this->errorCode);
            $this->tpl->display($this->template);
            return true;
        }
        parent::_after_invoke();
    }
}
