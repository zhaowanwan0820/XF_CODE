<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\ApiBaseAction;

class Detail extends ApiBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            // O2O Feature 礼物ID
            'couponGroupId' => array("filter" => "required"),
            'load_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'action' => array('filter' => 'int', 'option' => array('optional' => true)),
            // O2O Feature
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
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

        $couponGroupId = intval($data['couponGroupId']);
        $gift_detail = $this->rpc->local('O2OService\getCouponGroupInfo', array($couponGroupId, $loginUser['id'], $data['action'], $data['load_id']));
        $this->tpl->assign('coupon', $gift_detail);
        $this->tpl->assign('action', $this->form->data['action']);
        $this->tpl->assign('load_id', $this->form->data['load_id']);
        $this->tpl->assign('usertoken', $this->form->data['token']);
        $this->template = $this->getTemplate('detail');
    }

}
