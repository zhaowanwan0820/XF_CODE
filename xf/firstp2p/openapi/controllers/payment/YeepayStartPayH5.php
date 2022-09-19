<?php

/**
 * 易宝-绑卡页面/充值页面-H5
 * 
 * @author 郭峰<guofeng3@ucfgroup.com>
 */

namespace openapi\controllers\payment;

use openapi\controllers\YeepayBaseAction;
use libs\web\Form;
use libs\web\Url;
use libs\utils\PaymentApi;
use core\service\YeepayPaymentService;

/**
 * 易宝-绑卡页面/充值页面-H5
 * 
 */
class YeepayStartPayH5 extends YeepayBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array();

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR');
            return false;
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

        // 用户ID
        $userId = $userInfo->userId;
        $userClientKey = isset($this->form->data['userClientKey']) ? $this->form->data['userClientKey'] : '';
        $returnUrl = isset($this->form->data['returnUrl']) ? $this->form->data['returnUrl'] : '';
        // 获取绑卡信息列表
        $yeepayPaymentService = new YeepayPaymentService();
        $cardBindList = $yeepayPaymentService->bankCardAuthBindList($userId);
        if (!isset($cardBindList['respCode']) || $cardBindList['respCode'] !== '00')
        {
            $this->setErr('ERR_MANUAL_REASON', $cardBindList['respMsg']);
            return false;
        }
        $this->tpl->assign("returnBtn", $_COOKIE['returnBtn']);

        // 用户尚未绑卡
        if (!isset($cardBindList['data']['cardlist']) || empty($cardBindList['data']['cardlist']))
        {
            // 银行名称
            $bankName = strlen($userInfo->bank) > 0 ? $userInfo->bank : '';
            // 银行卡号
            $bankCard = strlen($userInfo->bankNo) > 0 && $userInfo->bankNo !== '无' ? $userInfo->bankNo : '';
            // 银行简码
            $bankCode = !empty($userInfo->bankCode) ? $userInfo->bankCode : '';

            // 需要先验卡再充值
            $quickBankList = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('common', 'QUICK_BANKLIST');
            if (YeepayPaymentService::isInBankListByCode($bankCode) && !empty($bankCard))
            {
                // 先锋支付绑的卡，易宝支持
                $isAccord = 1;
            }else{
                $bankName = $bankCard = '';
                // 先锋支付绑的卡，易宝不支持
                $isAccord = 0;
            }
            // 用户银行卡是否在易宝支付的银行列表中存入redis，存到redis哨兵
            $redis = YeepayPaymentService::getRedisSentinels();
            if ($redis)
            {
                $cacheKey = sprintf(YeepayPaymentService::CACHEKEY_YEEPAY_ORDER_API, $userClientKey);
                $redis->hSet($cacheKey, 'isAccord', $isAccord);
            }
            // 易宝支持的16家银行列表
            $bankSelectList = !empty($quickBankList) ? array_values($quickBankList) : array();
            $this->tpl->assign('isAccord', $isAccord);
            $this->tpl->assign('realName', $userInfo->realName);
            $this->tpl->assign('idno', idnoFormat($userInfo->idno));
            $this->tpl->assign('bankName', $bankName);
            $this->tpl->assign('bankCard', $bankCard);
            $this->tpl->assign('bankList', json_encode($bankSelectList));
            $this->tpl->assign('userClientKey', $userClientKey);
            // 载入验卡页面
            $this->template = 'openapi/views/payment/yeepay_bindcard_h5.html';
        } else {
            // 获取用户缓存中的订单信息
            $userOrderInfo = $this->getUserRedisOrderInfo();
            // 用户在易宝的绑卡信息，载入充值页面(获取绑定的最后一张银行卡)
            $cardListOne = array_pop($cardBindList['data']['cardlist']);
            // 充值金额，单位元
            $amountYuan = (isset($userOrderInfo['amountFen']) && !empty($userOrderInfo['amountFen'])) ? bcdiv($userOrderInfo['amountFen'], 100, 2) : 0;
            // 生成脱敏卡号
            $bankCard = YeepayPaymentService::getFormatBankCard($cardListOne['cardtop'], $cardListOne['cardlast']);
            $redis = YeepayPaymentService::getRedisSentinels();
            $yeepayService = new YeepayPaymentService();
            if ($redis)
            {
                $cacheData = array(
                    'cardTop' => $cardListOne['cardtop'],
                    'cardLast' => $cardListOne['cardlast'],
                    'bankName' => $yeepayService->getBankNameByCode($cardListOne['bankcode']),
                );
                $cacheKey = sprintf(YeepayPaymentService::CACHEKEY_YEEPAY_ORDER_API, $userClientKey);
                $redis->hMset($cacheKey, $cacheData);
            }

            // 临时Token
            $this->tpl->assign('asgn', $this->setAsgnToken());
            // 易宝-确认充值页面
            $this->tpl->assign('amount', $amountYuan);
            $this->tpl->assign('bankName', $yeepayService->getBankNameByCode($cardListOne['bankcode']));
            $this->tpl->assign('bankCard', $bankCard);
            $this->tpl->assign('returnUrl', isset($userOrderInfo['returnUrl']) ? $userOrderInfo['returnUrl'] : '');
            $this->tpl->assign('userClientKey', $userClientKey);
            // 载入充值页面
            $this->template = 'openapi/views/payment/yeepay_start_pay_h5.html';
        }
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