<?php

namespace api\controllers\deal;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;

class SearchCoupon extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'id' => array('filter' => 'string', 'option' => array('optional' => true)),
            'money' => array(
                'filter' => 'reg',
                'message' => 'ERR_MONEY_FORMAT',
                'option' => array(
                    'regexp' => '/^\d+(\.\d{1,2})?$/',
                    'optional' => true
                ),
            ),
            'code' => array('filter' => 'string', 'option' => array('optional' => true)),
            'couponIsFixed' => array('filter' => 'string'),
            'couponProfitStr' => array('filter' => 'string'),
            'discount_id' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_group_id' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_sign' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_detail' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_bidAmount' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
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
        $this->tpl->assign('discount_id', isset($data['discount_id']) ? $data['discount_id'] : '');
        $this->tpl->assign('discount_group_id', isset($data['discount_group_id']) ? $data['discount_group_id'] : null);
        $this->tpl->assign('discount_sign', isset($data['discount_sign']) ? $data['discount_sign'] : '');
        $this->tpl->assign('discount_detail', isset($data['discount_detail']) ? $data['discount_detail'] : '');
        $this->tpl->assign('discount_detail_encodeurl', urlencode(isset($data['discount_detail']) ? $data['discount_detail'] : ''));
        $this->tpl->assign('discount_bidAmount', isset($data['discount_bidAmount']) ? $data['discount_bidAmount'] : '');
    }

    public function _after_invoke() {
        $this->tpl->display($this->template);
    }

}
