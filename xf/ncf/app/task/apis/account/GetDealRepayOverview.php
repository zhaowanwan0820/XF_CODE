<?php
/**
 * 账户资金
 */
namespace task\apis\account;

use task\lib\ApiAction;
use core\service\account\AccountService;


class GetDealRepayOverview extends ApiAction
{
    public function invoke()
    {
        /**
         * 此方法已废弃
         */

        return true;
        $this->json_data = $param;
        $param  = $this->getParam();
        $userId = !empty($param['userId']) ? (int) $param['userId'] : 0;
        $data = AccountService::getDealRepayOverview($userId);
        $this->json_data = $data;
    }
}
