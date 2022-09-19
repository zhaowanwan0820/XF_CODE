<?php

namespace task\apis\deal;

use core\enum\DealEnum;
use core\service\deal\DealService;
use libs\utils\Logger;
use task\lib\ApiAction;

class GetNewUserDealList extends ApiAction
{
    public function invoke()
    {
        $params = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));

        $siteId  = $params['site_id'];
        $count   = $params['count'];
        $option = array();
        $option['deal_type'] = DealEnum::DEAL_TYPE_ALL_P2P; //标的类型(p2p)
        $deal_service = new DealService();
        $option['isHitSupervision'] = true;
        $deals = $deal_service -> getDealsList(null,1,$count,false,$siteId,$option);
        $this->json_data =  $deals['list'];
    }
}