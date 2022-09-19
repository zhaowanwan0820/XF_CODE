<?php
/**
 * 普惠注册成功
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\BankModel;
use libs\payment\supervision\Supervision;

class GoRegisterStandard extends BaseAction {

    public function init() {
        if(!$this->check_login()) return app_redirect('/');
    }

    public function invoke() {
        if (Supervision::isServiceDown())
        {
            return $this->show_tips(Supervision::maintainMessage());
        }

        $userInfo = $GLOBALS['user_info'];
        if (intval($GLOBALS['user_info']['idcardpassed']) != 1 || !$GLOBALS['user_info']['real_name']) {
           return app_redirect('/account/addbank');
        }
        // 展示存管开户表单
        $result= $this->rpc->local('SupervisionAccountService\memberStandardRegisterPage', [$userInfo['id'], 'pc', false, false]);
        header("Location:{$result['data']['url']}");
        return;
    }
}
