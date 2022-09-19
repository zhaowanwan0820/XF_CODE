<?php

/**
 * OpenBrigeAction.php
 * 
 * Filename: OpenBrigeAction.php
 * Descrition: 开放平台后台桥接
 * Author: yutao@ucfgroup.com
 * Date: 16-1-27 下午3:41
 */

class OpenBrigeAction extends CommonAction {

    public function __call($method, $parms) {
        $this->redirectOpenAdmin($method);
    }
    
    public function auth() {
        $adminInfo = $this->getAdminInfo();
        $authInfo = array('isLogin' => 1, 'adminInfo' => $adminInfo);
        echo json_encode($authInfo, JSON_UNESCAPED_UNICODE);
    }

    private function redirectOpenAdmin($action) {
        $token = es_session::id();

        $action = str_replace("__", "-", $action);
        $action = str_replace("_", "/", $action);
        header('Location:http://' . $GLOBALS['sys_config']['OPEN_ADMIN_DOMAIN'] . '/' . $action . '?token=' . $token);
    }

    private function getAdminInfo() {
        $adminSession = es_session::get(md5(conf("AUTH_KEY")));

        $adminInfo = array();
        $adminInfo['adminName'] = $adminSession['adm_name'];
        $adminInfo['adminId'] = intval($adminSession['adm_id']);

        return $adminInfo;
    }

}
