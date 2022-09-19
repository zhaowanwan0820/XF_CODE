<?php
/**
 * 易宝个人中心充值操作
 * @author wangqunqiang<wangqunqiang@ucfgroup.com>
 */

namespace web\controllers\payment;
use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\PaymentNoticeModel;
use libs\web\Url;
use core\service\PaymentUserAccountService;
use core\service\YeepayPaymentService;

class Yeepay extends BaseAction {


    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form("get");
        $this->form->rules = array(
            'id' => array('filter' => 'string'),
        );
        $this->form->validate();
    }


    public function invoke() {
        $data     = $this->form->data;
        $userId = $GLOBALS['user_info']['id'];
        // 初始化订单数据
        $sessionData = \es_session::get('yeepay_order_'.$userId);
        $sessionData['orderId'] = $data['id'];
        $orderInfo = PaymentNoticeModel::instance()->find($data['id']);
        if (empty($orderInfo))
        {
            return $this->show_error('创建订单失败，请重新提交充值订单');
        }
        $sessionData['noticeSn'] = $orderInfo['notice_sn'];
        $sessionData['orderAmount'] = $orderInfo['money'];
        $sessionData['moneyFormat'] = bcadd($orderInfo['money'],'0.00', 2);
        $yeepayPaymentService = new YeepayPaymentService();
        // 检测用户是否在易宝设置银行卡
        $i = 0;
        do {
            $cardBindList = $yeepayPaymentService->bankCardAuthBindList($userId);
        } while (empty($cardBindList) && $i++ < 3);
        if(!isset($cardBindList['respCode']) || $cardBindList['respCode'] !== '00')
        {
            $msg = $i === 3 ? '网络异常，请稍后再试！' : $cardBindList['respMsg'];
            return $this->show_error($msg);
        }
        // 如果用户没有设定支付银行卡，跳转到支付银行卡界面进行银行卡验证
        if (empty($cardBindList['data']['cardlist']))
        {
           return app_redirect(Url::gene('payment', 'yeepayBindCard'));
        }
        // 如果用户设置支付银行卡， 则默认取出第一张卡数据
        $cardinfo = array_pop($cardBindList['data']['cardlist']);
        $sessionData['bankName'] = $yeepayPaymentService->getBankNameByCode($cardinfo['bankcode']);
        $sessionData['cardFormat'] = YeepayPaymentService::getFormatBankCard($cardinfo['cardtop'], $cardinfo['cardlast']);
        $sessionData['cardTop'] = $cardinfo['cardtop'];
        $sessionData['cardLast'] = $cardinfo['cardlast'];
        \es_session::set('yeepay_order_'.$userId, $sessionData);
        $this->tpl->assign('userInfo', $sessionData);

        // 获取易宝渠道的本地充值限额
        $paymentAccountobj = new PaymentUserAccountService();
        $limitInfo = $paymentAccountobj->getNewChargeLimit($userId, PaymentNoticeModel::CHARGE_YEEPAY_CHANNEL);
        $this->tpl->assign('limitInfo', $limitInfo);
    }
}