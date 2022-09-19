<?php

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use api\conf\ConstDefine;
use core\service\CouponService;

class LatestFix extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => '登录过期，请重新登录'),
            'pid' => array("filter" => 'required', 'message' => "pid is required"),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_MANUAL_REASON', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        //$loginUser = $this->rpc->local('UserService\getUser', array(666));
        //$GLOBALS['user_info'] = $loginUser;
        $couponLatest = $this->rpc->local('CouponService\getCouponLatest', array($loginUser['id'], $data['pid']));
        if(empty($couponLatest))
        {
            $couponLatest['is_fixed'] = true;
        }

        $data = array();
        $data['is_binded'] = $couponLatest['is_fixed'];
        if (!empty($couponLatest)) {
            $coupon = $couponLatest['coupon'];
            if (!empty($coupon)) {
                $data['coupon'] = $coupon['short_alias'];
                $data['remark'] = $coupon['remark'];
            }
            elseif($couponLatest['is_fixed'])
            {
                $data['coupon'] = CouponService::SHORT_ALIAS_DEFAULT;
                $data['remark'] = "";
            }
        }
        $this->json_data = $data;
    }
}
