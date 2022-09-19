<?php
/**
 * 个人中心充值操作
 * @author wangyiming<wangyiming@ucfgroup.com>
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\web\Url;
use core\dao\PaymentNoticeModel;
use core\service\PaymentService;
use core\service\UserService;
use libs\utils\PaymentApi;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
class Docharge extends BaseAction {

    public function init() {
        //if(!$this->check_login()) return false;
        $this->form = new Form("post");
        $this->form->rules = array(
            'paymentMethod' => array('filter' => 'string', 'option' => array('optional' => true)),
            "money" => array("filter"=>"float"),
            "pd_FrpId" => array("filter"=>"string"),
        );
        $this->form->validate();
    }

    public function invoke() {

        // 没有使用V3模版的分站，禁用PC端充值(JIRA#3632)
        if (\libs\utils\Site::getId() != 1 && !empty($GLOBALS['sys_config']['FENZHAN_NOT_OPEN']) && in_array(get_host(false), $GLOBALS['sys_config']['FENZHAN_NOT_OPEN']))
        {
            return $this->show_error('请使用手机APP进行充值', '', 0, 0, '/account');
        }


        $money = $this->form->data['money'];
        $pd_FrpId = $this->form->data['pd_FrpId'];
        //如果未绑定手机
        $checkBindCardRet = $userService->isBindBankCard();
        if (false == $checkBindCardRet['ret'] && ($checkBindCardRet['respCode'] == UserService::STATUS_BINDCARD_IDCARD || $checkBindCardRet['respCode'] == UserService::STATUS_BINDCARD_MOBILE))
        {
            return $this->show_error('请先填写身份证信息', '', 0, 0, '/account/addbank');
        }

        //非企业用户增加支付平台开户check
        if ( ! $isEnterprise && false == $checkBindCardRet['ret'] && $checkBindCardRet['respCode'] == UserService::STATUS_BINDCARD_PAYMENTUSERID)
        {
            if(empty($GLOBALS['user_info']['payment_user_id'])){
                return $this->show_error('无法充值', '', 0, 0, '/account');
            }
        }
        // 验证表单令牌
        if(!check_token()) {
            return $this->show_error($GLOBALS['lang']['TOKEN_ERR']);
        }

        if ($money < 0.01) {
            return $this->show_error($GLOBALS['lang']['PLEASE_INPUT_CORRECT_INCHARGE']);
        }
        $paymentObject = PaymentApi::instance($this->form->data['paymentMethod']);
        $paymentId = $paymentObject->getGateway()->getConfig('common', 'PAYMENT_ID');
        $noticeId = $this->rpc->local('ChargeService\createOrder', array($GLOBALS['user_info']['id'], $money, PaymentNoticeModel::PLATFORM_WEB, '', $paymentId));
        $userId = $GLOBALS['user_info']['id'];
        if (empty($noticeId)) {
            return $this->show_error('充值失败，请重新充值');
        }
        $sessionData = \es_session::get('yeepay_order_'.$userId);
        if (empty($sessionData))
        {
            $sessionData = array();
        }
        $sessionData['money'] = $money;
        $sessionData['orderId'] = $noticeId;
        RiskServiceFactory::instance(Risk::BC_CHARGE)->check(array('id'=>$GLOBALS ['user_info']['id'],'user_name'=>$GLOBALS ['user_info']['user_name'],'money'=>$money),Risk::ASYNC);
        // 区分支付方式， 进入不同的支付阶段
        switch ($this->form->data['paymentMethod'])
        {
            case 'yeepay':
                \es_session::set('yeepay_order_'.$userId, $sessionData);
                return app_redirect(Url::gene('payment', 'yeepay', array('id' => $noticeId)));
                break;
            case 'ucfpay':
                return app_redirect(Url::gene('payment', 'pay',array('id' => $noticeId, 'pd_FrpId' =>$pd_FrpId)));
            default:
                return app_redirect(Url::gene('account', 'charge'));
        }
    }

    /**
     * 旧的支付逻辑
     * @return boolean
     */
    public function oldInvoke()
    {
        $money = $this->form->data['money'];
        $payment_id = $this->form->data['payment'];
        $pd_FrpId = trim($this->form->data['pd_FrpId']);
// 验证表单令牌
        if (!check_token()) {
            return $this->show_error($GLOBALS['lang']['TOKEN_ERR']);
        }

        if ($money < 0.01) {
            return $this->show_error($GLOBALS['lang']['PLEASE_INPUT_CORRECT_INCHARGE']);
        }
        $bank_id = addslashes(htmlspecialchars($pd_FrpId));
        $bank_short = explode('-', $bank_id);

        $bank_charge = $this->rpc->local("BankService\getBankCharge", array($bank_id));
        $arr_banklist1 = explode(",", trim(app_conf('PAYMENT_XFJR_BANK'), ","));
        $arr_banklist2 = explode(",", trim(app_conf('XFJR_ALLCARDTYPE_BANK'), ","));
        $arr_banklist = array_merge($arr_banklist1, $arr_banklist2);
        if (in_array($bank_charge['short_name'], $arr_banklist)) {
            $payment_id = 4; // 先锋支付ID
            $pd_FrpId = $bank_charge['short_name'];
            $bank_short[0] = $bank_charge['short_name'];
        }
        $result = $this->rpc->local("DealOrderService\createDealOrder", array($payment_id, $money, $bank_short[0], PaymentNoticeModel::PLATFORM_WEB));
        if (intval($result['code']) === 0) {
            return app_redirect(Url::gene('payment', 'pay', array("id" => $result['result']['payment_notice_id'], 'pd_FrpId' => $pd_FrpId)));
        } else {
            return $this->show_success("恭喜，支付成功", '', 0, 0, APP_ROOT . '/account');
        }
    }
}
