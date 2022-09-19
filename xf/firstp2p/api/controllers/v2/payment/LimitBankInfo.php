<?php
/**
 *  获取快捷银行卡信息 
 */
namespace api\controllers\payment;

use libs\web\Form;
use libs\utils\PaymentApi;
use api\controllers\AppBaseAction;
use \core\service\BankService;
use core\service\SupervisionFinanceService;
use core\service\ncfph\SupervisionService as PhSupervisionService;

class LimitBankInfo extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter'=>"required")
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $userInfo = $this->getUserByToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        // 开关打开，获取新协议支付的限额数据
        $limitOpen = SupervisionFinanceService::isNewBankLimitOpen();
        if ($limitOpen) {
            $limitInfo = PhSupervisionService::getPhChargeLimitH5($userInfo['id']);
            if (!isset($limitInfo['respCode']) || $limitInfo['respCode'] != '00') {
                PaymentApi::log(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $userInfo['id'], sprintf('getPhChargeLimitH5_Failed, userId:%d, limitInfo:%s', $userInfo['id'], json_encode($limitInfo)))));
            }
            $this->json_data = !empty($limitInfo['data']) ? $limitInfo['data'] : null;
            return true;
        }

        $bankInfo = $this->rpc->local('BankService\getFastPayBanks', array());
        if ($bankInfo['status'] != '') {
            throw new \Exception($bankInfo['msg']);
        }
        $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id" => $userInfo['id']));
        $bankList = isset($bankInfo['data']) ? $bankInfo['data'] : array();
        $bankId = isset($bankcard['bank_id']) ? $bankcard['bank_id'] : '';
        $bankLimit = null;
        foreach ($bankList as $item) {
            if ($item['bank_id'] == $bankId) {
                $bankLimit = $item;
                break;
            }
        }
        $this->json_data = $bankLimit;
        return true;
    }
}