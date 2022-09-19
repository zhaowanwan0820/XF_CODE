<?php
/**
 * 账户列表
 */
namespace task\apis\account;

use task\lib\ApiAction;
use core\service\account\AccountService;

class GetListByUserIds extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $userIds = !empty($param['userIds']) ? explode(',', $param['userIds']) : [];
        $syncStatus = isset($param['syncStatus']) ? (int) $param['syncStatus'] : 0;
        if (empty($userIds)) {
            return false;
        }
        $list = AccountService::getListByUserIds($userIds, $syncStatus);
        $this->json_data = $list;
    }
}
