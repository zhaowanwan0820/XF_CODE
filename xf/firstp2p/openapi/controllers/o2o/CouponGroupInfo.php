<?php
/**
 * 获取优惠券组详情
 *
 * Date: 2015/6/10
 * Time: 15:56
 */
namespace openapi\controllers\o2o;

use libs\web\Form;
use openapi\controllers\BaseAction;

class CouponGroupInfo extends BaseAction {
    /**
     * 初始化
     */
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "token is required"),
            // O2O Feature 礼物ID
            'couponGroupId' => array("filter" => "required"),
            'load_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'action' => array('filter' => 'int', 'option' => array('optional' => true)),
             // O2O Feature
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByAccessToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $couponGroupId = intval($data['couponGroupId']);
        $gift_detail = $this->rpc->local('O2OService\getCouponGroupInfo', array($couponGroupId, $loginUser->userId, $data['action'], $data['load_id']));
        if ($gift_detail === false) {
            $msg = $this->rpc->local('O2OService\getErrorMsg');
            $this->setErr('ERR_COUPON_ERROR', $msg);
            return false;
        }

        $this->json_data = array(
            'coupon'=>$gift_detail,
            'action'=>$data['action'],
            'load_id'=>$data['load_id']
        );
        return true;
    }
}
