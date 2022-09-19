<?php

namespace core\service\ncfph;

use NCFGroup\Common\Library\ApiService;
use libs\utils\Logger;

class DuotouService
{
    public static function getDuotouActivityList()
    {
        $param = array();
        return ApiService::rpc('ncfph', 'duotou/getDuotouActivityList', $param, false);
    }

    public static function GetEntranceInfo($activityId){
        $param = compact('activityId', 'type');
        return ApiService::rpc('ncfph', 'duotou/getEntranceInfo', $param, false);
    }
    
    public static function getIndexDuotouList($userId,$site_id=0){
        $params = compact('userId','site_id');
        return ApiService::rpc("ncfph", "duotou/GetIndexDuotouList", $params);
    }

    public static function getActivityIndexDealsWithUserNum($site_id=0){
        $params = compact('site_id');
        return ApiService::rpc("ncfph", "duotou/GetActivityIndexDeals", $params);
    }
}
