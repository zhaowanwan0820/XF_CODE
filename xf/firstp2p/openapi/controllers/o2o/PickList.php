<?php

namespace openapi\controllers\o2o;

use libs\web\Form;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\O2O\RequestGetCouponInfo;
use core\service\O2OService;

class PickList extends BaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'oauth_token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'action' => array('filter' => 'required', 'message' => 'action is required'),
            // O2O Feature 投资记录，根据投资记录读取用户的投资
            'deal_load_id' => array("filter" => "required", "message"=>"deal load id is error"),
            'deal_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByAccessToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $userId = $userInfo->userId;
        $action = $data['action'];
        $dealLoadId = $data['deal_load_id'];
        $dealType = isset($data['deal_type']) ? $data['deal_type'] : CouponGroupEnum::CONSUME_TYPE_P2P;
        $page = empty($data['page']) ? 1 : intval($data['page']);
        $rpcParams = array($userId, $action, $dealLoadId, $dealType);
        $couponGroupList = $this->rpc->local('O2OService\getCouponGroupList', $rpcParams);
        if ($couponGroupList === false) {
            $msg = $this->rpc->local('O2OService\getErrorMsg');
            $this->setErr('ERR_COUPON_ERROR', $msg);
            return false;
        }

        $this->json_data = $couponGroupList;
        return true;
    }
}
