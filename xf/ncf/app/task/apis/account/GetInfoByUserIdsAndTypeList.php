<?php
/**
 * 账户信息
 */
namespace task\apis\account;

use task\lib\ApiAction;
use core\service\account\AccountService;

class GetInfoByUserIdsAndTypeList extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $userIds = !empty($param['userIds']) ? explode(',', $param['userIds']) : [];
        $accountTypeList = !empty($param['accountTypeList']) ? explode(',', $param['accountTypeList']) : [];
        $syncStatus = isset($param['syncStatus']) ? (int) $param['syncStatus'] : 0;
        if (empty($userIds) || empty($accountTypeList)) {
            return false;
        }
        $data = AccountService::getInfoByUserIdsAndTypeList($userIds, $accountTypeList, $syncStatus);
        $this->json_data = $data;
    }
}
