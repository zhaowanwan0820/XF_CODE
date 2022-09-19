<?php

/**
 * user桥接, 个人会员列表
 */
class UserBridgeAction {

    public function __call($method, $params) {
        $this->redirectUserAdmin($method);
    }

    public function auth() {
        $adminInfo = $this->getAdminInfo();
        // 已经通过验证的用户，在桥接中进行登录验证的时候，返回已认证的标识
        $adminInfo['adminAuthPassed'] = 1;
        $authInfo = array('isLogin' => 1, 'adminInfo' => $adminInfo);
        echo json_encode($authInfo, JSON_UNESCAPED_UNICODE);
    }

    private function redirectUserAdmin($action) {
        $token = es_session::id();
        $params = array();
        $params['token'] = $token;
        $params['a'] = $action;

        $queryString = http_build_query($params, null, '&');
        $location = 'http://'.$GLOBALS['sys_config']['USER_ADMIN_DOMAIN'].'/m.php?m=UserBridge&'.$queryString;
        header('Location:'.$location);
    }

    private function getAdminInfo() {
        $adminSession = es_session::get(md5(conf("AUTH_KEY")));
        return $adminSession;
    }

}
