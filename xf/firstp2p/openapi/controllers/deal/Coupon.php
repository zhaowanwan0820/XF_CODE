<?php

/**
 * Coupon.php
 *
 * @date 2015-06-01
 * @author zhaohui3 <zhaohui3@ucfgroup.com>
 */

namespace openapi\controllers\deal;

use libs\web\Form;
use openapi\controllers\BaseAction;
use openapi\conf\Error;

/**
 * 优惠码校验接口
 *
 * Class Coupon
 * @package openapi\controllers\deal
 */
class Coupon extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "coupon" => array("filter" => "required", "message" => "coupon is required"),
            "dealid" => array("filter" => "required", "message" => "dealid is required"),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        if (empty($this->form->data['coupon'])) {
            $this->setErr("ERR_PARAMS_ERROR", "coupon is error");
            return false;
        }

        //var_dump($this->form->data);
    }

    public function invoke() {
        $params = $this->form->data;

        $request = new \NCFGroup\Protos\Ptp\RequestCoupon();

        try {
            $request->setCoupon($params['coupon']);
            $request->setDealid($params['dealid']);
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }

        $response = $GLOBALS['rpc']->callByObject(array(
                'service' => 'NCFGroup\Ptp\services\PtpCoupon',
                'method' => 'queryCoupon',
                'args' => $request
        ));


        $result = array();

        if (!$response['resCode']) {

            $data = $response['data'];
            if (!$data['is_effect']) {
                $this->setErr("ERR_COUPON_EFFECT");
                return false;
            }

            $result['coupon'] = $data['short_alias'];
            $result['remark'] = $data['remark'];
            $result['rate'] =  $data['rebate_ratio_show'] . '%';
        } else {
            $this->setErr("ERR_COUPON_ERROR");
            return false;
        }
        $this->json_data = $result;
        return true;
    }

}
