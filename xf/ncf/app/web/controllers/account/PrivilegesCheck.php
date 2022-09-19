<?php
/**
 * 授权校验
 * @date  2018-01-03 18:56:00
 * @author wangqunqiang <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\account;

use web\controllers\BaseAction;
use libs\web\Form;
use core\service\account\AccountService;
use core\service\account\AccountAuthService;

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

        // 获取用户账户ID
        $accountId = AccountService::getUserAccountId($GLOBALS['user_info']['id'], $GLOBALS['user_info']['user_purpose']);
        // 检查用户授权
        $checkRet = AccountAuthService::checkAuth($accountId, $grantTypeArray);
        echo json_encode($checkRet);
    }
}