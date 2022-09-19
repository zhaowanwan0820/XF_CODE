<?php
namespace openapi\controllers\data;

use libs\web\Form;
use openapi\controllers\DataBaseAction;
use openapi\conf\Error;
use NCFGroup\Protos\Ptp\RequestUserMoneyLog;
/**
 * 资金记录列表接口
 * Class MoneyLogsWithType
 * @package api\controllers\account
 */
class MoneyLogs extends DataBaseAction
{
    /*
     *资金记录类型
     */
    private $log_type = array('充值', '提现申请', '提现失败', '投标冻结', '还本', '支付收益', '提前还款',
        '提前还款补偿金', '邀请返利', '投资返利', '转出资金', '转入资金', '注册返利', '平台贴息',
        '返现券返利', '返现券支出', '加息券返利', '加息券支出');

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            "userXid" => array("filter" => "required", "message" => "userXid is required"),
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
        $userXid = trim($data['userXid']);
        $userId = intval($this->decodeId($userXid));
        if (empty($userId)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $logInfo = (!empty($data['logType']) && in_array($data['logType'], $this->log_type)) ? trim($data['logType']) : '';
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
        $res['logTypes'] = $this->log_type;
        $this->json_data = $res;
    }
}
