<?php

namespace task\apis\duotou;

use task\lib\ApiAction;
use core\service\duotou\DtDealService;
use core\service\duotou\DtActivityRulesService;
use core\service\duotou\DuotouService;
use libs\utils\Logger;

/**
 * 网信首页智多鑫标的用
 */
class GetIndexDuotouList extends ApiAction {

    public function invoke() { 
        $params = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));
    
        $userId = $params['userId'];
        $siteId = $params['site_id'];
     
        $iUserId = 0;
        if ($userId) {
            $iUserId = $userId;
        }
     
        $oDtDealService = new DtDealService();
        $response = \SiteApp::init()->dataCache->call($oDtDealService, 'getIndexDeal', array($iUserId), 60);
        
        if (!$response) {
            return false;
        }

        if ($response['errCode'] != 0) {
            Logger::error("errCode:" . $response['errCode'] . " errMsg:" . $response['errMsg']);
            return false;
        }
        if (empty($response['data'])) {
            $res = array();
        } else {
          
            $params = array($siteId);
            $activityList = \SiteApp::init()->dataCache->call($oDtDealService, 'getActivityIndexDeals', $params, 60);                 
            if (empty($activityList)) {
                return false;
            }
            $rateYear = !empty($response['data']['rateYear']) ? $response['data']['rateYear'] : 0;
            $rateYearBase = !empty($response['data']['rateYearBase']) ? $response['data']['rateYearBase'] : 0;

            $vars = array(
                'projectId' => $response['data']['id'],
            );
            $investUserNumsResponse = \SiteApp::init()->dataCache->call(new DuotouService(), 'call', array(array('\NCFGroup\Duotou\Services\DealLoan', "getInvestUserNumsByProjectId", $vars)), 180);
            //$investUserNumsResponse = $this->callByObject(array('\NCFGroup\Duotou\Services\DealLoan', "getInvestUserNumsByProjectId", $vars));
            $investUserNums = array();
            if ($investUserNumsResponse && ($investUserNumsResponse['errCode'] == 0)) {
                $investUserNums = $investUserNumsResponse['data'];
            }

            $isNewUser = 0;
            if (!empty($iUserId)) {
                $oDtActivityRulesService = new DtActivityRulesService();
                $isNewUser = $oDtActivityRulesService->isMatchRule('loadGte3', array('userId' => $iUserId));
            }
       
            foreach ($activityList as & $activity) {
                if (bccomp($activity['max_rate'], $rateYear, 2) == 1) {
                    $rateYear = $activity['max_rate'];
                }
                if (bccomp($rateYearBase, $activity['min_rate'], 2) == 1) {
                    $rateYearBase = $activity['min_rate'];
                }

                $activity['invest_user_num'] = intval($investUserNums[$activity['id']]);
                $activity['rate_year_tag'] = '往期年化';
                if ($activity['lock_day'] == 1) {
                    $activity['invest_user_num'] += intval($investUserNums['0']);
                    $activity['duration'] = ['t1' => '期限', 't2' => $activity['lock_day'], 't3' => '天可申请转让/退出'];
                } else {
                    $activity['duration'] = ['t1' => '期限', 't2' => $activity['lock_day'], 't3' => '天可申请转让/退出'];
                }

                // 满足新用户规则直接覆盖以前字段、这样app就不用发版了
                $activity['min_invest_money'] = ($isNewUser && $activity['new_user_min_invest_money'] > 0) ? $activity['new_user_min_invest_money'] : $activity['min_invest_money'];            }
        }
        $this->json_data = $activityList;
    }

}
