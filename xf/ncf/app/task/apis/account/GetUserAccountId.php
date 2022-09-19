<?php
/**
 * 获取账户Id
 * 只返回开通或未激活账户
 */
namespace task\apis\account;

use task\lib\ApiAction;
use core\service\account\AccountService;

class GetUserAccountId extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $userId = !empty($param['userId']) ? (int) $param['userId'] : 0;
        $accountType = !empty($param['accountType']) ? (int) $param['accountType'] : 0;
        if (empty($userId) || empty($accountType)) {
            return false;
        }
        $accountId = AccountService::getUserAccountId($userId, $accountType);
        if (empty($accountId)) {
            return false;
        }
        $this->json_data = ['accountId' => $accountId];
    }
}
