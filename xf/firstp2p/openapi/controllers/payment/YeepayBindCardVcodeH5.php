<?php

/**
 * 易宝-绑卡-短信验证码页面-H5
 * 
 * @author 郭峰<guofeng3@ucfgroup.com>
 */

namespace openapi\controllers\payment;

use libs\web\Form;
use openapi\controllers\YeepayBaseAction;
use libs\utils\PaymentApi;
use core\service\UserBankcardService;
use core\service\YeepayPaymentService;

/**
 * 易宝-绑卡-短信验证码页面-H5
 * 
 */
class YeepayBindCardVcodeH5 extends YeepayBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'bankName' => array('filter' => 'string'),
            'bankCard' => array('filter' => 'string'),
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
        // 用户身份校验Key
        $userClientKey = isset($data['userClientKey']) ? $data['userClientKey'] : '';
        // 用户ID
        $userId = $userInfo->userId;
        // 银行名称
        $bankName = isset($data['bankName']) && !empty($data['bankName']) ? addslashes($data['bankName']) : $userInfo->bank;
        // 银行卡号
        $bankCard = isset($data['bankCard']) && !empty($data['bankCard']) ? addslashes($data['bankCard']) : '';
        // 用户注册手机号
        $userPhone = strlen($userInfo->mobile) > 0 ? $userInfo->mobile : '';

        // 临时Token
        $this->tpl->assign('asgn', $this->setAsgnToken());
        // 载入绑卡请求后，手机验证码页面
        $this->tpl->assign("returnBtn", $_COOKIE['returnBtn']);
        $this->tpl->assign('bankName', $bankName);
        $this->tpl->assign('bankCard', $bankCard);
        $this->tpl->assign('phone', $userPhone);
        $this->tpl->assign('userClientKey', $userClientKey);
        $this->template = 'openapi/views/payment/yeepay_bindcard_vcode_h5.html';
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
