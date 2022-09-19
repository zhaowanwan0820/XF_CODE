<?php
/**
 * @author wangchuanlu<wangchuanlu@ucfgroup.com>
 * @date   2017-08-23
 **/

namespace api\controllers\duotou;

use libs\web\Form;
use api\controllers\DuotouBaseAction;
use core\service\duotou\DuotouService;
use core\service\duotou\DtDealService;
use core\service\duotou\DtActivityRulesService;

/**
 * 多投活动页
 **/
class ActivityIndex extends DuotouBaseAction {
    // 对于原有的app的h5页面对应的wap页面，如果可以跳转，尝试跳转，否则更改对应的路由
    protected $redirectWapUrl = '/duotou/ActivityIndex';

    // 是否需要授权
    protected $needAuth = false;
    const UNIQUE_ID_KEY = 'HpyZ@UHUe3iTEx#gN36IHOPnju'; // 用于加密id

    public function init()
    {
        parent::init();
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

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $response = $this->callByObject(array('\NCFGroup\Duotou\Services\Project', "getProjectEffect", array()));
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

        $vars = array(
            'projectId' => $response['data']['id'],
        );
        $investUserNumsResponse = \SiteApp::init()->dataCache->call(new DuotouService() , 'call', array(array('\NCFGroup\Duotou\Services\DealLoan', "getInvestUserNumsByProjectId", $vars)), 180);
        //$investUserNumsResponse = $this->callByObject(array('\NCFGroup\Duotou\Services\DealLoan', "getInvestUserNumsByProjectId", $vars));
        $investUserNums = array();
        if($investUserNumsResponse && ($investUserNumsResponse['errCode'] == 0)) {
            $investUserNums = $investUserNumsResponse['data'];
        }

        $res = array();
        $peopleCount = !empty($project['peopleCount']) ? $project['peopleCount'] : 0 ;//当前加入的人数
        $siteId = \libs\utils\Site::getId();
        $params = array($siteId);
        $oDtDealService = new DtDealService();
        $activityList = \SiteApp::init()->dataCache->call($oDtDealService, 'getActivityIndexDeals', $params, 60);
        if(empty($activityList)) {
            $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
            return false;
        }

        $isNewUser = 0;
        $userInfo = $this->getUserByToken();
        if(!empty($userInfo)){
            $oDtActivityRulesService = new DtActivityRulesService();
            $isNewUser = $oDtActivityRulesService->isMatchRule('loadGte3', array('userId'=>$userInfo['id']));
        }


        foreach ($activityList as & $activity) {
            $activity['invest_user_num'] = intval($investUserNums[$activity['id']]);
            if($activity['lock_day'] == 1) {
                $activity['invest_user_num'] += intval($investUserNums['0']);
            }
            if($activity['invest_user_num'] >= 10000){
                $num = floor($activity['invest_user_num']/100);
                $activity['invest_user_num'] = number_format($num/100,2)."万";
            }
        }

        $res = array(
            "peopleCount" => $peopleCount,
            "activity_list" => $activityList,
            "project" => $project,
            "token" => $data['token'],
            "isNewUser" => $isNewUser,
            "unique_id" => md5($userInfo['id'] . self::UNIQUE_ID_KEY),
        );
        //临时重定向数据 app再发版时再改
        if(isset($data['money'])){
            $res['money'] = $data['money'];
        }
        $this->json_data = $res;
    }

}
