<?php
namespace task\apis\duotou;

use task\lib\ApiAction;
use core\service\duotou\DtDealService;

class GetActivityIndexDeals  extends ApiAction
{
    public function invoke(){
        $param = $this->getParam();
        $siteId = isset($param['siteId'])?$param['siteId']:0;
        $service = new DtDealService();
        $this->json_data = $service->getActivityIndexDealsWithUserNum($siteId);
    }
}
