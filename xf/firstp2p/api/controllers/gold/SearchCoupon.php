<?php

namespace api\controllers\gold;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;

class SearchCoupon extends AppBaseAction {

    const IS_H5 = true;
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'dealId' => array('filter' => 'required', 'message' => 'ERR_DEAL_NOT_EXIST'),
            'buyAmount' => array('filter' => 'required', 'option' => array('optional' => true)),
            'code' => array('filter' => 'string', 'option' => array('optional' => true)),
            'site_id' => array(
                    'filter' => 'int',
                    'option' => array('optional' => true),
                            'message' => 'ERR_PARAMS_VERIFY_FAIL',
                    ),
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
            return app_redirect('/error/tkTimeout');
        }
        $this->tpl->assign('data', $data);
        $this->tpl->assign('site_id', isset($data['site_id']) ? $data['site_id'] : 0);
    }

    public function _after_invoke() {
        $this->tpl->display($this->template);
    }

}
