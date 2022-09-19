<?php

namespace openapi\controllers\coupon;

use libs\web\Form;
use openapi\controllers\PageBaseAction;
use libs\utils\PaymentApi;
use core\service\O2OService;

class UnpickList extends PageBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'oauth_token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            // O2O Feature
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
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
        $page = intval($data['page']);
        $page = $page ? $page : 1;
        $rpcParams = array($loginUser['userId'], $page);
        PaymentApi::log('openapi - 进入我的未领取列表 - 请求参数'.json_encode($rpcParams, JSON_UNESCAPED_UNICODE));
        $unPickList = $this->rpc->local('O2OService\getUnpickList', $rpcParams);
        PaymentApi::log('openapi - 进入我的未领取列表 - 请求结果'.json_encode($unPickList, JSON_UNESCAPED_UNICODE));
        $return_uri = \es_session::get('return_uri');
        $this->tpl->assign("returnBtn", $_COOKIE['returnBtn']);
        $this->tpl->assign('unPickList', $unPickList);
        $this->tpl->assign('unPickListCount', count($unPickList));
        $this->tpl->assign('oauth_token', $this->form->data['oauth_token']);
        $this->tpl->assign('return_uri', $return_uri);
        $this->template = 'openapi/views/coupon/unpick_list.html';
    }

}
