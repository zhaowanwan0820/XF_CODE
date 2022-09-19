<?php

namespace api\controllers\user;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;

class QueryPayPwd extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('post');
        //$this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'Token不能为空'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
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

        $merchant = $GLOBALS['sys_config']['XFZF_PAY_MERCHANT'];
        $retData = $this->rpc->local("UserService\queryPayPwd", array($loginUser['id'], $merchant));
        if ($retData === false) {
            $this->setErr(0, '查询失败！');
            return false;
        } elseif ($retData['status'] == '00') {
            if ($retData['payPasswdSet'] == '1') {
                $ret = array("success" => ConstDefine::RESULT_SUCCESS);
            } else {
                $ret = array("success" => ConstDefine::RESULT_FAILURE);
            }
            $this->json_data = $ret;
            return true;
        } else {
            $this->setErr(0, '查询失败！');
            return false;
        }
    }
}
