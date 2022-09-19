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
        if(!$this->check_login()) return false;
        $this->form = new Form("post");
        $this->form->rules = array(
            'paymentMethod' => array('filter' => 'string', 'option' => array('optional' => true)),
            "money" => array("filter"=>"float"),
            "pd_FrpId" => array("filter"=>"string"),
        );
        $this->form->validate();
    }

    public function invoke() {

        if(app_conf('PAYMENT_ENABLE') == '0'){
            return $this->oldInvoke();
        }
        if (empty($this->form->data['paymentMethod']))
        {
            $this->form->data['paymentMethod'] = 'ucfpay';
        }

        // 获取当前可用的支付方式
        $paymentChannelList = PaymentApi::getPaymentChannel();
        // 没有可用的支付方式
        if (empty($paymentChannelList))
        {
            return $this->show_error('暂无可用的支付渠道', '', 0, 0, '/account');
        }

        // 没有使用V3模版的分站，禁用PC端充值(JIRA#3632)
        if (\libs\utils\Site::getId() != 1 && !empty($GLOBALS['sys_config']['FENZHAN_NOT_OPEN']) && in_array(get_host(false), $GLOBALS['sys_config']['FENZHAN_NOT_OPEN']))
        {
            return $this->show_error('请使用手机APP进行充值', '', 0, 0, '/account');
        }

        // 支付方式
        $paymentMethod = isset($this->form->data['paymentMethod']) && !empty($this->form->data['paymentMethod']) ? addslashes($this->form->data['paymentMethod']) : PaymentApi::PAYMENT_SERVICE_UCFPAY;
        // 先锋支付降级判断，降级时，先锋支付不能提供充值服务
        if ($paymentMethod === PaymentApi::PAYMENT_SERVICE_UCFPAY && PaymentApi::isServiceDown())
        {
            return $this->show_error(PaymentApi::maintainMessage(), '', 0, 0, '/account');
        }

        // 易宝支付关闭时，不能提供充值服务
        if ($paymentMethod === PaymentApi::PAYMENT_SERVICE_YEEPAY && (!isset($paymentChannelList[$paymentMethod]) || empty($paymentChannelList[$paymentMethod])))
        {
            $yeepayCloseTips = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('common', 'CREATE_ORDER_TIPS');
            $yeepayCloseTips = !empty($yeepayCloseTips) ? $yeepayCloseTips : PaymentApi::maintainMessage();
            return $this->show_error($yeepayCloseTips, '', 0, 0, '/account');
        }

        // 易宝支付不支持企业用户
        $userService = new UserService($GLOBALS['user_info']['id']);
        $isEnterprise = $userService->isEnterprise();
        if($isEnterprise && (isset($this->form->data['paymentMethod']) && strcmp($this->form->data['paymentMethod'], PaymentApi::PAYMENT_SERVICE_YEEPAY) == 0))
        {
            return $this->show_error('暂不支持企业账户充值', '', 0, 0, '/account');
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
            // 先锋支付降级判断
            if (!PaymentApi::isServiceDown())
            {
                if(empty($GLOBALS['user_info']['payment_user_id'])){
                    return $this->show_error('无法充值', '', 0, 0, '/account');
                }
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
        //业务日志参数
        $this->businessLog['busi_name'] = '充值';
        $this->businessLog['money'] = $money;
        $this->businessLog['busi_id'] = $noticeId;
        RiskServiceFactory::instance(Risk::BC_CHARGE)->check(array('id'=>$GLOBALS ['user_info']['id'],'user_name'=>$GLOBALS ['user_info']['user_name'],'money'=>$money),Risk::ASYNC);
        // 区分支付方式， 进入不同的支付阶段
        switch ($this->form->data['paymentMethod'])
        {
            case 'yeepay':
                \es_session::set('yeepay_order_'.$userId, $sessionData);
                return app_redirect(Url::gene('payment', 'yeepay', array('id' => $noticeId)));
                break;
            case 'ucfpay':
                if (!PaymentApi::isServiceDown())
                {
                    return app_redirect(Url::gene('payment', 'pay',array('id' => $noticeId, 'pd_FrpId' =>$pd_FrpId)));
                }
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

        if (app_conf('PAYMENT_USE_XFJR') == 1) {
            $bank_charge = $this->rpc->local("BankService\getBankCharge", array($bank_id));
            $arr_banklist1 = explode(",", trim(app_conf('PAYMENT_XFJR_BANK'), ","));
            $arr_banklist2 = explode(",", trim(app_conf('XFJR_ALLCARDTYPE_BANK'), ","));
            $arr_banklist = array_merge($arr_banklist1, $arr_banklist2);
            if (in_array($bank_charge['short_name'], $arr_banklist)) {
                $payment_id = 4; // 先锋支付ID
                $pd_FrpId = $bank_charge['short_name'];
                $bank_short[0] = $bank_charge['short_name'];
            }
        }
        $result = $this->rpc->local("DealOrderService\createDealOrder", array($payment_id, $money, $bank_short[0], PaymentNoticeModel::PLATFORM_WEB));
        if (intval($result['code']) === 0) {
            return app_redirect(Url::gene('payment', 'pay', array("id" => $result['result']['payment_notice_id'], 'pd_FrpId' => $pd_FrpId)));
        } else {
            return $this->show_success("恭喜，支付成功", '', 0, 0, APP_ROOT . '/account');
        }
    }
}
