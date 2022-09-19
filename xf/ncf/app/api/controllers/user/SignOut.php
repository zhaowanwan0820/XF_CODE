<?php

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\BaseAction;
use core\service\user\UserService;

/**
 * 用户退出
 * @package api\controllers\user
 */
class SignOut extends BaseAction {
    // 是否启用session
    protected $useSession = true;
    // 是否需要授权
    protected $needAuth = false;

    public function init() {
        parent::init();
    }

    public function invoke() {
        $uid = $_COOKIE['uid'];
        if (!empty($uid)) {
            $uid = de32Tonum($uid);
            \SiteApp::init()->cache->delete('TRACKIF_USER_WXLC_LOGIN_' . $uid);
        }

        UserService::userLogout();
        $this->json_data = array('status'=>1);
    }
}