<?php
/**
 * 网贷账户限制提现规则 更新可提现额度
 */
namespace task\apis\account;

use task\lib\ApiAction;
use core\service\account\AccountLimitService;

class UpdateLimitRecord extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $limitId = !empty($param['limitId']) ? (int) $param['limitId'] : 0;
        $accountLimitService = new AccountLimitService();
        $result = $accountLimitService->updateWithdrawLimitRecord($limitId);
        $this->json_data = $result;
    }
}
