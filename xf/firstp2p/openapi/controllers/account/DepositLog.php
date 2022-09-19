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
use NCFGroup\Protos\Ptp\RequestDeposit;
/**
 * 充值记录列表接口
 *
 * Class MoneyLog
 * @package api\controllers\account
 */
class DepositLog extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            "pageSize" => array("filter" => "int", "message" => "offset is error", 'option' => array('optional' => true)),
            "pageNum" => array("filter" => "int", "message" => "count is error", 'option' => array('optional' => true)),
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
        $request = new RequestDeposit();
        $request->setUserId($userId);
        $request->setPageSize(intval($data['pageSize']));
        $request->setPageNum(intval($data['pageNum']));
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpPayment',
            'method' => 'depositList',
            'args' => $request
        ));
        if ($response->resCode) {
            $this->errorCode = -1;
            $this->errorMsg = "empty list";
            return false;
        }
        $this->json_data = $response->toArray();
    }
}
