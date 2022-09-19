<?php

namespace task\apis\dealload;

use core\dao\deal\DealLoadModel;
use task\lib\ApiAction;

/**
 * 获取交易次数，投资的笔数
 */
class GetDealCount extends ApiAction
{
    public function invoke()
    {
        $params = $this->getParam();

        $userId = $params['userId'];

        $ds = new DealLoadModel();

        $dealSql = "`user_id`=:userId AND `create_time`>=:time";
        $ret = $ds->countViaSlave($dealSql, array(
            ':userId'=>$userId,
            ':time'=>time() - 7*86400 - 8*3600
        ));

        $this->json_data = $ret;
    }
}