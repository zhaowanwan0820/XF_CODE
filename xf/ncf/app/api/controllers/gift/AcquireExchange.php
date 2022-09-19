<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\o2o\CouponService;
use core\service\deal\DealService;

class AcquireExchange extends AppBaseAction {

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            // O2O Feature 礼物ID
            'couponGroupId' => array("filter" => "required", "message"=>"coupon group id is error"),
            'storeId' => array("filter" => "required", "message"=>"storeId is error"),
            'useRules' => array("filter" => "required", "message"=>"useRules is error"),
            'action' => array("filter" => "required", "message"=>"action is error"),
            'load_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'deal_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            // O2O Feature
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'o2oViewAccess' => array('filter' => 'string', 'option' => array('optional' => true)),
            'address_id' => array('filter' => 'int'),
            'receiverName' => array('filter' => 'string'),
            'receiverPhone' => array('filter' => 'string'),
            'receiverCode' => array('filter' =>'string'),
            'receiverArea' => array('filter' => 'string'),
            'receiverAddress' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->user;

        $couponGroupId = $data['couponGroupId'];
        $loadId = intval($data['load_id']);
        // CouponGroupEnum::CONSUME_TYPE_P2P
        $dealType = isset($data['deal_type']) ? $data['deal_type'] : 1;
        $action = intval($data['action']);
        $user_id = $loginUser['id'];
        $storeId = isset($data['storeId']) ? $data['storeId'] : 0;
        $useRules = isset($data['useRules']) ? $data['useRules'] : 0;

        $dealService = new DealService();
        $dealInfo = $dealService->getDealInfo($loadId);

        $result = array(
            'userInfo' => $loginUser,
            'o2o_frontend_sign' => md5('o2o:' . $loginUser['id']),
            'token' => $data['token'],
            'load_id' => $data['load_id'],
            'action' => $action,
            'site_id' => \libs\utils\Site::getId(),
        );
        $response = CouponService::giftAcquireExchange(
            $loginUser['id'], $loginUser['mobile'], $data['address_id'],
            $storeId, $useRules, $couponGroupId, $loadId, $dealType, $action
        );

        $result['isExchange'] = 0;
        if (!isset($response['flag'])) {
            $result = array_merge($result, $response);
            $result['isExchange'] = 1;
        }
        $this->json_data = $result;
    }
}
