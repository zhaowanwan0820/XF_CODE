<?php

/**
 * LoanInfo.php
 * Descrition: 获取速贷借款信息
 */

namespace api\controllers\speedloan;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\speedLoan\LoanService;
use libs\utils\ABControl;

use NCFGroup\Protos\Creditloan\Enum\CreditUserEnum;

class LoanInfo extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array("token" => array("filter" => "required", "message" => "token不能为空"));
        $this->form->validate();

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $userInfo = $this->getUserByToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL'); // 获取oauth用户信息失败
            return false;
        }

        $service = new LoanService();
        $creditUserInfo = $service->getUserCreditInfo($userInfo['id']);
        $result = array(
                'speedLoanStatus' => !empty($creditUserInfo) && $creditUserInfo['credit_status'] == CreditUserEnum::CREDIT_STATUS_SUCCESS ? '1' : '0',
                'speedLoanMaxAmount' => number_format(app_conf('SPEED_LOAN_USER_LIMIT_AMOUNT'), 2),
                'speedLoanUrl' => '/speedloan/index',
                'speedLoanSwitch' => empty($creditUserInfo) || $creditUserInfo['credit_status'] == CreditUserEnum::CREDIT_STATUS_SIGNED || $creditUserInfo['credit_status'] == CreditUserEnum::CREDIT_STATUS_PROCESSING ? 0 : app_conf('SPEED_LOAN_SWITCH'),
                'speedLoanUsableAmount' => $creditUserInfo['usableAmountFormat'],
        );
        if (!ABControl::getInstance()->hit('speedLoan'))
        {
            $result['speedLoanSwitch'] = 0;
        }

        $this->json_data = $result;
    }

}
