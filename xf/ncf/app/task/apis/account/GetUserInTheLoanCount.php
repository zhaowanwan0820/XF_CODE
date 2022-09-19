<?php
/**
 * 获取借款用户在途未还清的标的数量
 */
namespace task\apis\account;

use task\lib\ApiAction;
use core\dao\deal\DealModel;

class GetUserInTheLoanCount extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $userId = !empty($param['userId']) ? (int) $param['userId'] : 0;
        if (empty($userId)) {
            return false;
        }
        $count = DealModel::instance()->getUserInTheLoanCount($userId);
        $this->json_data = $count;
    }
}
