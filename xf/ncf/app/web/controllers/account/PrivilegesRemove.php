<?php

/**
 * PrivilegesRemove.php
 * 删除授权
 * @date  2018-01-03 18:56:00
 * @author wangqunqiang <wangqunqiang@ucfgroup.com>
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
use libs\web\Form;
use core\service\account\AccountService;
use core\service\account\AccountAuthService;
use core\enum\AccountAuthEnum;

/**
 * 用户授权管理 删除授权
 */
class PrivilegesRemove extends BaseAction {

    public function init() {
        if (!$this->check_login()) return false;
        $this->form = new Form("");
        $this->form->rules = array(
            "accountId" => array("filter" => "int"),
            "privilege" => array("filter" => "int"),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke() {
        $params = $this->form->data;
        $user = $GLOBALS['user_info'];
        // 用户权限解除检查
        if (!in_array($params['privilege'], [AccountAuthEnum::GRANT_TYPE_INVEST, AccountAuthEnum::GRANT_TYPE_REPAY, AccountAuthEnum::GRANT_TYPE_PAYMENT])) {
            return $this->show_error('权限不正确', '', 1);
        }

        // 获取用户账户ID
        $accountId = AccountService::getUserAccountId($user['id'], $user['user_purpose']);
        // 读取用户授权信息
        $removeResult = AccountAuthService::cancelAuth($accountId, [$params['privilege']]);
        if (empty($removeResult) || !isset($removeResult['code']) || $removeResult['code'] != 0) {
            $errmsg = !empty($removeResult['msg']) ? $removeResult['msg'] : '取消授权失败';
            return $this->show_error($errmsg, '授权管理', 1);
        }

        $this->show_success('授权取消成功', '授权管理', 1);
   }
}
