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

class GetDeal extends ApiAction
{

    public function invoke()
    {
        $params = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));

        $id = intval($params['id']);
        $read_only=  $params['read_only'];
        $hand_deal = $params['hand_deal'];
        $ds = new \core\service\deal\DealService();
        $deals = $ds->getDeal($id,$read_only,$hand_deal);
        $deals = (empty($deals) || is_array($deals))? $deals : $deals->getRow();
        $this->json_data = $deals;
    }
}
