<?php
/**
 * @author wangchuanlu<wangchuanlu@ucfgroup.com>
 * @date   2017-08-23
 **/

namespace api\controllers\duotou;

use libs\web\Form;
use api\controllers\DuotouBaseAction;
use libs\utils\Rpc;
use core\service\UserService;

/**
 * 多投活动页
 **/
class ActivityIndex extends DuotouBaseAction
{
    const IS_H5 = true;

    const UNIQUE_ID_KEY = 'HpyZ@UHUe3iTEx#gN36IHOPnju'; // 用于加密id

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'string',
                'option' => array(
                    'optional' => true,
                ),
            ),
            //临时重定向数据 app再发版时再改
            'money' => array(
                'filter' => 'reg',
                'message' => "金额格式错误",
                'option' => array(
                    'regexp' => '/^\d+(\.\d{1,2})?$/',
                    'optional' => true,
                ),
            ),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
        );

        if (!$this->form->validate() && !$this->isWapCall()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        if ($this->app_version < 470) {
            $this->json_data = [];
            return;
        }
        if (!$this->dtInvoke()) return false;

        $userInfo = $this->getUserByToken();

        $data = $this->form->data;
        $phWapUrl = app_conf('NCFPH_WAP_HOST').'/duotou/ActivityIndex?token='.$data['token'];
        return app_redirect($phWapUrl);

        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $rpc = new \libs\utils\Rpc('duotouRpc');
        $response = $rpc->go('\NCFGroup\Duotou\Services\Project', "getProjectEffect", $request);

        if(!$response) {
            return $this->assignError('ERR_SYSTEM_CALL_CUSTOMER');
        }
        if ($response['errCode'] != 0) {
            return $this->assignError($response['errCode'],$response['errMsg']);
        }
        $project = $response['data'];
        if (empty($project)) {
            return $this->assignError('ERR_DEAL_NOT_EXIST');
        }

        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $vars = array(
            'projectId' => $response['data']['id'],
        );
        $request->setVars($vars);

        $rpc = new \libs\utils\Rpc('duotouRpc');
        //$investUserNumsResponse = $rpc->go('\NCFGroup\Duotou\Services\DealLoan', "getInvestUserNumsByProjectId", $request);
        $investUserNumsResponse = \SiteApp::init()->dataCache->call($rpc, 'go', array('\NCFGroup\Duotou\Services\DealLoan', "getInvestUserNumsByProjectId", $request), 180);
        $investUserNums = array();
        if($investUserNumsResponse && ($investUserNumsResponse['errCode'] == 0)) {
            $investUserNums = $investUserNumsResponse['data'];
        }


        $res = array();
        $peopleCount = !empty($project['peopleCount']) ? $project['peopleCount'] : 0 ;//当前加入的人数
        $siteId = \libs\utils\Site::getId();
        $params = array($siteId);
        $activityList = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DtDealService\getActivityIndexDeals',$params), 60);
        if(empty($activityList)) {
            $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
            return false;
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
        $this->tpl->assign('peopleCount', $peopleCount);
        $this->tpl->assign('activity_list', $activityList);
        $this->tpl->assign('project', $project);
        $this->tpl->assign('token',$data['token']);
        $this->tpl->assign('isNewUser',$isNewUser);
        $this->tpl->assign('unique_id', md5($userInfo['id'] . self::UNIQUE_ID_KEY));
        //临时重定向数据 app再发版时再改
        if(isset($data['money'])){
            $this->tpl->assign('money',$data['money']);
        }
    }

    public function _after_invoke() {
        $this->afterInvoke();
        if($this->errno != 0){
            parent::_after_invoke();
        }
        $this->tpl->display($this->template);
    }

}
