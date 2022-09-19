<?php

/**
 * PrivilegesQuery.php
 * 授权信息查询
 * @date  2018-01-03 18:56:00
 * @author wangqunqiang <wangqunqiang@ucfgroup.com>
 */

namespace api\controllers\account;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\account\AccountAuthService;

/**
 * 用户授权信息查询
 */
class PrivilegesQuery extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
        }
    }

    public function invoke() {
        $params = $this->form->data;
        $user = $this->user;

        // 读取用户授权信息
        $userAuthorization = AccountAuthService::getAuthList($user['id'], true);
        $this->json_data = $userAuthorization;
    }
}
