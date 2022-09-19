<?php

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Aes;

class ApplyChgPwd extends AppBaseAction {
    public function init() {
        parent::init();
        if (app_conf('API_PAYMENT_APPLY_OPEN') === '0') {
            $this->setErr(ERR_SYSTEM, '支付系统升级中，请稍后重试，如需帮助请拨打95782');
            return false;
        }
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'ERR_AUTH_FAIL'),
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
        //$loginUser = $this->rpc->local('UserService\getUser', array(666));

        $merchant = $GLOBALS['sys_config']['XFZF_PAY_MERCHANT'];
        $params = array(
                    'userId' => $loginUser['id'],
                    'merchantId' => $merchant,
                );
        $query_string = \libs\utils\Aes::buildString($params);
        $signature = md5($query_string."&key=".$GLOBALS['sys_config']['XFZF_SEC_KEY']);
        $params['name'] = $loginUser['real_name'] ? $loginUser['real_name'] : "无";
        $params['idno'] = $loginUser['idno'];
        $params['sign'] = $signature;

        $this->json_data = $params;
        return true;
    }
}
