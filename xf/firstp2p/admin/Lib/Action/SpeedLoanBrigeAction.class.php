<?php

/**
 * SpeedLoanBrigeAction.class.php
 * 
 * Filename: SpeedLoanBrigeAction.class.php
 * Descrition: 速贷后台桥接
 * Author: weiwei12@ucfgroup.com
 * Date: 17-10-13
 */

class SpeedLoanBrigeAction extends CommonAction {

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
        $creditloanDomainKey = isP2PRc() ? 'CREDITLOAN_ADMIN_DOMAIN_PRE' : 'CREDITLOAN_ADMIN_DOMAIN';
        header('Location:http://' . $GLOBALS['sys_config'][$creditloanDomainKey] . '/' . $action . '?token=' . $token);
    }

    private function getAdminInfo() {
        $adminSession = es_session::get(md5(conf("AUTH_KEY")));

        $adminInfo = array();
        $adminInfo['adminName'] = $adminSession['adm_name'];
        $adminInfo['adminId'] = intval($adminSession['adm_id']);

        return $adminInfo;
    }
}
