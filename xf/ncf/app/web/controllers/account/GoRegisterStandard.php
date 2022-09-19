<?php
/**
 * 普惠注册成功
 */
namespace web\controllers\account;

use web\controllers\BaseAction;
use core\service\supervision\SupervisionService;

class GoRegisterStandard extends BaseAction {

    public function init() {
        if(!$this->check_login()) return app_redirect('/');
    }

    public function invoke() {
        if (SupervisionService::isServiceDown())
        {
            return $this->show_tips(SupervisionService::maintainMessage());
        }

        $userInfo = $GLOBALS['user_info'];
        if (intval($GLOBALS['user_info']['idcardpassed']) != 1 || !$GLOBALS['user_info']['real_name']) {
           return app_redirect('/account/addbank');
        }
        // 展示存管开户表单
        $url = '/payment/transit?srv=registerStandard';
        header("Location: {$url}");
        return;
    }
}
