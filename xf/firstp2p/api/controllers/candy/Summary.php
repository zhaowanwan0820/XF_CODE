<?php

namespace api\controllers\candy;

use api\controllers\AppBaseAction;
use app\models\dao\Deal;
use core\service\candy\CandyActivityService;
use core\service\CheckinService;
use core\service\DealCustomUserService;
use core\service\UserService;
use core\service\AccountService;
use core\service\vip\VipService;
use libs\web\Form;
use libs\utils\ABControl;
use core\service\candy\CandyAccountService;
use core\service\candy\CandyProduceService;
use core\service\candy\CandyShopService;
use core\service\AgreementService;

class Summary extends AppBaseAction
{
    const IS_H5 = true;
    public function init() {
        parent::init();
        $this->form = new Form('get');
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $userId = $loginUser['id'];
        $accountService = new CandyAccountService();
        $produceService = new CandyProduceService();
        $shopService = new CandyShopService();
        $activityService = new CandyActivityService();
        $accountInfo = $accountService->getAccountInfo($userId);

        $accountService->clearRedDot($userId);
        $userStat = $activityService->getUserActivityToday($userId);
        $suggestProductList = $shopService->getTopProductList();
        $couponList = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('CandyShopService\getCouponList', array(), 'candy'), 600);
        $topLifeProductList = $shopService->getTopLifeGoods();
        $showConfig = [];
        foreach ($userStat as $key => $value) {
            $showConfig[$key] = true;
        }

        $dealCustomService = new DealCustomUserService();
        // ??????????????????????????????????????????????????????????????????
        if ($dealCustomService->checkBlackList($loginUser['id']) == true || !$dealCustomService->canLoanZx($userId)) {
            $showConfig['ZHUANXIANG'] = false;
        }
        //????????????????????????
        $showConfig['BONUS_ON'] = (new CandyActivityService())->inBonus($userId);
        //???????????????
        $showConfig['BLACK_BUC'] = (new \core\service\BwlistService)->inList('DEAL_CU_BLACK');
        //???????????????????????????
        $showConfig['ACTIVITY_INVEST_RATIO'] = true;
        // ????????????
        $gameSwitch = app_conf('CANDY_TREE_GAME_SWITCH') ?: 0;
        if (empty($gameSwitch)) {
            $showConfig['GAME_ON'] = (new \core\service\BwlistService)->inList('CANDY_GAME_WHITE');
        } else {
            $showConfig['GAME_ON'] = true;
        }

        // ???????????????
        if ($showConfig['GAME_ON']) {
            $blackList = \core\dao\BonusConfModel::get("XINBAO_GAME_BLACK") ?: '';
            $blackList = explode(',', $blackList);
            if ($blackList && in_array($userId, $blackList)) {
                $showConfig['GAME_ON'] = false;
            }
        }

        // total????????????10??????
        //$activityPool = \SiteApp::init()->dataCache->call($this->rpc, 'local', ['CandyAccountService\getAllActivityTotalToday', [], 'candy'], 180);
        $activityPool = $activityService->getAllActivityTotalTodayFromCache();
        $userActivity = array_sum($userStat);

        $estimateCandy = $produceService->calcUserCandyToday($userActivity, $activityPool); //????????????????????????

        $userSummary = [
            "activityPool" => number_format($activityPool), //?????????
            "userActivity" => number_format($userActivity), //????????????
            "estimateCandy" => number_format($estimateCandy, 3), //????????????????????????
            "candy" => empty($accountInfo) ? '0.000' : number_format($accountInfo['amount'], 3), //??????
            "candyCashValue" => $accountService->calcCandyWorth($accountInfo['amount']),
            "stat" => $userStat //????????????????????????
        ];

        $userSummary['estimateCandyCashValue'] = number_format($accountService->calcCandyWorth($estimateCandy), 2);
        /**
         *  userStat??????
        Array
        (
        [CHECKIN] => 1 // ??????
        [INVITE] => 10 // ??????
        [P2P] => 0 // ??????p2p
        [ZHUANXIANG] => 0 // ????????????
        [DT] => 0 // ???????????????
        )
         */

        $activityKeyConf = (new CandyActivityService())->getActivityKeyConf();

        //??????????????????
        $agreementCheck = AgreementService::check($loginUser['id'], 'candy');
        $this->tpl->assign('agreementCheck', $agreementCheck);

        $checkInfo = (new CheckinService())->getCheckedInfo($userId);
        // ????????????????????????
        $userHasLoan = (new UserService())->hasLoan($userId);

        $this->tpl->assign('userSummary', $userSummary);
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('showConfig', $showConfig);
        $this->tpl->assign('suggestProductList', $suggestProductList);
        $this->tpl->assign('couponList', $couponList);
        $this->tpl->assign('topLifeProductList', $topLifeProductList);
        $this->tpl->assign('activityConf', $activityKeyConf);
        $this->tpl->assign('checkInStatus', $checkInfo['checkedStatus']);
        $this->tpl->assign('userHasLoan', intval($userHasLoan));
        $this->tpl->assign('shopUrl', $GLOBALS['sys_config']['LIFE_SHOP']['SHOP_HOST']);
        $this->tpl->assign('gameUrl', app_conf('CANDY_TREE_GAME_URL'));
        $this->template = $this->getTemplate('');
    }

}
