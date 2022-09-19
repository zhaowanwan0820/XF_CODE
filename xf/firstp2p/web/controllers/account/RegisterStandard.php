<?php
/**
 * 普惠注册成功
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\BankModel;
use libs\utils\PaymentApi;
use libs\payment\supervision\Supervision;
class RegisterStandard extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
    }

    public function invoke() {
        if (Supervision::isServiceDown())
        {
            return $this->show_tips(Supervision::maintainMessage());
        }

        $userInfo = $GLOBALS['user_info'];
        // 展示存管开户表单
        $result= $this->rpc->local('SupervisionAccountService\memberStandardRegisterPage', [$userInfo['id']]);
        $this->tpl->assign('formString', $result['data']['form']);
        $this->tpl->assign('formId', $result['data']['formId']);
        $this->template = 'web/views/v3/account/rna_standard_success.html';
        return;
    }
}
