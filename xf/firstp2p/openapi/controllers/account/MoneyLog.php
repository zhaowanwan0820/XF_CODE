<?php
/**
 * MoneyLogDetail
 *
 * @date 2014-10-30
 * @author wangjiansong <wangjiansong@ucfgroup.com>
 */

namespace openapi\controllers\account;


use libs\web\Form;
use openapi\controllers\BaseAction;
use openapi\conf\Error;
use NCFGroup\Protos\Ptp\RequestUserMoneyLog;
/**
 * 资金记录列表接口
 *
 * Class MoneyLog
 * @package api\controllers\account
 */
class MoneyLog extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            "offset" => array("filter" => "int", "message" => "offset is error", 'option' => array('optional' => true)),
            "count" => array("filter" => "int", "message" => "count is error", 'option' => array('optional' => true)),
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
        $userId = $userInfo->getUserId();
        $offset = !empty($data['offset'])?intval($data['offset']):0;
        $count = !empty($data['count']) ?intval($data['count']):20;

        $request = new RequestUserMoneyLog();
        $request->setUserId($userId);
        $request->setOffset($offset);
        $request->setCount($count);
        $request->setMoneyType('money_only');
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpUser',
            'method' => 'getMoneyLogByUid',
            'args' => $request
        ));
        if ($response->resCode) {
            $this->errorCode = -1;
            $this->errorMsg = "get user coupon failed";
            return false;
        }
        $this->json_data = $response->toArray();
    }
}
