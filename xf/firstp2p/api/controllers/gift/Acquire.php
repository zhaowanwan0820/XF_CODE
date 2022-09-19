<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\ApiBaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;

class Acquire extends ApiBaseAction {

    const IS_H5 = true;

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            // O2O Feature 礼物ID
            'couponGroupId' => array("filter" => "required", "message"=>"coupon group id is error"),
            'action' => array("filter" => "required", "message"=>"action is error"),
            'load_id' => array('filter' => 'int', 'option' => array('optional' => true)),
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

        $couponGroupId = $data['couponGroupId'];
        $loadId = intval($data['load_id']);
        $action = intval($data['action']);
        $rpcParams = array($couponGroupId, $loginUser['id'], $action, $loadId, $loginUser['mobile']);
        $gift = $this->rpc->local('O2OService\acquireCoupon', $rpcParams);
        $signData = $this->rpc->local('O2OService\addSign', array($gift, $loginUser));
        foreach ($signData as $key => $val) {
            $this->tpl->assign($key, $val);
        }
        $this->tpl->assign('userInfo', $loginUser);
        $this->tpl->assign('o2o_frontend_sign', md5('o2o:' . $loginUser['id']));
        $this->tpl->assign('token', $this->form->data['token']);
        $this->tpl->assign('load_id', $this->form->data['load_id']);
        $this->tpl->assign('site_id', \libs\utils\Site::getId());
        if (empty($gift)) {
            $msg = $this->rpc->local('O2OService\getErrorMsg');
            $this->tpl->assign('errMsg', $msg);
            $this->tpl->assign('action', $action);
            $this->template = $this->getTemplate('acquire_fail');
        } else {
            $this->tpl->assign('coupon', $gift);
            $this->template = $this->getTemplate('acquire_suc');
        }
    }

}
