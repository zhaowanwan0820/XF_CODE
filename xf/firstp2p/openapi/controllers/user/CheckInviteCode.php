<?php

/**
 * @abstract  验证邀请码
 * @author    wangge<wangge@ucfgroup.com>
 * @date      2015-09-22
 */
namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestCheckInviteCode;

class CheckInviteCode extends BaseAction {
    
    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            "invite_code" => array("filter" => "length",   "option"  => array("min" => 1), "message" => "invite_code is required"),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $userInfo = $this->getUserByAccessToken();
        if (!is_object($userInfo) || $userInfo->resCode) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $data = $this->form->data;
        $request = new RequestCheckInviteCode();
        $request->setInviteCode($data['invite_code']);
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpUser',
            'method'  => 'checkInviteCode',
            'args'    => $request
        ));

        if (!$response->checkRes) {
            $this->setErr("ERR_COUPON_ERROR");
            return false;
        }
        
        unset($response->checkRes);
        $this->json_data = $response->toArray();

        return true;
    }

}
