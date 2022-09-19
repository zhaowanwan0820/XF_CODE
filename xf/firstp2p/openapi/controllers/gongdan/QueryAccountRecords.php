<?php
/**
 * 大额充值收款账户资金流水查询接口
 * @date 2019/3/19
 * @author: yangshuo5@ucfgroup.com
 */

namespace openapi\controllers\gongdan;

use openapi\controllers\BaseAction;
use libs\web\Form;
use libs\utils\Logger;

class QueryAccountRecords extends BaseAction
{
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "merchantId" => array("filter" => "string"),
            "outOrderId" => array("filter" => "string"),
            "transStartTime" => array("filter" => "string", ),
            "transEndTime" => array("filter" => "string", ),
            "payAccountName" => array("filter" => "string",),
            "payAccountNo" => array("filter" => "string", ),
            "amount" => array("filter" => "string",),
            "accountNo" => array("filter" => "string",),
            "status" => array("filter" => "string",),
            "accountStartDate" => array("filter" => "string",),
            "accountEndDate" => array("filter" => "string",),
            "pageSize" => array("filter" => "required", "message" => "pageSize is required"),
            "pageNo" => array("filter" => "required", "message" => "pageNo is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if(!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        return true;
    }

    public function invoke() {
        $params = $this->form->data;
        if (empty($params['outOrderId']) && ( empty($params['transStartTime']) || empty($params['transEndTime'])) && ( empty($params['accountStartDate']) || empty($params['accountEndDate']))) {
            $this->setErr("ERR_PARAMS_VERIFY_FAIL");
            return false;
        }
        $requestParams['transStartTime'] = $params['transStartTime'] ?: '';
        $requestParams['transEndTime'] = $params['transEndTime'] ?: '';
        $requestParams['accountStartDate'] = $params['accountStartDate'] ?: '';
        $requestParams['accountEndDate'] = $params['accountEndDate'] ?: '';
        $requestParams['merchantId'] = $params['merchantId'] ?: '';
        $requestParams['outOrderId'] = $params['outOrderId'] ?: '';
        $requestParams['payAccountName'] = $params['payAccountName'] ?: '';
        $requestParams['payAccountNo'] = $params['payAccountNo'] ?: '';
        $requestParams['amount'] = $params['amount'] ?: '';
        $requestParams['accountNo'] = $params['accountNo'] ?: '';
        $requestParams['status'] = $params['status'] ?: '';
        $requestParams['pageSize'] = $params['pageSize'];
        $requestParams['pageNo'] = $params['pageNo'];
        try {
            $result = array();
            $requestResult = \SiteApp::init()
                ->dataCache
                ->call($this->rpc, 'local', ['PaymentCheckService\queryAccountRecords', [$requestParams]], 15*60, false, true);
            Logger::info('queryAccountRecords result :' . json_encode($requestResult) . ' ' . __FILE__ . ' ' . __LINE__);
            if ( !empty($requestResult)) {
                $result['allNum'] = $requestResult['pageCnt'];
                $result['recordList'] = $requestResult['pageList'];
            }
        } catch (\Exception $e) {
            Logger::error('queryAccountRecordsError:'.$e->getMessage());
            $result = [];
        }

        $this->json_data = $result;
    }

}
