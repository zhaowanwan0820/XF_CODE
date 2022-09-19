<?php
/**
 * 网信房贷 申请页 跳转到个人信息
 * @author sunxuefeng sunxuefeng@ucfgroup
 * @data 2017.9.30
 */

namespace api\controllers\house;

use libs\web\Form;
use api\controllers\AppBaseAction;

class Apply extends AppBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_TOKEN_ERROR')
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL',$this->form->getErrorMsg());
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

        $this->tpl->assign('user_info', $loginUser);
        $this->tpl->assign('data', $data);
        $this->template = $this->getTemplate('personal_information_filling');
    }
}