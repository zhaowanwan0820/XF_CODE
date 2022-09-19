<?php

namespace openapi\controllers\user;

use libs\web\Form;
use core\service\UserService;
use openapi\controllers\AdminProxyBaseAction;

class NcfphCarry extends AdminProxyBaseAction {

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            "user_name" => array("filter" => "string", "option" => array("optional" => true)),
            "page_num"  => array("filter" => "int",    "option" => array("optional" => true)),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", "参数存在错误");
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;
        if (!trim($params['user_name'])) {
            $this->setErr("ERR_PARAMS_ERROR", "缺少必须参数会员名称");
            return false;
        }

        $params = array(
            'user_name' => trim($params['user_name']),
            'p'         => intval($params['page_num'])
        );

        $this->json_data = $this->revokeAdmin($params);
        return true;
    }
}
