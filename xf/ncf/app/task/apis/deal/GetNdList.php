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

class GetNdList extends ApiAction
{

    public function invoke()
    {
        $params = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));

        $page = intval($params['page']);
        $pageSize = intval($params['pageSize']);
        $siteIdStr = $params['siteIdStr'];

        $option = array();


        $ds = new \core\service\deal\DealService();
        $deals = $ds->getDealsList(null, $page, $pageSize=0, false, $siteIdStr,array(),true);
        $this->json_data = $deals;
    }
}