<?php

namespace core\service\ncfph;

use libs\utils\Logger;
use NCFGroup\Common\Library\ApiService;

class DealLoadService
{

    /**
     * 用户是否投资过
     */
    public static function getFirstDealByUser($userId)
    {
        $params = compact('userId');
        Logger::info(__CLASS__.' | '.__FUNCTION__.' | '.json_encode($params));
        $result = ApiService::rpc("ncfph", "dealload/GetFirstDealByUser", $params);
        return $result;
    }

    public static function getUserTodayLoadMoneyStat($userId){
        $params = compact('userId');
        Logger::info(__CLASS__.' | '.__FUNCTION__.' | '.json_encode($params));
        return ApiService::rpc("ncfph", "dealload/GetUserTodayLoadMoneyStat", $params);
    }

    /**
     * 根据开始时间查询
     * @param $userId
     * @param $startTime
     * @return mixed
     * @throws \Exception
     */
    public static function getUserLoadMoneyStat($userId, $startTime){
        $params = compact('userId','startTime');
        Logger::info(__CLASS__.' | '.__FUNCTION__.' | '.json_encode($params));
        return ApiService::rpc("ncfph", "dealload/GetUserLoadMoneyStat", $params);
    }

    public static function isTodayLoadByUserId($userId){
        $params = compact('userId');
        Logger::info(__CLASS__.' | '.__FUNCTION__.' | '.json_encode($params));
        return ApiService::rpc("ncfph", "dealload/IsTodayLoadByUserId", $params);
    }

    public static function getO2ODealLoadInfo($dealLoadId) {
        $params = compact('dealLoadId');
        return ApiService::rpc("ncfph", "dealload/GetO2ODealLoadInfo", $params);
    }

    public static function getUserLoadMoreTenThousand($userId){
        $params = compact('userId');
        Logger::info(__CLASS__.' | '.__FUNCTION__.' | '.json_encode($params));
        return ApiService::rpc("ncfph", "dealload/GetUserLoadMoreTenThousand", $params);
    }

    public static function getDealCount($userId){
        $params = compact('userId');
        Logger::info(__CLASS__.' | '.__FUNCTION__.' | '.json_encode($params));
        return ApiService::rpc("ncfph", "dealload/GetDealCount", $params);
    }

    public static function getInvestInfoByUserId($userId, $startTime = false, $endTime = false){
        $params = compact('userId','startTime','endTime');
        Logger::info(__CLASS__.' | '.__FUNCTION__.' | '.json_encode($params));
        return ApiService::rpc("ncfph", "dealload/GetInvestInfoByUserId", $params);
    }
}
