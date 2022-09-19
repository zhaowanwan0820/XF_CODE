<?php

/**
 * Coupon.php
 *
 * @date 2014-03-27
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\AppBaseAction;
use api\conf\Error;

/**
 * 优惠码校验接口
 *
 * Class Coupon
 * @package api\controllers\deal
 */
class Coupon extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "id" => array("filter" => "required", "message" => "id is required"),
            "pid" => array("filter" => "required", "message" => "pid is required"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        if (empty($this->form->data['id'])) {
            $this->setErr("ERR_PARAMS_ERROR", "id is error");
            return false;
        }
        $this->form->data['id'] = addslashes($this->form->data['id']);
        $this->form->data['id'] = strtoupper($this->form->data['id']);
    }

    public function invoke() {
        $shortAlias = $this->form->data['id'];
        $deal_id = $this->form->data['pid'];
        $rspn = $this->rpc->local('CouponService\queryCoupon', array($shortAlias, true, $deal_id));
        $result = array();
        if (!empty($rspn)) {
            if (!$rspn['is_effect']) {
                $this->setErr("ERR_COUPON_EFFECT");
            }else if($rspn['coupon_disable']){
                $this->setErr("ERR_COUPON_DISABLE");
            }
            $result['coupon'] = $shortAlias;
            $result['remark'] = $rspn['remark'];
            $result['rate'] = number_format($rspn['rebate_ratio'], 2) . '%';
        } else {
            $this->setErr("ERR_COUPON_ERROR");
        }
        $this->json_data = $result;
    }

}
