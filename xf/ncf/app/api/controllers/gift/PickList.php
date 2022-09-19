<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\o2o\CouponService;

class PickList extends AppBaseAction {

    public function init() {
        parent::init();

        $this->appversion = isset($_SERVER['HTTP_VERSION']) ? $_SERVER['HTTP_VERSION'] : '';
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            // O2O Feature 投资记录，根据投资记录读取用户的投资数据
            'action' => array('filter' => 'required', 'message' => 'action is required'),
            'load_id' => array("filter" => "required", "message"=>"deal load id is error"),
            'deal_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
            'o2oViewAccess' => array('filter' => 'string', 'option' => array('optional' => true)),
            // O2O Feature
            //'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            // 处理领取逻辑所需的参数
            'couponGroupId' => array("filter" => "int"),
            'storeId' => array("filter" => "int"),
            'useRules' => array("filter" => "int"),
            'address_id' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $address_id = isset($_COOKIE['address_id']) ? $_COOKIE['address_id'] : '';
        $loginUser = $this->user;

        $dealLoadId = $data['load_id'];
        $dealType = isset($data['deal_type']) ? $data['deal_type'] : 1;
        $userid = $loginUser['id'];
        $page = isset($data['page']) ? intval($data['page']) : 1;
        $page = $page < 1 ? 1 : $page;

        $result = array(
            'token' => $data['token'],
            'mobile' => $loginUser['mobile'],
            'userInfo' => $loginUser,
            'action' => $data['action'],
            'deal_id' => $dealLoadId,
            'deal_type' => $dealType,
            'usertoken' => $data['token'],

        );
        $response = CouponService::giftPickList($userid, $data['action'], $dealLoadId, $dealType);
        if (!empty($response) && is_array($response)) {
            $result = array_merge($result, $response);
        }

        $this->json_data = $result;
    }
}
