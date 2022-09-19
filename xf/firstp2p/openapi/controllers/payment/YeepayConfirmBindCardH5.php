<?php

/**
 * 易宝-绑卡确认页面-H5
 * 
 * @author 郭峰<guofeng3@ucfgroup.com>
 */

namespace openapi\controllers\payment;

use libs\web\Form;
use openapi\controllers\YeepayBaseAction;
use libs\utils\PaymentApi;
use core\service\YeepayPaymentService;

/**
 * 易宝-绑卡确认页面-H5
 * 
 */
class YeepayConfirmBindCardH5 extends YeepayBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'rid' => array('filter' => 'required', 'message' => 'rid is required'),
        );

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

        $data = $this->form->data;
        if (empty($data['rid']) || !is_numeric($data['rid']))
        {
            $this->setErr('ERR_MANUAL_REASON', '绑卡请求号不能为空或格式不正确');
            return false;
        }
        // 用户ID
        $userId = $userInfo->userId;
        // 获取绑卡请求号并校验
        $requestId = \es_session::get(sprintf('%s%d', YeepayPaymentService::KEY_YEEPAY_BINDCARD, $userId));
        if (empty($requestId) || strcmp($data['rid'], $requestId) !== 0)
        {
            $this->setErr('ERR_MANUAL_REASON', '绑卡请求号校验失败');
            return false;
        }

        // 用户身份校验Key
        $userClientKey = isset($data['userClientKey']) ? $data['userClientKey'] : '';
        // 绑卡成功后，获取redis中的充值订单号、充值金额等
        $userOrderInfo = $this->getUserRedisOrderInfo();
        // 银行名称
        $bankNameCache = isset($userOrderInfo['bankName']) ? $userOrderInfo['bankName'] : '';
        // 银行编码
        $bankCode = isset($userOrderInfo['bankCode']) ? $userOrderInfo['bankCode'] : '';
        // 银行名称为空时的处理
        if (empty($bankNameCache) && !empty($bankCode))
        {
            // 易宝支持的16家银行列表
            $quickBankList = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('common', 'QUICK_BANKLIST');
            if (isset($quickBankList[$bankCode]) && !empty($quickBankList[$bankCode]))
            {
                $bankName = $quickBankList[$bankCode];
            }
        }else{
            $bankName = $bankNameCache;
        }
        // 银行卡前6位
        $cardTop = isset($userOrderInfo['cardTop']) ? $userOrderInfo['cardTop'] : '';
        // 银行卡后4位
        $cardLast = isset($userOrderInfo['cardLast']) ? $userOrderInfo['cardLast'] : '';
        // 生成脱敏卡号
        $bankCard = YeepayPaymentService::getFormatBankCard($cardTop, $cardLast);
        // 充值金额，单位元
        $amountYuan = (isset($userOrderInfo['amountFen']) && !empty($userOrderInfo['amountFen'])) ? bcdiv($userOrderInfo['amountFen'], 100, 2) : 0;

        // 临时Token
        $this->tpl->assign('asgn', $this->setAsgnToken());
        // 绑卡成功后，确认支付页面
        $this->tpl->assign("returnBtn", $_COOKIE['returnBtn']);
        $this->tpl->assign('amount', $amountYuan); // 充值金额
        $this->tpl->assign('bankName', $bankName); // 银行名称
        $this->tpl->assign('bankCard', $bankCard); // 银行卡号
        $this->tpl->assign('returnUrl', isset($userOrderInfo['returnUrl']) ? $userOrderInfo['returnUrl'] : '');
        $this->tpl->assign('userClientKey', $userClientKey);
        $this->template = 'openapi/views/payment/yeepay_confirm_bindcard_h5.html';
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
