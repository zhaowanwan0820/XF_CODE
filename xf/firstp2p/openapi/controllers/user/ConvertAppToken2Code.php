<?php

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;

require_once APP_ROOT_PATH . "/libs/vendors/oauth2/Server.php";

class ConvertAppToken2Code extends BaseAction {

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            "app_token" => array("filter" => "required", "message" => "app token为必须字段"),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $appToken = trim($data['app_token']);
        if (empty($appToken)) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }

        $tokenInfo = $this->rpc->local('UserService\getUserByCode', array($appToken));
        if (!empty($tokenInfo['code'])) {
            $this->setErr("ERR_PARAMS_ERROR", $tokenInfo['reason']);
            return false;
        }

        $userInfo = $tokenInfo['user']->getRow();
        if (empty($userInfo)) {
            $this->setErr("ERR_SYSTEM");
            return false;
        }

        $oauth = new \PDOOAuth2(array('user_id' => $userInfo['id']));
        $params = array('client_id' => '7b9bd46617b3f47950687351',  'response_type' => 'code'); //wap 站的client id
        $result = $oauth->finishClientAuthorization(true, $params, false);
        if (empty($result['query'])) {
            $this->setErr("ERR_SYSTEM", "create code error");
            return false;
        }

        $GLOBALS['user_info'] = $userInfo;
        $this->json_data = $result['query'];
        return true;
    }

    public function authCheck() {
        return true;
    }

}
