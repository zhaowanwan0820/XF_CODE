<?php

namespace task\apis\deal;

use core\dao\deal\DealModel;
use core\enum\DealEnum;
use core\enum\DealExtEnum;
use libs\web\Form;
use libs\utils\Page;
use libs\utils\Logger;
use core\service\deal\DealService;
use task\lib\ApiAction;

class GetDealsListForDiscount extends ApiAction {

    public function invoke()
    {
        $params = $this->getParam();

        $user       = $params['user'];
        $sourceType = $params['sourceType'];
        $pageNum    = isset($params['pageNum']) ? $params['pageNum'] : 1;
        $pageSize   = isset($params['pageSize']) ? $params['pageSize'] : 100;
        if (empty($user) || !isset($user['id']) || !isset($user['create_time'])) {
            throw new \Exception('用户参数错误');
        }
        if (empty($sourceType)) {
            throw new \Exception('数据来源参数错误');
        }

        $ds = new \core\service\deal\DealService();
        $deals = $ds->getDealsListForDiscount($user, $sourceType, $pageNum, $pageSize);
        $this->json_data = $deals;
    }
}
