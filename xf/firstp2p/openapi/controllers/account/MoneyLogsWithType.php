<?php
/**
 * MoneyLogsWithType.php
 * 资金记录，可筛选资金类型&时间
 * @author longbo
 * @date 2016-03-07 17:06:58 CST
 */

namespace openapi\controllers\account;

use libs\web\Form;
use openapi\controllers\BaseAction;
use openapi\conf\Error;
use NCFGroup\Protos\Ptp\RequestUserMoneyLog;
/**
 * 资金记录列表接口
 *
 * Class MoneyLogsWithType
 * @package api\controllers\account
 */
class MoneyLogsWithType extends BaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            "logType" => array("filter" => "string", "message" => "log_type is error", 'option' => array('optional' => true)),
            "beginTime" => array("filter" => "int", "message" => "beginTime is error", 'option' => array('optional' => true)),
            "endTime" => array("filter" => "int", "message" => "endTime is error", 'option' => array('optional' => true)),
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
        $logInfo = !empty($data['logType']) ? trim($data['logType']) : '';
        $offset = !empty($data['offset']) ? intval($data['offset']) : 0;
        $count = !empty($data['count']) ? intval($data['count']) : 20;

        $beginTime = intval($data['beginTime'] - 8 * 3600);
        $endTime = intval($data['endTime'] - 8 * 3600);
        $beginTime = $beginTime > 0 ? $beginTime : 0;
        $endTime = $endTime > 0 ? $endTime : 0;


        $request = new RequestUserMoneyLog();
        $request->setUserId($userId);
        $request->setBeginTime($beginTime);
        $request->setEndTime($endTime);
        $request->setLogInfo($logInfo);
        $request->setOffset($offset);
        $request->setCount($count);
        $request->setMoneyType('money');
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpUser',
            'method' => 'getMoneyLogByUid',
            'args' => $request
        ));
        if ($response->resCode) {
            $this->errorCode = -1;
            $this->errorMsg = "get user money log failed";
            return false;
        }
        $res = $response->toArray();
        $this->json_data = $res;
    }
}
