<?php
/**
 * 易宝个人中心充值操作- 确认绑卡
 * @author wangqunqiang<wangqunqiang@ucfgroup.com>
 */

namespace web\controllers\payment;
use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\PaymentModel;
use core\dao\PaymentNoticeModel;
use core\dao\DealOrderModel;
use libs\web\Url;
use libs\utils\PaymentApi;
use libs\utils\Block;
use core\service\YeepayPaymentService;
class YeepayConfirmBind extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form("post");
        $this->form->rules = array(
            'vCode' => array('filter' => 'required', 'msg' => '请填写验证码'),
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
        // 请求易宝绑卡确认接口
        $yeepayPaymentService = new YeepayPaymentService();
        $response = $yeepayPaymentService->confirmBindBankCard($sessionData['userId'], $data['vCode']);

        if(!isset($response['respCode']) || $response['respCode'] !== '00')
        {
            return ajax_return(array('status' =>-1, 'msg' => $response['respMsg']));
        }
        $bankCode = (isset($response['data']['bankcode']) && !empty($response['data']['bankcode'])) ? $response['data']['bankcode'] : '';
        if (!empty($bankCode))
        {
            $bankList = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('common', 'QUICK_BANKLIST');
            isset($bankList[$bankCode]) && !empty($bankList[$bankCode]) && $sessionData['bankName'] = $bankList[$bankCode];
        }
        $sessionData['cardTop'] = $response['data']['cardtop'];
        $sessionData['cardLast'] = $response['data']['cardlast'];
        // 需要脱敏显示的银行卡号
        $sessionData['cardNoDisplay'] = YeepayPaymentService::getFormatBankCard($sessionData['cardTop'], $sessionData['cardLast']);
        $sessionData['bankCode'] = $response['data']['bankcode'];
        \es_session::set('yeepay_order_'.$userId, $sessionData);
        return ajax_return(array('status' => 0, 'msg' => '绑卡成功'));
    }
}
