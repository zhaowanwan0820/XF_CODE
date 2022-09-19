<?php
/**
 * 易宝个人中心充值操作- 在易宝设置充值卡
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
use core\service\YeepayPaymentService;
class YeepayBindCard extends BaseAction {

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
        $sessionData = \es_session::get('yeepay_order_'.$userId);
        // 用户id
        $sessionData['userId'] = $GLOBALS['user_info']['id'];
        $sessionData['idno'] = $GLOBALS['user_info']['idno'];
        $sessionData['realName'] = $GLOBALS['user_info']['real_name'];
        // 收集用户相关数据，通过session读取
        $sessionData['mobile'] = $GLOBALS['user_info']['mobile'];
        // 银行卡数据信息
        $bankcard = \core\dao\UserBankcardModel::instance()->findBy(sprintf("user_id = '%d'", $userId));
        $sessionData['cardNo'] = $bankcard['bankcard'];
        $bankInfo = \core\dao\BankModel::instance()->find($bankcard['bank_id']);
        $sessionData['bankName'] = $bankInfo['name'];
        $sessionData['cardTop'] = substr($bankcard['bankcard'], 0, 6);
        $sessionData['cardLast'] = substr($bankcard['bankcard'], strlen($bankcard['bankcard']) - 4, 4);
        // 需要脱敏显示的银行卡号
        $sessionData['cardNoDisplay'] = YeepayPaymentService::getFormatBankCard($sessionData['cardTop'], $sessionData['cardLast']);
        $bankList = $bankListOptions = '';
        if (YeepayPaymentService::isInBankListByCode($bankInfo['short_name']))
        {
            // 用户银行卡在易宝支持范围内，展示不可修改界面
            $lockFields = $sessionData['lockFields'] = true;
        }
        else
        {
            // 用户银行卡不在易宝支持的范围内， 展示可修改界面
            $lockFields = $sessionData['lockFields'] = false;
            $bankList = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('common', 'QUICK_BANKLIST');
            foreach ($bankList as $bankCode => $bankName)
            {
                $bankListOptions .= "<li data-name='{$bankName}'>{$bankName}</li>";
            }
        }
        \es_session::set('yeepay_order_'.$userId, $sessionData);
        $this->tpl->assign('lockFields', $lockFields);
        $this->tpl->assign('userInfo', $sessionData);
        $this->tpl->assign('bankList', $bankListOptions);
    }
}
