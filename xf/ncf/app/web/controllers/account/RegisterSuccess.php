<?php
/**
 * 注册成功
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\BankModel;
use libs\utils\PaymentApi;
class RegisterSuccess extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
    }

    public function invoke() {
        $userInfo = $GLOBALS['user_info'];
        $formString = $this->rpc->local('PaymentService\getBindCardForm', [['token' => base64_encode(microtime(true))], true, false, 'bindCardForm']);
        $this->tpl->assign('formString', $formString);
        $this->template = 'web/views/v2/account/rna_success.html';
        return;
    }
}
