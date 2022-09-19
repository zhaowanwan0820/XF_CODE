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
 * 网信首页获取p2p标的用
 */
class GetDealList extends ApiAction
{

    public function invoke()
    {
        $params = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));

        $cate = $params['cate'];
        $type = $params['type'];
        $field = $params['field'];
        $page = $params['page'];
        $page_size = $params['page_size'];
        $is_all_site = $params['is_all_site'];
        $site_id = $params['site_id'];
        $show_crowd_specific = $params['show_crowd_specific'];
        $dealTypes = $params['dealTypes'];
        $dealTagName = $params['dealTagName'];
        $needCount = $params['needCount'];
        $isShowP2p = $params['isShowP2p'];
        $option = $params['option'];

        $ds = new \core\service\deal\DealService();
        $deals = $ds->getList($cate, $type, $field, $page, $page_size, $is_all_site, $site_id,
            $show_crowd_specific, $dealTypes, $dealTagName, $needCount, $isShowP2p, $option);
        $this->json_data = $deals;
    }
}
