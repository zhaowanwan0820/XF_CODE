<?php

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\AdminProxyBaseAction;

class ChangeBankcardDetail extends AdminProxyBaseAction {

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            "id" => array("filter" => "int", "message" => 'ERR_PARAMS_ERROR', "option" => array("optional" => true)),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", "用户传入参数存在错误");
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;
        if (!intval($params['id'])) {
            $this->setErr("ERR_PARAMS_ERROR", "缺少必须参数申请ID");
            return false;
        }

        $params = array(
            'id'        => trim($params['id']),
        );

        $this->json_data = $this->revokeAdmin($params);
        return true;
    }
}
