<?php
/**
 * 网贷账户日充值金额
 */
namespace task\apis\account;

use task\lib\ApiAction;
use core\dao\supervision\SupervisionChargeModel;

class GetDayChargeAmount extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $accountId = !empty($param['accountId']) ? (int) $param['accountId'] : 0;

        if (empty($accountId)) {
            $result = false;
        }

        $result = SupervisionChargeModel::instance()->sumUserOnlineChargeAmountToday($accountId);
        $this->json_data = $result;
    }
}
