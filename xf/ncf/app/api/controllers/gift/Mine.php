<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\conf\ApiConfService;
use core\service\o2o\CouponService;

class Mine extends AppBaseAction {

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
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
        $loginUser = $this->user;

        $user_id = $loginUser['id'];
        $page = intval($data['page']);
        $page = $page ? $page : 1;

        // 默认传0，表示不做状态判断
        $status = isset($data['status']) ? intval($data['status']) : 0;
        $couponList = CouponService::getUserCouponList($user_id, $status, $page);
        if (!is_array($couponList) || empty($couponList)) {
            $couponList = array();
        }
        $resultJson = array(
            'couponList' => $couponList,
            'couponListCount' => empty($couponList) ? 0 : count($couponList),
            'usertoken' => $data['token'],
            'discountCenterUrl' => (new \core\service\conf\ApiConfService())->getDiscountCenterUrl(2),
        );
        $this->json_data = $resultJson;
    }
}

