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
use core\service\AccountAuthorizationService;
use core\dao\AccountAuthorizationModel;

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
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        // 读取用户授权信息
        $authorization = new \core\service\AccountAuthorizationService();
        $userAuthorization = $authorization->getAuthList($user['id'], true);

        $this->json_data = $userAuthorization;
    }
}
