<?php
namespace openapi\controllers\cfp;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestCustomerMemo;
use NCFGroup\Protos\Ptp\RPCErrorCode;

class MemoCustomer extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            "customerId" => array('filter' => 'int', 'message' => '请选择客户'),
            "memo" => array('filter' => 'string', 'message' => '请填写备注'),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByAccessToken();
        if (!is_object($userInfo) || $userInfo->resCode) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $customerId = $data['customerId'];
        $memo = $data['memo'];

        $request = new RequestCustomerMemo();
        $request->setUserId(intval($userInfo->userId));
        $request->setCustomerId(intval($customerId));
        $request->setMemo($memo);
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpCfp',
            'method' => 'addMemoForCustomer',
            'args' => $request
        ));

        $ret = array();
        if ($response->resCode === RPCErrorCode::SUCCESS) {
            $ret['success'] = 0;
        } else {
            $ret['success'] = 1;
        }

        $this->json_data = $ret;
        return true;
    }

}
