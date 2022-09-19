<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
use core\service\PaymentService;

class FactorAuthAction extends CommonAction{
    public function index() {
        $paymentService = new PaymentService();

        $params = [];
        $adminSession = \es_session::get(md5(conf("AUTH_KEY")));
        $params['creatName'] = urlencode($adminSession['adm_name']);
        $params['groupId'] = $adminSession['adm_role_id'];
        $params['merId'] = \libs\utils\PaymentApi::instance()->getGateway()->getConfig('common', 'MERCHANT_ID');

        $paymentService->gotoFactorAuthPage($params);
        return true;
    }
}
