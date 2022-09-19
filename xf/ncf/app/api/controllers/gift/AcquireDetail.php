<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\o2o\CouponService;

/**
 * 新版优化的领取详情页面
 */

class AcquireDetail extends AppBaseAction {
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
            'o2oViewAccess' => array('filter' => 'string', 'option' => array('optional' => true)),
            // 处理领取逻辑所需的参数
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

        if (isset($data['o2oViewAccess']) && $data['o2oViewAccess']) {
            \es_session::set('o2oViewAccess', 'pick');//session中设置页面浏览的来源，方便前端控制关闭逻辑
        }
        $couponGroupId = intval($data['couponGroupId']);

        $response = CouponService::giftAcquireDetail($couponGroupId, $loginUser['id'], $loginUser['mobile'], $data['action'], $data['load_id'], $address_id);
        if (empty($response) || !is_array($response)) {
            $response = array();
        }
        $response['token'] = $data['token'];
        $response['usertoken'] = $data['token'];
        $response['mobile'] = $loginUser['mobile'];
        $response['userInfo'] = $loginUser;
        $response['action'] = $data['action'];
        $response['load_id'] = $data['load_id'];

        $this->json_data = $response;
    }

}
