<?php
/**
 * 个人中心充值操作
 * @author caolong<caolong@ucfgroup.com>
 */
namespace web\controllers\payment;

use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\PaymentNoticeModel;
use core\dao\PaymentModel;
use core\service\PaymentService;
use libs\utils\PaymentApi;
use core\service\UserAccessLogService;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;

class Startpay extends BaseAction {
    // 是否检查未完成充值订单
    const IS_CHECK_CHARGE = 0;

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form("get");
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
            'pd_FrpId'=>array('filter'=>'string'),
            'site'=>array('filter'=>'string'),
        );
        if(!$this->form->validate()) {
            //return app_redirect(url("Bid"));
        }
    }

    public function invoke() {
        if(app_conf('PAYMENT_ENABLE') == '0'){
            return $this->oldInvoke();
        }
        $payment_notice = PaymentNoticeModel::instance()->find($this->form->data['id']);

        if($payment_notice['user_id'] != $GLOBALS['user_info']['id']){
            return $this->show_error("当前访问发生问题，请稍后再试"); 
        }

        //增加支付平台单笔交易查询判断
        $status = $this->rpc->local('PaymentService\chargeStatusByPaymentNoticeNo', array($payment_notice['notice_sn']));

        if($payment_notice['is_paid'] == 0 && $status == PaymentService::ERROR_PAYMENT_ORDER_NOTEXITS)
        {
            //是否可信
            $isCredible = $this->rpc->local('UserCreditService\isCredible', array($GLOBALS['user_info']['id']));

            $torechargeParam['amount'] = $payment_notice['money']*100; //单位为分
            $torechargeParam['curType'] = 'CNY';
            $torechargeParam['userId'] = $GLOBALS ['user_info'] ['payment_user_id'];
            $torechargeParam['outOrderId'] = $payment_notice['notice_sn'];
            $torechargeParam['fastPayFlag'] = $isCredible ? '1' : '0';

            // 生成Form表单
            $payment_code = PaymentApi::instance()->getGateway()->getForm('torecharge', $torechargeParam, 'redirect_form', false);
            if(empty($payment_code)){
               return $this->show_error("当前访问发送问题，请稍后再试"); 
            }

            //生产访问日志
            $device = UserAccessLogService::getPaymentDevice($payment_notice['platform']);
            $extraInfo = [
                'orderId'       => $payment_notice['notice_sn'],
                'chargeAmount'  => (int) bcmul($payment_notice['money'], 100),
                'chargeChannel' => $payment_notice['payment_id'] == PaymentNoticeModel::PAYMENT_UCFPAY ? UserAccessLogEnum::CHARGE_CHANNEL_UCFPAY : UserAccessLogEnum::CHARGE_CHANNEL_YEEPAY,
            ];
            UserAccessLogService::produceLog($payment_notice['user_id'], UserAccessLogEnum::TYPE_CHARGE, sprintf('网信充值申请%s元', (float)$payment_notice['money']), $extraInfo, '', $device, UserAccessLogEnum::STATUS_INIT);


            // 是否检查未完成充值订单
            $this->tpl->assign("isCheckCharge", self::IS_CHECK_CHARGE);
            $this->tpl->assign("payment_code", $payment_code);
            $this->tpl->assign("payment_title", '正在跳转到支付页面');
            $this->tpl->assign("payment_tip", '正在跳转到支付页面，请稍等....');
            $this->tpl->assign("inc_file", "web/views/payment/startpay.html");
            $this->template = "web/views/account/frame.html";
        }
        else if($payment_notice['is_paid'] == 2 || $status == PaymentService::CHARGE_PENDING){
             return $this->show_error('充值处理中','操作无效',0,0,"/account");
        }
        else if($payment_notice['is_paid'] == 1 && $status == PaymentService::CHARGE_SUCCESS){
             return $this->show_error("已充值",'操作无效',0,0,"/account");
        }
        else if($status == PaymentService::CHARGE_FAILURE){
            return $this->show_error("充值失败","操作无效",0,0,"/account");
        }
        else {
            return $this->show_error("当前访问发生问题，请稍后再试"); 
        }
    }

    /**
     * 旧的支付逻辑
     * @return boolean
     */
    public function oldInvoke()
    {
        $payment_notice = PaymentNoticeModel::instance()->find($this->form->data['id']);
        if ($payment_notice) {
            $payment_info = PaymentModel::instance()->find($payment_notice['payment_id']);
            \FP::import("libs.payment." . $payment_info['class_name'] . "_payment");
            $payment_class = $payment_info['class_name'] . "_payment";
            $payment_object = new $payment_class();
            $payment_code = $payment_object->get_payment_code($payment_notice['id'], $this->form->data['pd_FrpId']);
            $this->tpl->assign("payment_code", $payment_code);
            $this->tpl->assign("inc_file", "web/views/payment/startpay.html");
            $this->template = "web/views/account/frame.html";
        }
    }
}
