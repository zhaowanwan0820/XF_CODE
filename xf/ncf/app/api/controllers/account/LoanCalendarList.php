<?php
/**
 * 回款计划日历 年月列表
 * @author jinhaidong@ucfgroup.com
 * @date 2016-3-29 16:07:52
 **/

namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\deal\DealLoanRepayCalendarService;

class LoanCalendarList extends AppBaseAction {

    public function init() {
        parent::init();

        $this->form = new Form("post");
        $this->form->rules = array(
            "token" => array("filter"=>"required"),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $user = $this->user;
        $data = $this->form->data;
        $result = DealLoanRepayCalendarService::getDealLoanRepayCalendarList($user['id']);
        $this->json_data = $result;
    }
}
