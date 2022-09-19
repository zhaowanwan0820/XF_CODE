<?php
/**
 * 验卡跳转
 * @author wangqunqiang<wangqunqiang@ucfgroup.com>
 */

namespace web\controllers\payment;
use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\PaymentModel;
use core\dao\PaymentNoticeModel;
use core\dao\DealOrderModel;
use libs\web\Url;
use libs\utils\PaymentApi;
use core\service\PaymentService;
use core\service\UserService;
use core\service\risk\RiskService;

class GoValidateCard extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
    }


    public function invoke() {
        $userInfo = $GLOBALS['user_info'];

        //风控检查
        $extraData = [
            'user_id' => $userInfo['id'],
            'mobile' => $userInfo['mobile'],
        ];
        $checkRet = RiskService::check('CHANGE_CARD', $extraData);
        if (false === $checkRet) {
            return $this->show_error('操作失败，请稍后再试', '操作失败', 0, 0, '/account', 3);
        }

        /**
         * 非企业用户增加支付平台开户校验
         */
        $userService = new UserService($userInfo['id']);
        if($userService->isEnterprise())
        {
            $addbankUrl = '/deal/promptCompany';
            return app_redirect($addbankUrl);
        }

        $checkResult = $userService->isBindBankCard();
        // 港澳台用户本地绑卡
        $hasPassport = $this->rpc->local('AccountService\hasPassport', array($userInfo['id']));
        if ($hasPassport && $checkResult['respCode'] == UserService::STATUS_BINDCARD_UNBIND)
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
        //$form = PaymentApi::instance()->getGateway()->getForm('authCard', array('userId' => $userInfo['id']));
        //echo $form;
        //echo '<script>document.forms[0].submit();</script>';
        header("Location:{$url}");
    }

}
