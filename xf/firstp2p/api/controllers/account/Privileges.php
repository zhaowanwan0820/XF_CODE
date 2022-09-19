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
use core\service\AccountAuthorizationService;

/**
 * 用户授权管理页面 h5
 */
class Privileges extends AppBaseAction {
    const IS_H5 = true;

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
        $authorization = new AccountAuthorizationService();
        $userAuthorization = $authorization->getAuthList($user['id'], true);
        $this->tpl->assign('privileges', $userAuthorization);
        $this->tpl->assign('token', $params['token']);
        $this->tpl->assign('isSvOpen', $user['supervision_user_id'] > 0 ? 1 : 0);
        $this->template = 'account/privileges.html';
    }

}
