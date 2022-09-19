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

class GetDealsList extends ApiAction
{

    public function invoke()
    {
        $params = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));

        $type_id        = $params['type_id'];
        $page           = $params['page'];
        $page_size      = $params['page_size'];
        $is_all_site    = $params['is_all_site'];
        $site_id        = $params['site_id'];
        $option         = $params['option'];

        $ds = new \core\service\deal\DealService();
        $deals = $ds->getDealsList($type_id, $page, $page_size, $is_all_site, $site_id,$option);
        $this->json_data = $deals;
    }
}