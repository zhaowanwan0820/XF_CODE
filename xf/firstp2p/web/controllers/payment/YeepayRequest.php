<?php
/**
 * 易宝个人中心充值操作- 请求支付接口
 * @author wangqunqiang<wangqunqiang@ucfgroup.com>
 */

namespace web\controllers\payment;
use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\PaymentNoticeModel;
use core\service\YeepayPaymentService;

class YeepayRequest extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form("post");
        $this->form->rules = array(
        );
        $this->form->validate();
    }


    public function invoke() {
        $data     = $this->form->data;
        $userId = $GLOBALS['user_info']['id'];
        // 收集用户相关数据，通过session读取
        $sessionData = \es_session::get('yeepay_order_'.$userId);
        if (empty($sessionData))
        {
            return ajax_return(array('status' => -1, 'msg' => '订单支付失败，请重新支付'));
        }
        // 请求易宝支付接口
        $yeepayPaymentService = new YeepayPaymentService();
        $directBindPayParams = array(
            'uid' => $userId,
            'orderId' => $sessionData['noticeSn'],
            'amount' => $sessionData['orderAmount'],
            'productname' => '充值订单 '. $sessionData['orderId'],
            'productdesc' => '充值订单',
            'card_top' => $sessionData['cardTop'],
            'card_last' => $sessionData['cardLast'],
            'registtime' => date('Y-m-d H:i:s', ($GLOBALS['user_info']['create_time'] + 28800)),
            'lastloginterminalid' => 'pc',
            'terminalid' => 'pc',
        );
        $response = $yeepayPaymentService->directBindPay($directBindPayParams);

        if(!isset($response['respCode']) || $response['respCode'] !== '00')
        {
            return ajax_return(array('status' =>-1, 'msg' => $response['respMsg']));
        }
        // 把[未支付]的订单状态，更新为[待支付]
        $GLOBALS['db']->update('firstp2p_payment_notice', array('is_paid'=>PaymentNoticeModel::IS_PAID_ING, 'update_time'=>get_gmtime()), sprintf('notice_sn=\'%s\' AND user_id=%d AND is_paid=%d', $sessionData['noticeSn'], $userId, PaymentNoticeModel::IS_PAID_NO));
        // 等待支付回调更新订单状态
        return ajax_return(array('status' => 0));
    }
}