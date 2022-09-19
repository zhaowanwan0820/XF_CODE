<?php
/**
 * 网信房贷 用户协议页
 * @author sunxuefeng sunxuefeng@ucfgroup
 * @data 2017.10.17
 */

namespace api\controllers\house;

use libs\web\Form;
use api\controllers\AppBaseAction;

class UserProtocol extends AppBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
//        $this->form = new Form();
//        $this->form->rules = array(
//            'token' => array('filter' => 'required', 'message' => 'ERR_TOKEN_ERROR')
//        );
//
//        if (!$this->form->validate()) {
//            $this->setErr($this->form->getErrorMsg());
//            return false;
//        }
    }

    public function invoke() {
//        $data = $this->form->data;
//        $loginUser = $this->getUserByToken();
//        if (empty($loginUser)) {
//            $this->setErr('ERR_GET_USER_FAIL');
//            return false;
//        }
//
//        $this->tpl->assign('token', $data['token']);
        $this->template = $this->getTemplate('user_service_agreement');
    }
}
