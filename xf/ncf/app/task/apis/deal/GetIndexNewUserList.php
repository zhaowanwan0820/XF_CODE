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

/**
 * 网信首页获取新手标的用
 */
class GetIndexNewUserList extends ApiAction
{

    public function invoke()
    {
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));

        $ds = new \core\service\deal\DealService();
        $deals = $ds->getIndexNewUserList();
        $this->json_data = $deals;
    }
    
}
