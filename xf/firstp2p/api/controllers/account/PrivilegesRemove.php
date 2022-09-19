<?php

/**
 * PrivilegesRemove.php
 * 删除授权
 * @date  2018-01-03 18:56:00
 * @author wangqunqiang <wangqunqiang@ucfgroup.com>
 */

namespace api\controllers\account;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\AccountAuthorizationService;
use core\dao\AccountAuthorizationModel;

/**
 * 用户授权管理 删除授权
 */
class PrivilegesRemove extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            "accountId" => array("filter" => "required", "message" => "accountId is required"),
            "privilege" => array("filter" => "required", "message" => "privilege is required")
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
        // 用户权限解除检查
        if (!in_array($params['privilege'], [AccountAuthorizationModel::GRANT_TYPE_INVEST, AccountAuthorizationModel::GRANT_TYPE_REPAY, AccountAuthorizationModel::GRANT_TYPE_PAYMENT])) {
            $this->setErr('ERR_REMOVE_PRIVILIEGES');
            return false;
        }

        // 读取用户授权信息
        $authorization = new \core\service\AccountAuthorizationService();
        $removeResult = $authorization->cancelAuth($user['id'], [$params['privilege']]);
        if (empty($removeResult) || !isset($removeResult['code']) || $removeResult['code'] != 0) {
            $errmsg = !empty($removeResult['msg']) ? $removeResult['msg'] : '取消授权失败';
            $this->setErr('ERR_REMOVE_PRIVILIEGES', $errmsg);
            return false;
        }

        $this->json_data = $removeResult;
    }
}
