<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\o2o\CouponService;

class MineDetail extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'couponId' => array('filter' => 'required'),
            // 处理领取逻辑所需的参数
            'storeId' => array('filter' => 'int'),
            'useRules' => array('filter' => 'int'),
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

        $user_id = $loginUser['id'];
        $response = CouponService::giftMineDetail($data['couponId'], $user_id, $data['storeId'], $data['useRules'], $data['address_id']);

        if (!is_array($response) || empty($response)) {
            $response = array();
        }
        $response['token'] = $data['token'];
        $response['mobile'] = $loginUser['mobile'];
        $response['o2o_frontend_sign'] = md5('o2o:' . $loginUser['id']);
        $response['usertoken'] = $data['token'];
        $response['userInfo'] = $loginUser;

        $this->json_data = $response;
    }
}
