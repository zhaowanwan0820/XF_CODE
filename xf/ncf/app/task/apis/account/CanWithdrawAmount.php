<?php
/**
 * 网贷账户限制提现
 */
namespace task\apis\account;

use task\lib\ApiAction;
use core\service\account\AccountLimitService;

class CanWithdrawAmount extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $userId = !empty($param['userId']) ? (int) $param['userId'] : 0;
        $amount = !empty($param['amount']) ? (int) $param['amount'] : 0;
        $platform = 1;
        $accountType = !empty($param['accountType']) ? (int) $param['accountType'] : 0;
        $useBonus = isset($param['useBonus']) ? $param['useBonus'] : true;
        $bonusInfo = !empty($param['bonusInfo']) ? $param['bonusInfo'] : [];

        $result = true;
        do {
            if (empty($userId) || empty($accountType)) {
                $result = false;
                break;
            }

            $accountLimitService = new AccountLimitService();
            $result = $accountLimitService->canWithdrawAmount($userId, $amount, $platform, $accountType, $useBonus, $bonusInfo);
        } while (false);
        $this->json_data = $result;
    }
}
