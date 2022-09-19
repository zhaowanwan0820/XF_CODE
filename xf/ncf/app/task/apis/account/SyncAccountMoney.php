<?php
/**
 * 同步账户金额
 */
namespace task\apis\account;

use task\lib\ApiAction;
use core\service\account\AccountService;

class SyncAccountMoney extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $userId = !empty($param['userId']) ? (int) $param['userId'] : 0;
        $accountType = !empty($param['accountType']) ? (int) $param['accountType'] : 0;
        if (empty($userId) || empty($accountType)) {
            return false;
        }
        $ret = AccountService::syncAccountMoney($userId, $accountType);
        $this->json_data = ['ret' => $ret];
    }
}
