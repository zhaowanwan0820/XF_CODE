<?php
/**
 * PC授权管理页面
 *
 * @date 2018-01-03
 */
namespace web\controllers\account;

use web\controllers\BaseAction;
use core\service\account\AccountAuthService;
use core\service\account\AccountService;

class Privileges extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $user_info = $GLOBALS['user_info'];
        $userAuthorization = AccountAuthService::getAuthList($user_info['id'], true);

        $accountInfo = AccountService::getAccountInfo($user_info['id'], $user_info['user_purpose']);
        if (AccountService::isUnactivated($accountInfo)) {
            return $this->show_error('请先激活网贷P2P账户', '操作失败', 0, 0, sprintf('/payment/transit?srv=register&return_url=%s', get_domain() . '/account'), 3);
        }

        $this->tpl->assign('privileges', $userAuthorization);
        return;
    }
}
