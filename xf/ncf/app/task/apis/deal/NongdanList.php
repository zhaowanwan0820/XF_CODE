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

class NongdanList extends ApiAction
{

    public function invoke()
    {
        $params = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));

        $page = intval($params['page']);
        $pageSize = intval($params['pageSize']);
        $siteIdStr = $params['siteIdStr'];

        $option = array();


        $deals = DealModel::instance()->getList(null, false, $page, $pageSize, FALSE, TRUE, $siteIdStr, $option, true, false, false, false);
        $result['list'] = $this->handleDealForList($deals['list']);
        $this->json_data = $result;
    }

    private function handleDealForList($list)
    {
        $deal_list = array();

        if ($list) {
            foreach ($list as $key => $deal) {
                $list[$key] = DealModel::instance()->handleDealNew($deal, 1);
            }
            $deal_list['list'] = $list;
        } else {
            $deal_list['list'] = array();
        }

        return $deal_list;
    }
}