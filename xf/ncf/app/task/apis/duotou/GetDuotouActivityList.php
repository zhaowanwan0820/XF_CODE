<?php

namespace task\apis\duotou;

use libs\utils\Logger;
use task\lib\ApiAction;
use core\service\duotou\DuotouService;
use core\service\duotou\DtActivityRulesService;
use core\service\duotou\DtDealService;

class GetDuotouActivityList extends ApiAction
{
    public function invoke()
    {
        // 多投宝开始，企业站点不现实智多鑫
        if ('1' == app_conf('DUOTOU_SWITCH') && is_duotou_inner_user()) {
            // 1 project
            $response = DuotouService::callByObject(array('\NCFGroup\Duotou\Services\Project', 'getProjectEffect', array()));
            if (!$response) {
                Logger::error(implode('  |  ', array(__CLASS__, __FUNCTION__, __LINE__, ' Project getProjectEffect eror ', '系统繁忙，如有疑问，请拨打客服电话：4008909888')));
                return false;
            }
            if (0 != $response['errCode']) {
                Logger::error(implode('  |  ', array(__CLASS__, __FUNCTION__, __LINE__, ' Project getProjectEffect eror ', 'error:'.json_encode($response))));
                return false;
            }
            if (empty($response['data'])) {
                Logger::error(implode('  |  ', array(__CLASS__, __FUNCTION__, __LINE__, ' Project getProjectEffect eror data is empty ', 'error:'.json_encode($response))));
                return false;
            }
            $project = $response['data'];

            // 2 invest user number
            $vars = array(
                'projectId' => $response['data']['id'],
            );
            $investUserNumsResponse = \SiteApp::init()->dataCache->call(new DuotouService() , 'call', array(array('\NCFGroup\Duotou\Services\DealLoan', "getInvestUserNumsByProjectId", $vars)), 180);
            //$investUserNumsResponse = DuotouService::callByObject(array('\NCFGroup\Duotou\Services\DealLoan', 'getInvestUserNumsByProjectId', $request));
            $investUserNums = array();
            if ($investUserNumsResponse && (0 == $investUserNumsResponse['errCode'])) {
                $investUserNums = $investUserNumsResponse['data'];
            }

            // 3 activity_list
            $siteId = \libs\utils\Site::getId();
            $activityList = \SiteApp::init()->dataCache->call(new DtDealService(), 'getActivityIndexDeals', array($siteId), 60);
            foreach ($activityList as &$activity) {
                $activity['invest_user_num'] = intval($investUserNums[$activity['id']]);
                if (1 == $activity['lock_day']) {
                    $activity['invest_user_num'] += intval($investUserNums['0']);
                }
            }
            // 4 是否新手
            $isNewUser = 0;
            if (!empty($GLOBALS['user_info'])) {
                $dtActivityRulesService = new DtActivityRulesService();
                $isNewUser = $dtActivityRulesService->isMatchRule('loadGte3', array('userId' => $GLOBALS['user_info']['id']));
            }

            $this->json_data = array('project' => $project, 'activity_list' => $activityList, 'isNewUser' => $isNewUser);
        }
    }
}
