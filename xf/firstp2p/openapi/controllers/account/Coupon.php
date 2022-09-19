<?php

/**
 * Coupon.php opanapi
 * @abstract  我的邀请码openapi接口
 * @author yutao <yutao@ucfgroup.com>
 */

namespace openapi\controllers\account;

use libs\web\Form;
use openapi\controllers\BaseAction;

/**
 * 我的优惠码接口
 *
 * Class Coupon
 */
class Coupon extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpCoupon',
            'method' => 'getUserCoupon',
            'args' => $userInfo
        ));

        if ($response->resCode) {
            $this->errorCode = -1;
            $this->errorMsg = "get user coupon failed";
            return false;
        }

        $this->json_data = $response->toArray();
    }
}
