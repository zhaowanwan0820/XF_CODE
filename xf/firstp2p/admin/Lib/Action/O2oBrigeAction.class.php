<?php

/**
 * O2O 桥接, 权限控制在这里做
 * 实际跳转 o2o.firstp2padmin.corp.ncfgroup.com
 *
 *
 */
class O2oBrigeAction extends CommonAction {

    public function __call($method, $params) {
        $this->redirectO2oAdmin($method);
    }

    public function auth() {
        $adminInfo = $this->getAdminInfo();
        $authInfo = array('isLogin' => 1, 'adminInfo' => $adminInfo);
        echo json_encode($authInfo, JSON_UNESCAPED_UNICODE);
    }

    private function redirectO2oAdmin($action) {
        $token = es_session::id();
        $action = str_replace("__", "-", $action);
        $action = str_replace("_", "/", $action);

        $params = array();
        $params['token'] = $token;
        $unsetFields = array('m', 'a');
        foreach ($_REQUEST as $key=>$value) {
            if (in_array($key, $unsetFields)) {
                continue;
            }
            $params[$key] = $value;
        }

        $queryString = http_build_query($params, null, '&');
        header('Location:http://' . $GLOBALS['sys_config']['O2O_ADMIN_DOMAIN'] . '/' . $action . '?' . $queryString);
    }

    private function getAdminInfo() {
        $adminSession = es_session::get(md5(conf("AUTH_KEY")));

        $adminInfo = array();
        $adminInfo['adminName'] = $adminSession['adm_name'];
        $adminInfo['adminId'] = intval($adminSession['adm_id']);

        return $adminInfo;
    }

}
