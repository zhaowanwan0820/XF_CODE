<?php

namespace core\service\ncfph;

use libs\utils\Logger;
use NCFGroup\Common\Library\ApiService;

class DealService
{

    public static function getIndexList($page,$pageSize)
    {
        $params = compact('page', 'pageSize');
        Logger::info('DealService-getIndexList:'. json_encode($params));
        $result = ApiService::rpc("ncfph", "deal/IndexList", $params);
        return $result;
    }

    public static function getNongdanList($page,$pageSize,$siteIdStr){
        $params = compact('page', 'pageSize','siteIdStr');
        Logger::info('DealService-getNongdanList:'.json_encode($params));
        return ApiService::rpc("ncfph", "deal/NongdanList", $params);
    }

    public static function getNdList($page,$pageSize,$siteIdStr){
        $params = compact('page', 'pageSize','siteIdStr');
        Logger::info('DealService-getNdList:'.json_encode($params));
        return ApiService::rpc("ncfph", "deal/getNdList", $params);
    }

    public static function getDeal($id, $read_only=false, $hand_deal=true){
        $params = compact('id', 'read_only','hand_deal');
        Logger::info('DealService-getDeal:'.json_encode($params));
        return ApiService::rpc("ncfph", "deal/GetDeal", $params);
    }

    public static function getDealsList($type_id, $page, $page_size=0, $is_all_site=false, $site_id=0,$option=array()){
        $params = compact('type_id', 'page','page_size','is_all_site','site_id','option');
        Logger::info('DealService-getDealsList:'.json_encode($params));
        return ApiService::rpc("ncfph", "deal/GetDealsList", $params);
    }

    /**
     * 获取用户可以投资的p2p列表
     */
    public static function getDealsListForDiscount($user, $sourceType, $pageNum = 1, $pageSize = 50) {
        $params = compact('user', 'sourceType', 'pageNum', 'pageSize');
        Logger::info('DealService-getDealsListForDiscount:'.json_encode($params));
        return ApiService::rpc("ncfph", "deal/getDealsListForDiscount", $params);
    }

    public function getList($cate, $type, $field, $page, $page_size = 0, $is_all_site = false, $site_id = 0,
        $show_crowd_specific = true, $dealTypes = '', $dealTagName = '', $needCount = true, $isShowP2p = false, $option = array())
    {
        $params = compact('cate', 'type', 'field', 'page', 'page_size', 'is_all_site', 'site_id',
            'show_crowd_specific', 'dealTypes', 'dealTagName', 'needCount', 'isShowP2p', 'option');
        Logger::info('DealService-getList:'.json_encode($params));
        return ApiService::rpc("ncfph", "deal/GetDealList", $params);
    }

    public static function GetDealNum()
    {
        Logger::info('DealService-GetDealNum:');
        $params = [];
        $result = ApiService::rpc("ncfph", "deal/GetDealNum", $params);
        return $result;
    }

    /*
    * 获取新手标列表
    * @param $site_id
    * @param $count
    */
    public function getNewUserDeals($site_id=1,$count=3){
        Logger::info('DealService-GetNewUserDealList:');
        $params = compact('site_id', 'count');
        $result = ApiService::rpc("ncfph", "deal/GetNewUserDealList", $params);
        return $result;
    }

     /*
    * 获取首页新手标
    * @param $site_id
    * @param $count
    */
    public function getIndexNewUserList(){
        Logger::info('DealService-GetNewUserDealList:');
        $result = ApiService::rpc("ncfph", "deal/GetIndexNewUserList", array());
        return $result;
    }

    public function getZoneTags()
    {
        return ApiService::rpc("ncfph", "deal/GetZoneTags", []);
    }

    public function setZoneTags($tags)
    {
        return ApiService::rpc("ncfph", "deal/SetZoneTags", ['tags' => $tags]);
    }

    /**
     * @param Array $uids
     * @return float
     * @throws \Exception
     */
    public static function getUnrepayP2pMoneyByUids($uids){
        Logger::info('DealService-getUnrepayP2pMoneyByUids:');
        $params = compact('uids');
        $result = ApiService::rpc("ncfph", "deal/GetUserUnRepayMoney", $params);
        return $result;
    }

    public function getProductNameByDealId($dealId)
    {
        $params = compact('dealId');
        Logger::info('DealService-getProductNameByDealId:');
        $result = ApiService::rpc("ncfph", "deal/getProductNameByDealId", $params);
        return $result;
    }

}
