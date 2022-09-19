<?php
/**
 * Index controller class file.
 *
 * @author 赵辉<zhaohui3@ucfgroup.com>
 * @date   2016-07-26
 **/

namespace api\controllers\duotou;

use libs\web\Form;
use api\controllers\DuotouBaseAction;
use core\service\duotou\DtDealService;
use core\service\duotou\DtActivityRulesService;
use core\service\duotou\DuotouService;
use libs\utils\Logger;

/**
 * 多投首页标的列表
 *
 * @packaged default
 * @author 赵辉<zhaohui3@ucfgroup.com>
 **/
class Index extends DuotouBaseAction {
    // 是否需要授权
    protected $needAuth = false;

    public function init() {
        parent::init();
        $this->form = new Form();

        $this->form->rules = array(
            'token' => array('filter' => 'string'),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $userInfo = $this->getUserByToken(false);
        $iUserId = 0;
        if ($userInfo) {
            $iUserId = $userInfo['id'];
        }

        $oDtDealService = new DtDealService();
        $response = \SiteApp::init()->dataCache->call($oDtDealService, 'getIndexDeal', array($iUserId), 60);
        if (!$response) {
            $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
            return false;
        }

        if ($response['errCode'] != 0) {
            Logger::error("errCode:".$response['errCode']." errMsg:".$response['errMsg']);
            $this->setErr("ERR_SYSTEM",$response['errMsg']);
            return false;
        }

        if (empty($response['data'])) {
            $res = array();
        } else {
            $minLoanMoney = $response['data']['singleMinLoanMoney'];
            $siteId = \libs\utils\Site::getId();
            $params = array($siteId);
            //目前没有这个表
            $activityList = \SiteApp::init()->dataCache->call($oDtDealService, 'getActivityIndexDeals', $params, 60);
            if(empty($activityList)) {
                $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
                return false;
            }
            $rateYear = !empty($response['data']['rateYear']) ? $response['data']['rateYear'] : 0;
            $rateYearBase = !empty($response['data']['rateYearBase']) ? $response['data']['rateYearBase'] : 0;

            $vars = array(
                'projectId' => $response['data']['id'],
            );
            $investUserNumsResponse = \SiteApp::init()->dataCache->call(new DuotouService() , 'call', array(array('\NCFGroup\Duotou\Services\DealLoan', "getInvestUserNumsByProjectId", $vars)), 180);
            //$investUserNumsResponse = $this->callByObject(array('\NCFGroup\Duotou\Services\DealLoan', "getInvestUserNumsByProjectId", $vars));
            $investUserNums = array();
            if($investUserNumsResponse && ($investUserNumsResponse['errCode'] == 0)) {
                $investUserNums = $investUserNumsResponse['data'];
            }

            $isNewUser = 0;
            if(!empty($userInfo)){
                $oDtActivityRulesService = new DtActivityRulesService();
                $isNewUser = $oDtActivityRulesService->isMatchRule('loadGte3', array('userId'=>$userInfo['id']));
            }

            foreach ($activityList as & $activity) {
                if(bccomp($activity['max_rate'],$rateYear,2) == 1) {
                    $rateYear = $activity['max_rate'];
                }
                if(bccomp($rateYearBase,$activity['min_rate'],2) == 1) {
                    $rateYearBase = $activity['min_rate'];
                }

                $activity['invest_user_num'] = intval($investUserNums[$activity['id']]);
                $activity['rate_year_tag'] = '往期年化';
                if($activity['lock_day'] == 1) {
                    $activity['invest_user_num'] += intval($investUserNums['0']);
                    $activity['duration']  = ['t1' => '期限', 't2' => $activity['lock_day'], 't3' => '天可申请转让/退出'];
                 } else {
                    $activity['duration']  = ['t1' => '期限', 't2' => $activity['lock_day'], 't3' => '天可申请转让/退出'];
                }

                if( $activity['invest_user_num'] >= 10000){
                    $num =  floor($activity['invest_user_num']/100);
                    $activity['invest_user_num'] = number_format($num/100,2)."万";
                }

                // 满足新用户规则直接覆盖以前字段、这样app就不用发版了
                $activity['min_invest_money'] = ($isNewUser && $activity['new_user_min_invest_money'] > 0) ? $activity['new_user_min_invest_money'] : $activity['min_invest_money'];
            }

            $res = array(//智多鑫三期
                'rateYear' => number_format($rateYear,2).'%',// 年化收益率
                'rateYearBase' => number_format($rateYearBase,2).'%',// 基础年化收益率
                'minLoanMoney' => number_format($minLoanMoney,2,'.',''),// 最低投资金额
                'name' => $response['data']['name'],//项目名称
                'projectId' => $response['data']['id'],//项目ID
                'investUserNum' => $response['data']['peopleCount'],//当前加入的人数
                'interestStartTag' => '按日计算利息/收益',
                'rateYearTag' => '最高预计年化利率/收益率',
                'tagBeforeName' => !empty($response['data']['tagBeforeName']) ? $response['data']['tagBeforeName'] : '',
                'tagAfterName' => !empty($response['data']['tagAfterName']) ? $response['data']['tagAfterName'] : '',
                'tagBeforeDesc' => !empty($response['data']['tagBeforeDesc']) ? $response['data']['tagBeforeDesc'] : '',
                'tagAfterDesc' => !empty($response['data']['tagAfterDesc']) ? $response['data']['tagAfterDesc'] : '',
                'duration' => ['t1' => '最低期限', 't2' => '灵活转让', 't3' => ''],
                'activityList' => $activityList,  //活动列表
            );
        }

        $this->json_data = $res;
    }

}
