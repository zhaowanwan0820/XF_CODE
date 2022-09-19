<?php

/**
 * Privileges.php
 *
 * @date 2014-03-28
 * @author wangqunqiang <wangqunqiang@ucfgroup.com>
 */

namespace api\controllers\account;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\account\AccountAuthService;

/**
 * 用户授权管理页面 h5
 */
class Privileges extends AppBaseAction {

    protected $redirectWapUrl = '/account/privileges';

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "ERR_GET_USER_FAIL"),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;
        $user = $this->user;

        // 读取用户授权信息
        $userAuthorization = AccountAuthService::getAuthList($user['id'], true);
        $this->json_data = array(
            'privileges' => $userAuthorization,
            'isSvOpen' => $user['supervision_user_id'] > 0 ? 1 : 0
        );
    }

}
