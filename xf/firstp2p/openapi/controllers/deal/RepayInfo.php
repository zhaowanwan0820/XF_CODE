<?php

namespace openapi\controllers\deal;

use libs\web\Form;
use libs\utils\Logger;
use openapi\conf\Error;
use core\service\DealService;
use core\service\DealRepayService;
use openapi\controllers\BaseAction;

class RepayInfo extends BaseAction {

    public function init(){
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'approve_number' => ['filter' => 'required', 'message' => "approve_number is error"],
            'repay_id'       => ['filter' => 'required', 'message' => "repay_id is error"],
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if(!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke(){
        $data          = $this->form->data;
        $repayId       = (int)$data['repay_id'];
        $approveNumber = (string)$data['approve_number'];

        $dealInfo  = (new DealService())->getDealByApproveNumber($approveNumber);
        $repayInfo = (new DealRepayService())->getInfoById($repayId);

        if ($dealInfo['id'] != $repayInfo['deal_id']) {
            $this->setErr('ERR_PARAMS_ERROR', '参数错误');
            return false;
        }

        $this->json_data = [
            'status' => $repayInfo['status'],
            'account_type' => $repayInfo['repay_type'],
        ];
        return true;
    }

}

