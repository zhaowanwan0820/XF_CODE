<?php

/**
 * 易宝-充值成功页面-APP
 * 
 * @author 郭峰<guofeng3@ucfgroup.com>
 */

namespace api\controllers\payment;

use libs\web\Form;
use api\controllers\YeepayBaseAction;
use core\dao\PaymentNoticeModel;
use NCFGroup\Common\Library\Zhuge;

/**
 * 易宝-充值成功页面-APP
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
        if (!$this->form->validate())
        {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $userInfo = $this->getUserBaseInfo();
        if (empty($userInfo))
        {
            // 登录token失效，跳到App登录页
            header('Location:' . $this->getAppScheme('native', array('name'=>'login')));
            return false;
        }
        // 检查用户是否已在先锋支付开户
        if ($userInfo['payment_user_id'] <= 0)
        {
            $this->setErr('ERR_MANUAL_REASON', '您尚未开户无法进行充值，请稍后再试');
            return false;
        }

        $data = $this->form->data;
        // 获取支付Token并校验
        $pid = $this->getAsgnToken('openapi_pay_asgn');
        if (empty($pid) || strcmp($data['pid'], $pid) !== 0)
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

        // 用户身份标识
        $userClientKey = $data['userClientKey'];
        // 用户ID
        $userId = $userInfo['id'];
        // 订单ID
        $orderId = $userOrderInfo['orderId'];
        // 充值完成后，要跳转的页面
        $returnSuccessUrl = !empty($userOrderInfo['returnSuccessUrl']) ? $userOrderInfo['returnSuccessUrl'] : $this->getAppScheme('closeall');
        // 获取订单数据
        $paymentNotice = PaymentNoticeModel::instance()->getInfoByUserIdNoticeSn($userId, $orderId);
        if (empty($paymentNotice) || empty($paymentNotice['notice_sn']))
        {
            $this->setErr('ERR_MANUAL_REASON', '充值订单不存在，请重新发起充值');
            return false;
        }
        (new Zhuge(Zhuge::APP_WEB))->event('网信账户_充值成功_app', $paymentNotice['user_id'], ['money'=>$paymentNotice['money']]);
        (new Zhuge(Zhuge::APP_MOBILE))->event('网信账户_充值成功_app', $paymentNotice['user_id'], ['money'=>$paymentNotice['money']]);

        // 载入支付完成后的页面
        $this->tpl->assign('returnSuccessUrl', $returnSuccessUrl); // 充值结束后，前端要跳转的地址;
        $this->tpl->assign('userClientKey', $userClientKey);
        $this->template = $this->getTemplate('yeepay_request_pay');
        return true;
    }
}
