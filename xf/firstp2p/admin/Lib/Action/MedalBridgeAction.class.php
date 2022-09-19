<?php

/**
 * Medal 桥接, 权限控制在这里做
 * 实际跳转 admin.medal.firstp2p.com
 *
 *
 */
class MedalBridgeAction extends CommonAction {

    public function __call($method, $parms) {
        $this->redirectMedalAdmin($method);
    }

    public function auth() {
        $adminInfo = $this->getAdminInfo();
        $authInfo = array('isLogin' => 1, 'adminInfo' => $adminInfo);
        echo json_encode($authInfo, JSON_UNESCAPED_UNICODE);
    }

    private function redirectMedalAdmin($action) {
        $token = es_session::id();

        $action = str_replace("__", "-", $action);
        $action = str_replace("_", "/", $action);
        header('Location:http://' . $GLOBALS['sys_config']['MEDAL_ADMIN_DOMAIN'] . '/' . $action . '?token=' . $token);
    }

    private function getAdminInfo() {
        $adminSession = es_session::get(md5(conf("AUTH_KEY")));

        $adminInfo = array();
        $adminInfo['adminName'] = $adminSession['adm_name'];
        $adminInfo['adminId'] = intval($adminSession['adm_id']);

        return $adminInfo;
    }

}
