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
use core\service\AccountAuthorizationService;
use core\dao\AccountAuthorizationModel;

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
        if (!in_array($params['privilege'], [AccountAuthorizationModel::GRANT_TYPE_INVEST, AccountAuthorizationModel::GRANT_TYPE_REPAY, AccountAuthorizationModel::GRANT_TYPE_PAYMENT])) {
            return $this->show_error('权限不正确', '', 1);
        }

        // 读取用户授权信息
        $authorization = new \core\service\AccountAuthorizationService();
        $removeResult = $authorization->cancelAuth($user['id'], [$params['privilege']]);
        if (empty($removeResult) || !isset($removeResult['code']) || $removeResult['code'] != 0) {
            $errmsg = !empty($removeResult['msg']) ? $removeResult['msg'] : '取消授权失败';
            return $this->show_error($errmsg, '授权管理', 1);
        }

        $this->show_success('授权取消成功', '授权管理', 1);
   }
}
