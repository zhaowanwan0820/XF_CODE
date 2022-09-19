<?php
/**
 * 初始化账户
 * 生成账户Id
 */
namespace task\apis\account;

use task\lib\ApiAction;
use core\service\account\AccountService;

class InitAccount extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $userId = !empty($param['userId']) ? (int) $param['userId'] : 0;
        $accountType = !empty($param['accountType']) ? (int) $param['accountType'] : 0;
        if (empty($userId) || empty($accountType)) {
            return false;
        }
        $accountId = AccountService::InitAccount($userId, $accountType);
        if (empty($accountId)) {
            return false;
        }
        $this->json_data = ['accountId' => $accountId];
    }
}
