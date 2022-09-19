<?php

/**
 * Privileges.php
 * PC授权管理页面
 *
 * @date 2018-01-03
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
use core\service\ncfph\AccountService as PhAccountService;

class Privileges extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $user_info = $GLOBALS ['user_info'];

        //未激活检查
        $phAccountService = new PhAccountService();
        $accountInfo = $phAccountService->getInfoByUserIdAndType($user_info['id'], $user_info['user_purpose']);
        if (!empty($accountInfo['isUnactivated'])) {
            return $this->show_error('请先升级网贷P2P账户', '操作失败', 0, 0, sprintf('/payment/transit?srv=register&return_url=%s', get_domain() . '/account'), 3);
        }

        $userAuthorization = $this->rpc->local('AccountAuthorizationService\getAuthList', array($user_info['id'], true));
        $this->tpl->assign('privileges', $userAuthorization);
        return;
    }

}
