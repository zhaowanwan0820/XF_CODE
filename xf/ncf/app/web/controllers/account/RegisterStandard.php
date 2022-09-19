<?php
/**
 * 普惠注册成功
 */
namespace web\controllers\account;

use libs\web\Form;
use libs\common\ErrCode;
use web\controllers\BaseAction;
use core\enum\SupervisionEnum;
use core\service\account\AccountService;
use core\service\supervision\SupervisionService;
use core\service\supervision\SupervisionAccountService;

class RegisterStandard extends BaseAction {
    public function init() {
        if(!$this->check_login()) return false;
    }

    public function invoke() {
        if (SupervisionService::isServiceDown()) {
            return $this->show_tips(SupervisionService::maintainMessage());
        }

        // 获取账户ID
        $userInfo = $GLOBALS['user_info'];
        $accountId = AccountService::getUserAccountId($userInfo['id'], $userInfo['user_purpose']);
        if (empty($accountId)) {
            return $this->show_error(ErrCode::getMsg('ERR_ACCOUNT_NOEXIST'));
        }

        // 展示存管开户表单
        $obj = new SupervisionAccountService();
        $result = $obj->memberStandardRegisterPage($accountId);
        if (empty($result) || $result['status'] == SupervisionEnum::RESPONSE_FAILURE) {
            return $this->show_error(ErrCode::getMsg('ERR_GET_FORM'));
        }

        $this->tpl->assign('formString', $result['data']['form']);
        $this->tpl->assign('formId', $result['data']['formId']);
        $this->template = 'web/views/account/rna_standard_success.html';
        return;
    }
}
