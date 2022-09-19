<?php
/**
 * 网贷账户限制提现规则 增加可提现额度金额
 */
namespace task\apis\account;

use task\lib\ApiAction;
use core\service\account\AccountLimitService;

class AddRemainMoney extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $limitId = !empty($param['id']) ? (int) $param['id'] : 0;
        $amount = !empty($param['withdrawAmount']) ? (int) $param['withdrawAmount'] : 0;
        $accountLimitService = new AccountLimitService();
        $result = $accountLimitService->addRemainMoney($limitId, $amount);
        $this->json_data = $result;
    }
}
