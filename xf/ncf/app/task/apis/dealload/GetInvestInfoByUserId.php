<?php

namespace task\apis\dealload;


use core\dao\deal\DealLoadModel;
use task\lib\ApiAction;

/**
 * 根据user_id 获取投资人某段时间内累计投资总额,投资次数
 */
class GetInvestInfoByUserId extends ApiAction
{
    public function invoke()
    {
        $params = $this->getParam();

        $userId = $params['userId'];
        $startTime = $params['startTime'];
        $endTime = $params['endTime'];

        $ds = new DealLoadModel();

        $sql = "SELECT SUM(money) AS total, COUNT(*) AS investTimes FROM %s WHERE user_id=':user_id' ";
        $param = array(':user_id' => $userId);
        if($startTime){
            $sql .= " AND create_time >= ':date_start'";
            $param [':date_start'] = $startTime;
        }
        if($endTime){
            $sql .= " AND create_time < ':date_end'";
            $param [':date_end'] = $endTime;
        }

        $sql = sprintf($sql, $ds->tableName());
        $rst = $ds->findBySql($sql, $param, true);
        $result = array(
            'total' => empty($rst['total'])? 0 : $rst['total'],
            'investTimes' => empty($rst['investTimes'])? 0 : $rst['investTimes'],
        );

        $this->json_data = $result;

    }
}