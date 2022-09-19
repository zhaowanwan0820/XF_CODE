<?php
/**
 * 验卡跳转
 * @author wangqunqiang<wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\payment;

use web\controllers\BaseAction;
use libs\web\Url;
use libs\utils\PaymentApi;
use core\service\user\UserService;
use core\service\user\UserBindService;
use core\service\payment\PaymentUserAccountService;

class GoValidateCard extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
    }


    public function invoke() {
        $userInfo = $GLOBALS['user_info'];
        /**
         * 非企业用户增加支付平台开户校验
         */
        $isEnterprise = UserService::isEnterprise($userInfo['id']);
        if($isEnterprise)
        {
            $addbankUrl = '/deal/promptCompany';
            return app_redirect($addbankUrl);
        }

        $checkResult = UserBindService::isBindBankCard($userInfo['id']);
        // 港澳台用户本地绑卡
        $hasPassport = PaymentUserAccountService::hasPassport($userInfo['id'], $userInfo);
        if ($hasPassport && $checkResult['respCode'] == UserBindService::STATUS_BINDCARD_UNBIND)
        {
            return app_redirect('/account/addbank');
        }

        // 大陆身份证用户前往先锋支付绑卡、验卡， 港澳台用户前往先锋支付验卡
        $payment_https = empty($GLOBALS['sys_config']['TURN_ON_PAYMENT_HTTPS']) ? 0 : intval($GLOBALS['sys_config']['TURN_ON_PAYMENT_HTTPS']);
        $http = 'http://';
        if ( $payment_https == 1){
            $http = 'https://';
        }

        //分站回调原协议
        if(!empty($this->appInfo)){
            $http = preg_match('/https/i', $_SERVER['SERVER_PROTOCOL']) ? 'https://' : 'http://';
        }

        $returnUrl = $http.APP_HOST.'/account/editbank';
        $url = PaymentApi::instance()->getGateway()->getRequestUrl('authCard',
            array('userId' => $userInfo['id'], 'orderId' => md5(microtime(true)), 'returnUrl' => $returnUrl));
        header("Location:{$url}");
    }
}