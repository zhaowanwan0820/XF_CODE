<?php
/**
 * 读取网贷账户限制提现规则
 */
namespace task\apis\account;

use task\lib\ApiAction;
use core\service\account\AccountLimitService;

class GetAllLimits extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $accountId = !empty($param['accountId']) ? (int) $param['accountId'] : 0;
        $accountLimitService = new AccountLimitService();
        $result = $accountLimitService->GetAllLimits($accountId);
        $this->json_data = $result;
    }
}
