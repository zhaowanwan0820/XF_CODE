<?php
namespace api\controllers\bonus;

use api\controllers\AppBaseAction;
use libs\web\Form;

/**
 * Passport
 * 用户信息认证
 */
class Passport extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_GET_USER_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $payUserExisted = $this->rpc->local("PaymentService\mobileRegister", array($loginUser['id']));
        if (!in_array($payUserExisted, array(0, 1))) { // 是否处于已经开户以及本次开户成功中
            $this->setErr('ERR_MANUAL_REASON', '支付未开户');
            return false;
        }

        $result = array('userId' => $loginUser['id'],
                        'merchantId' => $GLOBALS['sys_config']['XFZF_PAY_MERCHANT'],
                       );
        $queryString = \libs\utils\Aes::buildString($result);
        $signature = md5($queryString."&key=".$GLOBALS['sys_config']['XFZF_SEC_KEY']);
        $result['sign'] = $signature;
        $this->json_data = $result;

    }
}
