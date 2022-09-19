<?php

namespace openapi\controllers\coupon;

use libs\web\Form;
use openapi\controllers\PageBaseAction;
use libs\utils\PaymentApi;

class AjaxMine extends PageBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'oauth_token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
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
        $status = isset($data['status']) ? intval($data['status']) : 0;
        PaymentApi::log('openapi - 进入列表 - 请求参数'.json_encode(array($user_id, $status, $page), JSON_UNESCAPED_UNICODE));

        $couponList = $this->rpc->local('O2OService\getUserCouponList', array($user_id, $status, $page));
        PaymentApi::log('openapi - 进入列表 - 请求结果'.json_encode($couponList, JSON_UNESCAPED_UNICODE));
        $this->json_data = $couponList;
    }

    public function _after_invoke() {
        $arr_result = array();
        if ($this->errorCode == 0) {
            $arr_result["errorCode"] = 0;
            $arr_result["errorMsg"] = "";
            $arr_result["data"] = $this->json_data;
        } else {
            $arr_result["errorCode"] = $this->errorCode;
            $arr_result["errorMsg"] = $this->errorMsg;
            $arr_result["data"] = $this->json_data_err;
        }

        if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 1) {
            var_export($arr_result);
        } else {
            echo json_encode($arr_result);
        }
    }

}
