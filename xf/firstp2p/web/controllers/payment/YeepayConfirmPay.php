<?php
/**
 * 易宝个人中心充值操作- 确认支付 
 * @author wangqunqiang<wangqunqiang@ucfgroup.com>
 */

namespace web\controllers\payment;
use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\PaymentNoticeModel;
class YeepayConfirmPay extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form("get");
        $this->form->rules = array(
        );
        $this->form->validate();
    }


    public function invoke() {
        $data     = $this->form->data;
        $userId = $GLOBALS['user_info']['id'];
        $sessionData = \es_session::get('yeepay_order_'.$userId);
        if (empty($sessionData['orderId']))
        {
            return $this->show_error('订单不存在，请重新支付','', 0, 0, '/account/charge/');
        }
        $orderInfo = PaymentNoticeModel::instance()->find($sessionData['orderId']);
        if (empty($orderInfo))
        {
            return $this->show_error('订单不存在，请重新支付','', 0, 0, '/account/charge/');
        }
        $sessionData['noticeSn'] = $orderInfo['notice_sn'];
        $sessionData['moneyFormat'] = number_format($orderInfo['money'], 2);
        $sessionData['orderAmount'] = $orderInfo['money'];
        \es_session::set('yeepay_order_'.$userId, $sessionData);
        $this->tpl->assign('orderInfo', $orderInfo);
        $this->tpl->assign('userInfo', $sessionData);
    }
}
