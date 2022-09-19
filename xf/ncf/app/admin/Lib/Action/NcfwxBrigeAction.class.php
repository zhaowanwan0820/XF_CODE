<?php


class NcfwxBrigeAction extends CommonAction {


    public function __call($method, $parms) {
        $this->redirectP2pAdmin($method);
    }

    public function auth() {
        $adminInfo = $this->getAdminInfo();
        $authInfo = array('isLogin' => 1, 'adminInfo' => $adminInfo);
        echo json_encode($authInfo, JSON_UNESCAPED_UNICODE);
    }

    private function redirectP2pAdmin($action) {
        $token = es_session::id();
        $action = explode('_',$action);
        $params = array();
        $params['token'] = $token;
        $params['m']=$action[0];
        if( empty($action['0'])){
            $params['a']=$action[0];
        }
        $queryString = http_build_query($params, null, '&');
        header('Location:http://' . $GLOBALS['sys_config']['USER_ADMIN_DOMAIN']  . '?' . $queryString);
    }

    private function getAdminInfo() {
        $adminSession = es_session::get(md5(conf("AUTH_KEY")));

        $adminInfo = array();
        $adminInfo['adminName'] = $adminSession['adm_name'];
        $adminInfo['adminId'] = intval($adminSession['adm_id']);

        return $adminInfo;
    }

}
