<?php

/**
 * 多投宝标的详情页
 * ActivityIndexData.php
 * @author wangchuanlu@ucfgroup.com
 */

namespace web\controllers\finplan;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Rpc;

class ActivityIndexData extends BaseAction {
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
        );
        if (!$this->form->validate()) {
//            return app_redirect(url("index"));
        }
    }

    public function invoke() {
        $result = array('activityList'=>array(),'project'=>array(),'isNewUser'=>0);
        if(app_conf('DUOTOU_SWITCH') == '0') {
            return $this->returnJson($result);
        }
        if(!is_duotou_inner_user()) {
            return $this->returnJson($result);
        }

        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $rpc = new \libs\utils\Rpc('duotouRpc');
        $response = $rpc->go('\NCFGroup\Duotou\Services\Project', "getProjectEffect", $request);

        if(!$response) {
            return $this->returnJson($result);
        }
        if ($response['errCode'] != 0) {
            return $this->returnJson($result);
        }
        $project = $response['data'];
        if (empty($project)) {
            return $this->returnJson($result);
        }

        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $vars = array(
            'projectId' => $response['data']['id'],
        );
        $request->setVars($vars);
        $rpc = new \libs\utils\Rpc('duotouRpc');
        $investUserNumsResponse = \SiteApp::init()->dataCache->call($rpc, 'go', array('\NCFGroup\Duotou\Services\DealLoan', "getInvestUserNumsByProjectId", $request), 180);
        //$investUserNumsResponse = $rpc->go('\NCFGroup\Duotou\Services\DealLoan', "getInvestUserNumsByProjectId", $request);
        $investUserNums = array();
        if($investUserNumsResponse && ($investUserNumsResponse['errCode'] == 0)) {
            $investUserNums = $investUserNumsResponse['data'];
        }
        $siteId = \libs\utils\Site::getId();
        $params = array($siteId);
        $activityList = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DtDealService\getActivityIndexDeals',$params), 60);
        if(empty($activityList)) {
            return $this->returnJson($result);
        }
        $isNewUser = 0;
        if(!empty($userInfo)){
            $isNewUser = $this->rpc->local('DtActivityRulesService\isMatchRule', array('loadGte3', array('userId'=>$userInfo['id'])),'duotou');
        }

        foreach ($activityList as & $activity) {
            $activity['invest_user_num'] = intval($investUserNums[$activity['id']]);
            if($activity['lock_day'] == 1) {
                $activity['invest_user_num'] += intval($investUserNums['0']);
            }
        }
        $result['activityList'] = $activityList;
        $result['project'] = $project;
        $result['isNewUser'] = $isNewUser;
        return $this->returnJson($result);
    }

    private function returnJson($res) {
        echo json_encode($res,JSON_UNESCAPED_UNICODE);
        return true;
    }

}
