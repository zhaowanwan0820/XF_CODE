<?php

namespace openapi\controllers\oauth;

use openapi\controllers\BaseAction;
use libs\web\Form;

class GetToken extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "client_id" => array("filter" => "string"),
            "grant_type" => array("filter" => "string"),
            "scope" => array("filter" => "string"),
            "code" => array("filter" => "string"),
            "redirect_uri" => array("filter" => "string"),
            "refresh_token" => array("filter" => "string"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        require_once(APP_ROOT_PATH . 'libs/vendors/oauth2/Server.php');
        $oauth = new \PDOOAuth2();

        //developer_credentials 认证方式特殊处理
        if ($data['grant_type'] == "developer_credentials") {
            if ($this->clientConf['grant_type'] == "developer_credentials") {
                $accessTokenInfo = $oauth->grantAccessTokenForClient($data['client_id']);
                if (!empty($accessTokenInfo) && isset($accessTokenInfo['access_token'])) {
                    $this->json_data = $accessTokenInfo;
                    return true;
                }
                $this->errorCode = -1;
                $this->errorMsg = "token get ERROR";
                return false;
            }
            $this->errorCode = -2;
            $this->errorMsg = "not support the client";
            return false;
        }

        $_POST['client_id'] = $data['client_id'];
        $_POST['scope'] = $data['scope'];
        $_POST['code'] = $data['code'];
        $_POST['redirect_uri'] = $data['redirect_uri'];
        $_POST['refresh_token'] = $data['refresh_token'];

        $accessTokenInfo = $oauth->getAccessToken();
        if (empty($accessTokenInfo)) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }
        $this->json_data = $accessTokenInfo;
        return true;
    }

}
