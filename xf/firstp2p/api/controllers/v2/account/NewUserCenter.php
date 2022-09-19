<?php

/**
 * NewUserCenter.php
 */

namespace api\controllers\account;

use api\controllers\AppBaseAction;
use core\service\ReservationEntraService;
use core\dao\ReservationEntraModel;
use libs\web\Form;
use libs\utils\Logger;

/**
 * 新手专区接口
 *
 * Class NewUserCenter
 * @package api\controllers\account
 */
class NewUserCenter extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                "token" => array("filter" => "string", "option" => array('optional' => true)),
                "clientInviteCode" => array("filter" => "string", "option" => array('optional' => true)),
                "siteId" => array("filter" => "int", "message" => "id error"),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;
        if(!empty($params['token'])){
            $userInfo = $this->getUserByToken();
            if (empty($userInfo)) {
                $this->setErr('ERR_GET_USER_FAIL');
                return false;
            }
        }
        $siteId = !empty($params['siteId']) ? intval($params['siteId']) : 1;
        $userId = isset($userInfo) ? $userInfo['id'] : null;
        $userStatus = $this->rpc->local('NewUserPageService\getNewUserProgress', array($userId));

        $entraService = new ReservationEntraService();
        $reservationCardList = $entraService->getReserveEntraDetailList(ReservationEntraModel::STATUS_VALID, 2);
        $reservationCards = $reservationCardList['list'];
        foreach($reservationCards as $k=>$v){
            $reservationCards[$k]['rate'] = substr($v['rate'],0,-1);
        }
        $newUserDealsCount = 3-count($reservationCards);

        $newUserDealsList = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('NewUserPageService\getNewUserDeals', array($siteId,$newUserDealsCount)), 30);

        $dealsList = array();
        foreach ($newUserDealsList as $key => $value){
            $dealsList[$key]['id'] = $value['id'];
            $dealsList[$key]['name'] = $value['name'];
            $dealsList[$key]['repayTime'] = $value['repay_time'];
            $dealsList[$key]['loanType'] = $value['loantype'];
            $dealsList[$key]['dealType'] = $value['deal_type'];
            $dealsList[$key]['rate'] = number_format($value['rate'],2);
        }

        //智多鑫标列表
        $duotouList = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DtDealService\getActivityIndexDeals',$params), 60);

        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $rpc = new \libs\utils\Rpc('duotouRpc');
        $response = $rpc->go('\NCFGroup\Duotou\Services\Project', "getProjectEffect", $request);

        $request->setVars(array('projectId' => $response['data']['id']));
        $investUserNumsResponse = \SiteApp::init()->dataCache->call($rpc, 'go', array('\NCFGroup\Duotou\Services\DealLoan', "getInvestUserNumsByProjectId", $request), 180);
        //$investUserNumsResponse = $rpc->go('\NCFGroup\Duotou\Services\DealLoan', "getInvestUserNumsByProjectId", $request);
        $investUserNums = array();
        if($investUserNumsResponse && ($investUserNumsResponse['errCode'] == 0)) {
            $investUserNums = $investUserNumsResponse['data'];
        }

        $isNewUser = 0;
        if(!empty($userInfo)){
            $isNewUser = $this->rpc->local('DtActivityRulesService\isMatchRule', array('loadGte3', array('userId'=>$userInfo['id'])),'duotou');
        }

        foreach ($duotouList as & $activity) {
            $activity['invest_user_num'] = intval($investUserNums[$activity['id']]);
            $activity['rate_year_tag'] = '预期年化';
            if($activity['lock_day'] == 1) {
                $activity['invest_user_num'] += intval($investUserNums['0']);
                $activity['duration']  = ['t1' => '期限', 't2' => '灵活转让/退出', 't3' => ''];
            } else {
                $activity['duration']  = ['t1' => '期限', 't2' => $activity['lock_day'], 't3' => '天可转让/退出'];
            }

            $activity['min_invest_money'] = ($isNewUser && $activity['new_user_min_invest_money'] > 0) ? $activity['new_user_min_invest_money'] : $activity['min_invest_money'];
        }

        $clientInviteCode = !empty($params['clientInviteCode']) ? trim($params['clientInviteCode']) : null;
        $imgList = $this->rpc->local('NewUserPageService\getPageInfoByInviteCode', array($clientInviteCode));
        if(empty($imgList)){
            $this->setErr('ERR_SYSTEM','图片配置不能为空');
            return false;
        }
        Logger::info(implode(' | ', array(__CLASS__,APP, json_encode(array('pageId' => $imgList['id'],'inviteCode' => $clientInviteCode, 'userId' => $userId)))));

        $registerCount = $this->rpc->local('UserService\getCountByDay', array(date("Y-m-d")));
        $loadUserCount = $this->rpc->local('DealLoadService\getLoadUsersNumByTime', array());


        //分享
        $shareImage = app_conf('APP_COUPON_INVITE_CARD_IMG');
        $shareContent = app_conf('APP_COUPON_INVITE_CARD_DESCRIBE');
        $shareRegUrl = app_conf('APP_COUPON_INVITE_CARD_URL');
        if ($siteId) {
            if (intval($siteId) != 0 && intval($siteId) != 1){
                $template_list = $GLOBALS['sys_config']['TEMPLATE_LIST'];
                $site_name = array_search($siteId,$template_list);
                if($site_name){
                    $site_domain = $GLOBALS['sys_config']['SITE_DOMAIN']["$site_name"];
                    if ($site_domain) {
                        $shareRegUrl = str_replace(app_conf('WXLC_DOMAIN'), $site_domain, $shareRegUrl);
                    }
                }
            }
        }
        $shareRegUrl = str_replace('{$COUPON}', $clientInviteCode, $shareRegUrl);

        $result['status'] = $this->rpc->local('NewUserPageService\isNewUserSwitchOpen', array());
        $result['userStatus'] = $userStatus;
        $result['registerCount'] = $registerCount;
        $result['newUserDealsList'] = $dealsList;
        $result['reservationCardList'] = $reservationCards;
        $result['duotouList'] = $duotouList;
        $result['imgList'] = $imgList;
        $result['loadUserCount'] = $loadUserCount;
        $result['siteId'] = $siteId;
        $result['shareImage'] =$shareImage;
        $result['shareContent'] = $shareContent;
        $result['shareRegUrl'] = $shareRegUrl;
        $this->json_data = $result;
    }

}
