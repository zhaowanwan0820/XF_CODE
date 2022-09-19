<?php

namespace api\controllers\discount;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\o2o\DiscountService;

class Mine extends AppBaseAction {
    // 对于原有的app的h5页面对应的wap页面，如果可以跳转，尝试跳转，否则更改对应的路由
    protected $redirectWapUrl = '/discount/mine';

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
            'discount_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            'consume_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            'use_status' => array('filter' => 'int', 'option' => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->user;

        $page = !empty($data['page']) ? intval($data['page']) : 1;
        // 默认取0，表示取返现券和加息券
        $discountType = isset($data['discount_type']) ? intval($data['discount_type']) : 0;
        $useStatus = isset($data['use_status']) ? intval($data['use_status']) : 1;

        $result = DiscountService::discountMine($loginUser['id'], $page, $discountType, 1, $useStatus);
        if ($result === false) {
            $this->setErr(UserService::getErrorData(), UserService::getErrorMsg());
            return false;
        }

        $result['userName'] = $loginUser['user_name'];
        $this->json_data = $result;
    }
}
