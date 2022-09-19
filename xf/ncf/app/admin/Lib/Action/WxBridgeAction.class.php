<?php

/**
 * 桥接类, 支持按照module和action跳转并携带query参数
 */
class WxBridgeAction extends AuthAction {
    public function __construct()
    {
        parent::__construct();
    }

    public function __call($method, $params) {
        $this->redirectUserAdmin($method);
    }

    private function redirectUserAdmin($action) {
        $token = es_session::id();
        $params = array();
        $params['token'] = $token;
        $params['a'] = $_REQUEST['m'];
        $params['toAction'] = $action;
        unset($_REQUEST['m'],$_REQUEST['a']);
        $params = array_merge($params, $_REQUEST);

        $queryString = http_build_query($params, null, '&');
        $location = 'http://'.$GLOBALS['sys_config']['USER_ADMIN_DOMAIN'].'/m.php?m=UserBridge&'.$queryString;
        header('Location:'.$location);
    }
}
