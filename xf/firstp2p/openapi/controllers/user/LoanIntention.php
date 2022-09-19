<?php

/**
 * @abstract  借款邀请码验证
 * @author    wangge<wangge@ucfgroup.com>
 * @date      2015-10-27
 */
namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestLoanIntention;

class LoanIntention extends BaseAction {

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            "invite_code" => array("filter" => "length", "option" => array("min" => 1), "message" => "invite_code is required"),
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

        $request = new RequestLoanIntention();
        $request->setUserId($userInfo->getUserId());
        $request->setInviteCode($data['invite_code']);
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpLoanIntention',
            'method'  => 'checkInviteCode',
            'args'    => $request
        ));
        if (!$response->resCode) {
            $this->json_data = array(
                "code"      => $data['invite_code'],
                "realName"  => $userInfo->getRealName(),
                "idNo"      => $userInfo->getIdno(),
                "mobile"    => $userInfo->getMobile(),
                "allAmount" => format_price($response->resExt['principal']),
                // type=1 变现通   type=2 消费贷
                "type"      => $response->resExt['type'],
                "max_money" => $response->maxMoney,
                "mini_borrow_money" => $response->resExt['mini_borrow_money'],
            );
            return true;
        }

        $this->setErr("ERR_MANUAL_REASON", $response->resMsg);
        return false;
    }

}
