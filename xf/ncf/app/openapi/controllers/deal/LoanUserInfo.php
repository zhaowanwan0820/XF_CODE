<?php

namespace openapi\controllers\deal;

use libs\web\Form;
use libs\utils\Logger;
use openapi\conf\Error;
use openapi\controllers\BaseAction;
use core\service\user\UserService;
use core\service\deal\DealService;
use core\service\repay\DealRepayService;
use core\service\account\AccountService;

class LoanUserInfo extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = [
            'repay_id'       => ['filter' => 'required', 'message' => "repay_id is error"],
            'approve_number' => ['filter' => 'required', 'message' => "approve_number is error"],
        ];

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if(!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke(){
        $data = $this->form->data;
        $repayId = (int)$data['repay_id'];
        $approveNumber = (string)$data['approve_number'];

        $dealInfo  = (new DealService())->getDealByApproveNumber($approveNumber);
        $repayInfo = (new DealRepayService())->getInfoById($repayId);

        if ($dealInfo['id'] != $repayInfo['deal_id']) {
            $this->setErr('ERR_PARAMS_ERROR', '参数错误');
            return false;
        }

        $userInfo = UserService::getUserById($repayInfo['user_id']);
        $supervisionUserInfo = AccountService::getAccountMoney($repayInfo['user_id'],$userInfo['user_purpose']);
        $supervisionMoney    = !empty($supervisionUserInfo['money']) ? $supervisionUserInfo['money'] : '0.00';

        $this->json_data   = [
            'repay_id'     => $repayInfo['id'],
            'deal_id'      => $repayInfo['deal_id'],
            'repay_money'  => $repayInfo['repay_money'],
            'user_balance' => $supervisionMoney,
        ];
        return true;
    }

}
