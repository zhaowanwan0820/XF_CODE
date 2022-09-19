<?php
/**
 * PrivilegesQuery.php
 * 授权查询
 * @date  2018-01-03 18:56:00
 * @author wangqunqiang <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\account;

use web\controllers\BaseAction;
use core\service\account\AccountAuthService;

/**
 * 用户授权管理 删除授权
 */
class PrivilegesQuery extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $user = $GLOBALS['user_info'];
        // 读取用户授权信息
        $userAuthorization = AccountAuthService::getAuthList($user['id'], true);
        // 检查用户是否可以取消授权
        echo json_encode($userAuthorization);
    }
}
