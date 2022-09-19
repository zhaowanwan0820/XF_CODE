<?php

/**
 * @abstract  获取指定用户的邀请码
 * @author    wangge<wangge@ucfgroup.com>
 * @date      2015-10-28
 */
namespace openapi\controllers\account;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\ProtoUser;

class GetInviteCode extends BaseAction {

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            "uid" => array("filter" => "reg", "option" => array("regexp" => '/^\d+$/'), "message" => "uid is required"),
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

        $request = new ProtoUser();
        $request->setUserId(intval($data['uid']));
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpCoupon',
            'method'  => 'getInviteCode',
            'args'    => $request
        ));

        if ($response->resCode) {
            $this->json_data = array(
               'code' => $response->getCoupon(),
               'rebate_ratio' => $response->getRebateRatio(),
            );
            return true;
        }

        $this->setErr("ERR_MANUAL_REASON", $response->resMsg);
        return false;
    }

}
