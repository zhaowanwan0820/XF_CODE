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
use core\service\account\AccountAuthService;
use core\enum\AccountAuthEnum;

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
        }
    }

    public function invoke() {
        $params = $this->form->data;
        $user = $this->user;

        // 用户权限解除检查
        if (!in_array($params['privilege'], [
            AccountAuthEnum::GRANT_TYPE_INVEST,
            AccountAuthEnum::GRANT_TYPE_REPAY,
            AccountAuthEnum::GRANT_TYPE_PAYMENT
        ])) {
            $this->setErr('ERR_REMOVE_PRIVILIEGES');
        }

        // 读取用户授权信息
        $removeResult = AccountAuthService::cancelAuth($user['id'], [$params['privilege']]);
        if (empty($removeResult) || !isset($removeResult['code']) || $removeResult['code'] != 0) {
            $errmsg = !empty($removeResult['msg']) ? $removeResult['msg'] : '取消授权失败';
            $this->setErr('ERR_REMOVE_PRIVILIEGES', $errmsg);
        }

        $this->json_data = $removeResult;
    }
}
