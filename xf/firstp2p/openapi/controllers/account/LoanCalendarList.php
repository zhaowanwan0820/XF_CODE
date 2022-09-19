<?php
/**
 * 回款计划日历 年月列表
 * @author zhaohui3@ucfgroup.com
 * @date 2016-7-20
 **/

namespace openapi\controllers\account;

use libs\web\Form;
use openapi\controllers\BaseAction;

class LoanCalendarList extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
                "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $userResponse = $this->getUserByAccessToken();
        if (empty($userResponse)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $data = $this->form->data;
        $uid = $userResponse->getUserId();
        $result = $this->rpc->local('DealLoanRepayCalendarService\getDealLoanRepayCalendarList', array($uid,'openapi'));
        $this->json_data = $result;
    }
}
