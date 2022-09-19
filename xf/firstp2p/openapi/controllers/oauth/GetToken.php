<?php

/**
 * 
 * @author yutao <yutao@ucfgroup.com>
 * @abstract  openapi 获取token
 * @date       2014-12-1
 */

namespace openapi\controllers\oauth;

use openapi\controllers\BaseAction;
use libs\web\Form;
use NCFGroup\Protos\Ptp\RequestOauth;

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
        /**
         * developer_credentials 认证方式特殊处理
         */
        if ($data['grant_type'] == "developer_credentials") {
            if ($this->clientConf['grant_type'] == "developer_credentials") {
                $request = new RequestOauth();
                try {
                    $request->setClientId($data['client_id']);
                    $request->setGrantType("developer_credentials");
                } catch (\Exception $exc) {
                    $this->errorCode = -99;
                    $this->errorMsg = "param set ERROR";
                    return false;
                }
                $tokenResponse = $GLOBALS['rpc']->callByObject(array(
                    'service' => 'NCFGroup\Ptp\services\PtpUser',
                    'method' => 'getAccessTokenForClient',
                    'args' => $request,
                ));
                if (!empty($tokenResponse) && isset($tokenResponse['access_token'])) {
                    $this->json_data = $tokenResponse;
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

        $request = new RequestOauth();
        try {
            $request->setClientId($data['client_id']);
            $request->setScope($data['scope']);
            $request->setCode($data['code']);
            $request->setRedirectUri($data['redirect_uri']);
            $request->setRefreshToken($data['refresh_token']);
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }
        $tokenResponse = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpUser',
            'method' => 'getAccessToken',
            'args' => $request,
        ));
        if (isset($tokenResponse['errorCode'])) {
            $this->errorCode = $tokenResponse['errorCode'];
            $this->errorMsg = $tokenResponse['errorMsg'];
            return false;
        }
        $this->json_data = $tokenResponse;
        return true;
    }

}
