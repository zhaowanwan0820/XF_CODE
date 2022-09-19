<?php
/**
 *  提现
 * User: lys
 * Date: 2015/6/15
 * Time: 14:05
 */
namespace openapi\controllers\payment;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestPaymentCashOut;
use core\dao\PaymentNoticeModel;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
class CashOut extends BaseAction {


    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter"=>"required", "message"=>"token不能为空"),
            'money' => array(
                'filter' => 'reg',
                'message' => '金额格式错误，小数点两位',
                'option' => array(
                    'regexp' => '/^\d+(\.\d{1,2})?$/',
                ),
            ),
            "os" => array("filter"=>"int"),
        );
        /*
          * 与父类系统鉴权验证规则合并
          */
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $data = $this->form->data;
        if (empty($data['os'])){
            $data['os'] = PaymentNoticeModel::PLATFORM_H5;
        }
        if (!empty($data['os']) && !in_array($data['os'],array(PaymentNoticeModel::PLATFORM_ANDROID,PaymentNoticeModel::PLATFORM_IOS,PaymentNoticeModel::PLATFORM_H5))){
            $this->setErr('ERR_PARAMS_ERROR','os类型错误');
            return false;
        }
        if(bccomp($data['money'],0.00,2) <= 0){
            $this->setErr('ERR_PARAMS_ERROR','金额错误');
            return false;
        }

        $loginUser = $this->getUserByAccessToken();
        RiskServiceFactory::instance(Risk::BC_WITHDRAW_CASH,Risk::PF_OPEN_API,$this->device)->check($loginUser,Risk::ASYNC,$data);
        if (!$loginUser) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }
        // 检查用户身份认证
        if ($loginUser->idcardPassed == 0){
            $this->setErr("ERR_IDENTITY_NO_VERIFY");
            return false;
        }
        if ($loginUser->idcardPassed == 3){
            $this->setErr("ERR_IDENTITY_VERIFY");
            return false;
        }
        // Protos 返回的用户金额增加了千分位， 不能直接计算
        $userMoney = str_replace(',', '', $loginUser->money);
        // 检查用户提现金额是否小于可用余额
        if (bccomp($userMoney, $data['money'], 2) < 0)
        {
            $this->setErr('ERR_CASHOUT_NOT_ENOUGH_MONEY');
            return false;
        }

        $carryService = new \core\service\UserCarryService();
        $canWithdraw = $carryService->canWithdrawAmount($loginUser->userId, $data['money']);
        if (!$canWithdraw) {
            $this->setErr('ERR_CASHOUT_ERROR', $GLOBALS['lang']['CARRY_LIMIT_ERR']);
            return false;
        }
        $canWithdraw = $carryService->canWithdraw($loginUser->userId, $data['money']);
        if (!$canWithdraw['result']) {
            $this->setErr('ERR_CASHOUT_AMOUNT', $ret['reason']);
            return false;
        }

        $request = new RequestPaymentCashOut();
        $request->setUserId($loginUser->userId);
        $request->setOs(intval($data['os']));
        $request->setMoney((string) $data['money']);
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpPayment',
            'method' => 'cashOut',
            'args' => $request
        ));
        $succ = $response['success'];
        $msg = $response['msg'];
        if($succ != 0){
            $this->setErr("ERR_CASHOUT_ERROR",$msg);
            return false;
        }
        RiskServiceFactory::instance(Risk::BC_WITHDRAW_CASH,Risk::PF_OPEN_API)->notify();
        $this->json_data = $response;
        return true;
    }

}


