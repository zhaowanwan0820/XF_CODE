<?php

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\AdminProxyBaseAction;

class ChangeBankcard extends AdminProxyBaseAction {

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            "mobile" => array("filter" => "reg", "message" => 'ERR_SIGNUP_PARAM_PHONE', "option" => array("regexp" => "/^1[3456789]\d{9}$/", "optional" => true)),
            "status" => array("filter" => "int", "message" => 'ERR_PARAMS_ERROR', "option" => array("optional" => true)),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", "用户传入参数存在错误");
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;
        if (!trim($params['mobile'])) {
            $this->setErr("ERR_PARAMS_ERROR", "缺少必须参数手机号");
            return false;
        }

        $params = array(
            'mobile'    => trim($params['mobile']),
            'p'         => intval($params['page_num']),
            'status'    => intval($params['status'])
        );

        $this->json_data = $this->revokeAdmin($params);
        return true;
    }
}
