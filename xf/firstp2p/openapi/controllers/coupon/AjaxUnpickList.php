<?php

namespace openapi\controllers\coupon;

use libs\web\Form;
use openapi\controllers\PageBaseAction;
use libs\utils\PaymentApi;

class AjaxUnpickList extends PageBaseAction {
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
        #$loginUser->id = 40;
        $page = intval($data['page']);
        $page = $page ? $page : 1;

        $rpcParams = array($loginUser['userId'], $page);
        PaymentApi::log('openapi - 进入未领取列表 - 请求参数'.json_encode($rpcParams, JSON_UNESCAPED_UNICODE));
        $unPickList = $this->rpc->local('O2OService\getUnpickList', $rpcParams);
        PaymentApi::log('openapi - 进入列表 - 请求结果'.json_encode($unPickList, JSON_UNESCAPED_UNICODE));

        $this->json_data = $unPickList;
        return true;
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
