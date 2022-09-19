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

class IndexList extends ApiAction
{

    public function invoke()
    {
        $params = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));

        $page = intval($params['page']);
        $pageSize = intval($params['pageSize']);

        $option = array();

        $this->json_data = array('list' => array());
        
//        $dao = new DealModel();
//        $deals = $dao->getListV2(null, false, $page, $pageSize, FALSE, TRUE, 0, $option, false, false, false, false);
//        $ds = new DealService();
//        $result = $ds->handleDealForList($deals['list']);
//        $this->json_data = $result;
    }
}