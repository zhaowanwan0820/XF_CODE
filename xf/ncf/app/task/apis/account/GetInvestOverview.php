<?php
/**
 * 账户资金
 */
namespace task\apis\account;

use task\lib\ApiAction;
use core\dao\deal\DealModel;


class GetInvestOverview extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $userId = !empty($param['userId']) ? (int) $param['userId'] : 0;
        $dealModel = new DealModel();
        $data = $dealModel -> getInvestOverview($userId);
        $i = 0;
        while($i<3){
            $data[$i] = $data[$i]->_row;
            $i++;
        }
        $this->json_data = $data;
    }
}
