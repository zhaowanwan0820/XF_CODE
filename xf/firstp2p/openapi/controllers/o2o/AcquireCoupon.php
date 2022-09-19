<?php
/**
 * 优惠券领取接口
 *
 * Date: 2015/6/10
 * Time: 15:56
 */
namespace openapi\controllers\o2o;

use libs\web\Form;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use openapi\controllers\BaseAction;

class AcquireCoupon extends BaseAction {
    /**
     * 初始化
     */
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "token is required"),
            // O2O Feature 礼物ID
            'couponGroupId' => array("filter" => "required", "message"=>"coupon group id is error"),
            'action' => array("filter" => "required", "message"=>"action is error"),
            'load_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'deal_type' => array('filter' => 'int', 'option' => array('optional' => true)),
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

        $loadId = intval($data['load_id']);
        $dealType = isset($data['deal_type']) ? $data['deal_type'] : CouponGroupEnum::CONSUME_TYPE_P2P;
        $action = intval($data['action']);
        $couponGroupId = $data['couponGroupId'];
        $user_id = $loginUser->userId;
        // 根据load_id信息获取触发券组列表校验groupId，防止前端篡改groupId
        $triggerParams = array($user_id, $action, $loadId, $dealType);
        $couponGroupList = $this->rpc->local('O2OService\getCouponGroupList', $triggerParams);
        if (empty($couponGroupList) || !isset($couponGroupList[$couponGroupId])) {
            // 非法操作
            $msg = '抢光了！下次要尽早哦！';
            $this->setErr('ERR_COUPON_ERROR', $msg);
            return false;
        }

        $rpcParams = array($couponGroupId, $loginUser->userId, $action, $loadId, $dealType);
        $gift = $this->rpc->local('O2OService\acquireCoupon', $rpcParams);
        if ($gift === false) {
            $msg = $this->rpc->local('O2OService\getErrorMsg');
            $this->setErr('ERR_COUPON_ERROR', $msg);
            return false;
        }

        $this->json_data = $gift;
        return true;
    }
}
