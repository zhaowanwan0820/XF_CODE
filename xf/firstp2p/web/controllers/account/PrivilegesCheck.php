<?php

/**
 * PrivilegesCheck.php
 * 授权校验
 * @date  2018-01-03 18:56:00
 * @author wangqunqiang <wangqunqiang@ucfgroup.com>
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
use libs\web\Form;
use core\service\AccountAuthorizationService;
use core\dao\AccountAuthorizationModel;
/**
 * 用户授权校验
 */
class PrivilegesCheck extends BaseAction {

    public function init() {
        if (!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'privilege' => array('filter' => 'required', 'message' => 'privilege is required'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }

    }

    public function invoke() {
        $params = $this->form->data;
        $grantTypeList = explode(',', $params['privilege']);
        $grantTypeArray = [];
        foreach ($grantTypeList as $grantType) {
            $grantTypeArray[$grantType] = 0;
        }
        $user = $GLOBALS['user_info'];
        // 检查用户授权
        $authorization = new AccountAuthorizationService();
        $checkRet = $authorization->checkAuth($user['id'], $grantTypeArray);
        echo json_encode($checkRet);
    }
}
