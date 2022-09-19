<?php
/**
 * 账户信息
 */
namespace task\apis\account;

use task\lib\ApiAction;
use core\service\account\AccountService;

class GetInfoByIds extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $accountIds = !empty($param['accountIds']) ? explode(',', $param['accountIds']) : [];
        $syncStatus = isset($param['syncStatus']) ? (int) $param['syncStatus'] : 0;
        if (empty($accountIds)) {
            return false;
        }
        $data = AccountService::getInfoByIds($accountIds, $syncStatus);
        $this->json_data = $data;
    }
}
