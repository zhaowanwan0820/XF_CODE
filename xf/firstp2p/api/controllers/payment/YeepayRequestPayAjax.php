<?php

/**
 * 易宝-确认充值Ajax接口-APP
 * 
 * @author 郭峰<guofeng3@ucfgroup.com>
 */

namespace api\controllers\payment;

use libs\web\Form;
use api\controllers\YeepayBaseAction;
use core\dao\PaymentNoticeModel;
use core\service\YeepayPaymentService;

/**
 * 易宝-确认充值Ajax接口-APP
 * 
 */
class YeepayRequestPayAjax extends YeepayBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'asgn' => array('filter' => 'required', 'message' => 'asgn is required'),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate())
        {
//            $this->setErr('ERR_PARAMS_ERROR');
 //           return false;
        }
        $asgn = $this->getAsgnToken();
        if ($asgn !== $this->form->data['asgn'])
        {
            $this->setErr('ERR_PARAMS_ERROR', '页面已失效，请刷新后重试');
            return false;
        }
    }

    public function invoke() {
        $userInfo = $this->getUserBaseInfo();
        if (empty($userInfo))
        {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        // 检查用户是否已在先锋支付开户
        if ($userInfo['payment_user_id'] <= 0)
        {
            $this->setErr('ERR_MANUAL_REASON', '您尚未开户无法进行充值，请稍后再试');
            return false;
        }

        // 绑卡成功后，获取redis中的充值订单号、充值金额等
        $userOrderInfo = $this->getUserRedisOrderInfo();
        if (empty($userOrderInfo) || !isset($userOrderInfo['orderId']) || empty($userOrderInfo['orderId']))
        {
            $this->setErr('ERR_MANUAL_REASON', '充值订单不存在，请重新发起充值');
            return false;
        }

        $data = $this->form->data;
        // 用户身份标识
        $userClientKey = $data['userClientKey'];
        // 用户ID
        $userId = $userInfo['id'];
        // 订单ID
        $orderId = $userOrderInfo['orderId'];
        // 获取订单数据
        $paymentNotice = PaymentNoticeModel::instance()->getInfoByUserIdNoticeSn($userId, $orderId);
        if (empty($paymentNotice) || empty($paymentNotice['notice_sn']))
        {
            $this->setErr('ERR_MANUAL_REASON', '充值订单不存在，请重新发起充值');
            return false;
        }
        // 充值金额，单位i元
        $amount = $paymentNotice['money'];
        // 银行卡前6位
        $cardTop = isset($userOrderInfo['cardTop']) ? $userOrderInfo['cardTop'] : '';
        // 银行卡后4位
        $cardLast = isset($userOrderInfo['cardLast']) ? $userOrderInfo['cardLast'] : '';

        $params = array();
        $params['uid']         = $userId;
        $params['orderId']     = $orderId;
        $params['amount']      = $amount;
        $params['productname'] = '易宝充值订单-' . $orderId;
        $params['productdesc'] = '易宝充值订单';
        $params['card_top']    = $cardTop; // 卡号前6位
        $params['card_last']   = $cardLast; // 卡号后4位
        $params['registtime'] = date('Y-m-d H:i:s', ($userInfo['create_time'] + 28800));
        $params['lastloginterminalid'] = 'app';
        $params['terminalid'] = 'app';

        // 调用“4.3 支付接口-不发送短验-支付请求接口”
        $yeepayPaymentService = new YeepayPaymentService();
        $requestPayRet = $yeepayPaymentService->directBindPay($params);
        // 如果返回该订单重复提交，则允许进入充值成功页面(600049:订单重复提交)
        if (!isset($requestPayRet['respCode']) || $requestPayRet['respCode'] !== '00')
        {
            $this->setErr('ERR_MANUAL_REASON', isset($requestPayRet['respMsg']) ? $requestPayRet['respMsg'] : '充值失败，请重试');
            return false;
        }
        // 把[未支付]的订单状态，更新为[待支付]
        $GLOBALS['db']->update('firstp2p_payment_notice', array('is_paid'=>PaymentNoticeModel::IS_PAID_ING, 'update_time'=>get_gmtime()), sprintf('notice_sn=\'%s\' AND user_id=%d AND is_paid=%d', $paymentNotice['notice_sn'], $userId, PaymentNoticeModel::IS_PAID_NO));
        // 充值成功后设置的Token
        $pid = $this->setAsgnToken('openapi_pay_asgn');
        // 生成请求链接
        $this->json_data = array('code'=>1, 'url'=>sprintf('/payment/yeepayRequestPay?pid=%s&userClientKey=%s', $pid, $userClientKey));
        return true;
    }
}