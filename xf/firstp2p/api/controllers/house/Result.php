<?php
/**
 * 网信房贷 成功页
 * @author sunxuefeng sunxuefeng@ucfgroup
 * @data 2017.10.26
 */

namespace api\controllers\house;


use libs\web\Form;
use api\controllers\AppBaseAction;
use NCFGroup\Protos\Ptp\Enum\HouseEnum;

class Result extends AppBaseAction {
    /**
     * true：输出到模版
     * false：返回json结果(default)
     */
    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_TOKEN_ERROR'),
            'result' => array('filter' => 'int', 'option' => array('optional' => true))
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

        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('result', $data['result']);
        $this->template = $this->getTemplate('loan_status');
    }
}
