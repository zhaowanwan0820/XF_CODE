<?php
/**
 * openapi优惠券access_token接口
 *
 * Date: 2016年03月24日
 */
namespace openapi\controllers\coupon;

use libs\web\Form;
use openapi\controllers\BaseAction;

class GetAccessToken extends BaseAction {
    /**
     * 初始化
     */
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "token is required"),
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
        $couponAccessToken = $this->GetCouponAccessToken($oauth_token, $loginUser->userId);
        $this->json_data = array('accessToken' => $couponAccessToken);
        return true;
    }
}
