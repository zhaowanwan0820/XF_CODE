<?php

namespace openapi\controllers\coupon;

use openapi\controllers\PageBaseAction;
use libs\web\Form;
use libs\utils\PaymentApi;

class Mine extends PageBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'oauth_token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'o2oViewAccess' => array('filter' => 'string', 'option' => array('optional' => true)),
            'status' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByAccessToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $loginUser = $loginUser->toArray();
        #$loginUser['id'] = 40;
        $user_id = $loginUser['userId'];
        $page = intval($data['page']);
        $page = $page ? $page : 1;

        $status = isset($data['status']) ? intval($data['status']) : 1;//默认显示未使用的
        $rpcParams = array($user_id, $status, $page);
        PaymentApi::log('openapi - 进入我已经领取券列表 - 请求参数'.json_encode($rpcParams, JSON_UNESCAPED_UNICODE));
        $couponList = $this->rpc->local('O2OService\getUserCouponList', $rpcParams);
        PaymentApi::log('openapi - 进入我已经领取券列表 - 请求结果'.json_encode($couponList, JSON_UNESCAPED_UNICODE));
        $return_uri = \es_session::get('return_uri');

        $this->tpl->assign("returnBtn", $_COOKIE['returnBtn']);
        $this->tpl->assign('couponList', $couponList);
        $this->tpl->assign('couponListCount', empty($couponList) ? 0 : count($couponList));
        $this->tpl->assign('oauth_token', $this->form->data['oauth_token']);
        $this->tpl->assign('return_uri', $return_uri);
        $this->template = 'openapi/views/coupon/mine.html';
    }

}
