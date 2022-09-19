<?php
/**
 * DealDetail controller class file.
 *
 * @author 赵辉<zhaohui3@ucfgroup.com>
 * @date   2016-07-26
 **/

namespace api\controllers\duotou;

use libs\web\Form;
use api\controllers\DuotouBaseAction;
use NCFGroup\Protos\Duotou\Enum\DealEnum;
use libs\utils\Rpc;
use core\dao\DealModel;
use core\service\UserService;
use core\service\PaymentService;
use core\service\SupervisionService;
use core\dao\EnterpriseModel;
use core\service\UserTrackService;
use core\service\DtBidService;

/**
 * 多投标的详情页
 *
 * @packaged default
 * @author 赵辉<zhaohui3@ucfgroup.com>
 **/
class DealDetail extends DuotouBaseAction
{
    private $phAction = '/duotou/DealDetail';

    const IS_H5 = true;

    public function init()
    {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'token is required',
            ),
            'money' => array(
                'filter' => 'reg',
                'message' => "金额格式错误",
                'option' => array(
                    'regexp' => '/^\d+(\.\d{1,2})?$/',
                    'optional' => true,
                ),
            ),
            'activity_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
            ),
            'discount_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_type' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_group_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_sign' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_bidAmount' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            //重定向标志
            'is_detail' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
            ),
            'timestamp' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
            ),
            'site_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
            ),
            'signature' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
            ),
            'is_allow_access' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
            ),
            'type' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
            ),
            'url' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
            ),
            //wxb 安卓需要
            'wxb' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
            ),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->assignError("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            $this->par_validate =false;
        }
    }

    public function invoke()
    {
        //临时控制逻辑，待app重新发版后更改 by duxuefeng
        //is_detail如果不存在会调到智多鑫入口列表，如果不存在则会执行详情的invoke函数。
        $data = $this->form->data;
        if (!isset($data['is_detail'])) {
            //data中的url是经过两次urlencode，此时获取的url只是第一次urlencode，所以在此再次urlencode
            if(isset($data['url'])){
                $data['url']=urlencode($data['url']);
            }
            $url = "/duotou/ActivityIndex?";
            $paramsJoin = array(); //用于拼接url的数组
            foreach($data as $k => $v){
                $paramsJoin[]="$k=$v";
            }
            $paramsString = implode('&',$paramsJoin);
            return app_redirect($url.$paramsString);
        }

        if (!$this->dtInvoke()) {
            return false;
        }

        $userInfo = $this->getUserByToken();
        if (!$userInfo) {
            return $this->assignError('ERR_GET_USER_FAIL');//获取oauth用户信息失败
        }

        $ncfphData = array(
            'activity_id' => $data['activity_id'],
            'is_allow_access' => $data['is_allow_access'],
            'is_detail' => $data['is_detail'],
            'token' => $data['token'],
            'discount_id' => $data['discount_id'],
            'discount_group_id' => $data['discount_group_id'],
            'discount_bidAmount' => $data['discount_bidAmount']

        );

        return $this->ncfphRedirect($this->phAction, $ncfphData);

        $allow_bid = 1;
        if(!$this->rpc->local('UserService\allowAccountLoan', array($userInfo['user_purpose']))){
            $allow_bid = 0;
        }

        if ($userInfo['idcardpassed'] == 3) {
            return $this->assignError('ERR_MANUAL_REASON', '您的身份信息正在审核中，预计1到3个工作日内审核完成');
        }

        $age_check = $this->rpc->local('DealService\allowedBidByCheckAge', array($userInfo));
        if ($age_check['error'] == true) {
            return $this->assignError('ERR_IDENTITY_NO_VERIFY', '本项目仅限18岁及以上用户投资');
        }

        $userService = new UserService($userInfo['id']);
        $isEnterprise = $userService->isEnterpriseUser();
        if (!$isEnterprise) {
            // 如果未绑定手机，对于非企业用户需要进行身份认证
            if (intval($userInfo['mobilepassed']) == 0 || intval($userInfo['idcardpassed']) != 1 || !$userInfo['real_name']) {
                return $this->assignError('ERR_MANUAL_REASON', '请进行身份认证');
            }
        }

        if (app_conf('PAYMENT_ENABLE') && empty($userInfo['payment_user_id'])) {
            return $this->assignError('ERR_MANUAL_REASON', '无法投标');
        }

        if(!$this->is_firstp2p) {
            $bankcardInfo = $this->rpc->local("UserBankcardService\getBankcard", array($userInfo['id']));
            if (empty($bankcardInfo['verify_status'])) {
                return $this->assignError('ERR_MANUAL_REASON', '请完善银行卡信息');
            }
        }

        (new \core\service\ncfph\Proxy())->execute();// 代理请求普惠接口
        //强制风险评测
        $needForceAssess = 0;
        $needReAssess = 0;
        if ($userInfo['idcardpassed'] == 1) {
            $riskData = $this->rpc->local('RiskAssessmentService\getUserRiskAssessmentData', array(intval($userInfo['id'])));
            if ($riskData != false) {
                $needForceAssess = $riskData['needForceAssess'];

                $riskData2 = $this->rpc->local('DealProjectRiskAssessmentService\checkUserProjectRisk', array($GLOBALS['user_info']['id'], 2, true, $riskData));
                if ($riskData2['result'] == false) {
                    $needReAssess = 1;
                    $remainAssessNum = $riskData2['remaining_assess_num'];
                    $riskLevel = $riskData2['user_risk_assessment'];
                }
            }
        }

        $data = $this->form->data;
        $money = !empty($data['money']) ? $data['money'] : 0;
        $activityId = !empty($data['activity_id']) ? $data['activity_id'] : 0;
        $siteId = \libs\utils\Site::getId();
        $activityInfo = $this->rpc->local('DtEntranceService\getEntranceInfo', array($activityId, $siteId));
        if (empty($activityInfo)) {

            return $this->assignError('ERR_MANUAL_REASON', '活动信息不存在');
        }

        // 限制投资
        $userMoneyLimit = $this->rpc->local('UserCarryService\canWithdrawAmount', array(intval($userInfo['id']),$money,true));
        if ($userMoneyLimit === false){
            return $this->assignError('ERR_DEAL_FORBID_BID');
        }

        $rpc = new Rpc('duotouRpc');
        if (!$rpc) {
            return $this->assignError('ERR_SYSTEM_CALL_CUSTOMER');
        }

        //获取有效项目
        $responseDeal = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DtDealService\getIndexDeal'), 60);
        if (!$responseDeal) {
            $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
            return false;
        }

        $isNewUser = $this->rpc->local('DtActivityRulesService\isMatchRule', array('loadGte3', array('userId'=>$userInfo['id'])),'duotou');

        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $vars = array(
            'project_id' => intval($responseDeal['data']['id']),
            'user_id' => $userInfo['id'],
            'isEnterprise' => $isEnterprise,
            'isNewUser' => $isNewUser,
        );
        $request->setVars($vars);
        $response = $rpc->go('\NCFGroup\Duotou\Services\Project', 'getProjectByIdForBid', $request);

        if (!$response) {
            return $this->assignError('ERR_SYSTEM_CALL_CUSTOMER');
        }
        if ($response['errCode'] != 0) {
            return $this->assignError($response['errCode'], $response['errMsg']);
        }

        $deal = $response['data'];

        if (empty($deal)) {
            return $this->assignError('ERR_DEAL_NOT_EXIST');
        }

        $bonus = $this->rpc->local('BonusService\get_useable_money', array($userInfo['id']));
        // 智多鑫灵活投屏蔽红包
        $dtBidService = new DtBidService();
        $lockPeriod = $activityInfo['lock_day'];
        if (!$dtBidService->canDtUseBonus($activityInfo, $userInfo)) {
            $bonus =  array('money' => 0, 'bonuses' => array(), 'accountInfo' => array());
        }
        $this->tpl->assign('bnousMoney', number_format($bonus['money'], 2));

        $deal['tagBeforeName'] = !empty($deal['tagBeforeName']) ? $deal['tagBeforeName'] : '';
        $deal['tagAfterName'] = !empty($deal['tagAfterName']) ? $deal['tagAfterName'] : '';
        $deal['tagBeforeDesc'] = !empty($deal['tagBeforeDesc']) ? $deal['tagBeforeDesc'] : '';
        $deal['tagAfterDesc'] = !empty($deal['tagAfterDesc']) ? $deal['tagAfterDesc'] : '';
        $deal['rateYearTag'] = '往期年化';
        $deal['creditRule'] = '1份债权价值0.01元';
        $deal['interestRule'] = '按日计算利息/收益';

        $expiryInterestArray = explode(',', $deal['expiryInterest']);
        //$deal['expiryInterestText'] = !empty($deal['expiryInterest']) ? '每月'.implode('、', $expiryInterestArray).'日' : '';
        $deal['expiryInterestText'] = '加入资产还款日结息';
        $deal['feeRateText'] = '本金的年化' . number_format($deal['feeRate'], 2) . '%,满' . intval($deal['feeDays']) . '天免收';

        $investLimit = ($isNewUser && $activityInfo['new_user_min_invest_money'] > 0)  ? $activityInfo['new_user_min_invest_money'] : ($isEnterprise ? $deal['singleEnterpriseMinLoanMoney']
                                                                                       : $activityInfo['min_invest_money']);
        $minLoanMoney = ($isNewUser && $activityInfo['new_user_min_invest_money'] > 0)   ? $activityInfo['new_user_min_invest_money'] : ($isEnterprise ? $deal['singleEnterpriseMinLoanMoney']
            : $deal['singleMinLoanMoney']);
        if ($isEnterprise) {//企业用户
            $deal['investLimit'] = number_format(floor($investLimit)) . '元起<br/>单笔限额' . number_format(floor($deal['singleEnterpriseMaxLoanMoney'])) . '元';
            $deal['maxDayRedemptionText'] = floor($deal['enterpriseMaxDayRedemption'] / 10000);
            $deal['limitNum'] = '每人最多加入' . $deal['enterpriseLoanCount'] . '笔，其中1天可申请转让/退出期限最多加入'.$deal['quickLoanCount'].'笔(转让/退出后可再加入)';
            $deal['minLoanMoney'] = floatval($minLoanMoney);
            $deal['maxLoanMoney'] = floatval($deal['singleEnterpriseMaxLoanMoney']);
        } else {
            $deal['investLimit'] = number_format(floor($investLimit)) . '元起<br/>单笔限额' . number_format(floor($deal['singleMaxLoanMoney'])) . '元';

            $deal['maxDayRedemptionText'] = floor($deal['maxDayRedemption'] / 10000);
            $deal['limitNum'] = '每人最多加入' . $deal['loanCount'] . '笔，其中1天可申请转让/退出期限最多加入'.$deal['quickLoanCount'].'笔(转让/退出后可再加入)';
            $deal['minLoanMoney'] = floatval($minLoanMoney);
            $deal['maxLoanMoney'] = floatval($deal['singleMaxLoanMoney']);
        }

        $fullField = ($isNewUser) ? $responseDeal['data']['moneyLimitDay'] : $responseDeal['data']['oldUserMoneyLimitDay'];
        $isFull = ($responseDeal['data']['hasLoanMoneyDay'] >= $fullField && $responseDeal['data']['moneyLimitDay'] > 0) ? 1 : 0;

        $isOpen = $this->isOpen(strtotime($deal['loanStartTime']), strtotime($deal['loanEndTime']));//是否是在开放时间
        $deal['isOpen'] = $isOpen ? 1 : 0;
        $deal['openTime'] = '每日' . date('G:i', strtotime($deal['loanStartTime'])) . '-' . date('G:i', strtotime($deal['loanEndTime'])) . '开放';

        $deal['investUserNum'] = !empty($deal['peopleCount']) ? $deal['peopleCount'] : 0;//当前加入的人数
        $deal['rateYear'] = !empty($deal['rateYear']) ? number_format($deal['rateYear'], 2) : 0;//预期年化利率/收益率
        $deal['rateYearBase'] = !empty($deal['rateYearBase']) ? number_format($deal['rateYearBase'], 2) : 0;//基础年化收益率
        $deal['money'] = number_format($userInfo['money'], 2);//可用余额

        $discountSign = isset($data['discount_sign']) ? $data['discount_sign'] : '';
        // 通过discount_id 获取discount_sign
        if(!empty($data['discount_id']) && empty($data['discount_sign'])) {
            $params = array('user_id'=> $userInfo['id'], 'deal_id'=> $activityId, 'discount_id' => $data['discount_id'], 'discount_group_id' => $data['discount_group_id']);
            $discountSign = $this->rpc->local('DiscountService\getSignature', array($params));
        }
        // 获取投资券开关状态
        $o2oDiscountSwitch = intval(get_config_db('O2O_DISCOUNT_SWITCH', $siteId));

        //账户类型名称
        $accountInfo = $this->rpc->local('ApiConfService\getAccountNameConf');
        $deal['wxAccountConfig'] = $accountInfo[0];
        $deal['p2pAccountConfig'] = $accountInfo[1];

        $this->tpl->assign('siteId', $siteId);
        $this->tpl->assign('userId', isset($userInfo['id']) ? $userInfo['id'] : '');
        $this->tpl->assign('allowBid', $allow_bid);
        $this->tpl->assign('discount_id', isset($data['discount_id']) ? $data['discount_id'] : '');
        $this->tpl->assign('discount_group_id', isset($data['discount_group_id']) ? $data['discount_group_id'] : '');
        $this->tpl->assign('discount_type', isset($data['discount_type']) ? $data['discount_type'] : '');
        $this->tpl->assign('discount_sign', $discountSign);
        $this->tpl->assign('discount_bidAmount', isset($data['discount_bidAmount']) ? $data['discount_bidAmount'] : '');

        $this->tpl->assign('o2oDiscountSwitch', $o2oDiscountSwitch);
        $this->tpl->assign('deal', $deal);
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('needForceAssess', $needForceAssess);
        $this->tpl->assign('needReAssess', $needReAssess);
        $this->tpl->assign('remainAssessNum', $remainAssessNum);
        $this->tpl->assign('riskLevel', $riskLevel);
        $riskBackurl = sprintf('/duotou/DealDetail/?project_id=%u&token=%s&is_detail=1&is_allow_access=1&activity_id=%u', intval($data['project_id']), $data['token'], intval($data['actiivity_id']));
        $this->tpl->assign("backurl", $riskBackurl);
        $this->tpl->assign("defaultMoney", $money);
        $this->tpl->assign('new_bonus_title', app_conf('NEW_BONUS_TITLE'));
        $this->tpl->assign('new_bonus_unit', app_conf('NEW_BONUS_UNIT'));
        $this->tpl->assign('isFull', $isFull);
        $this->tpl->assign('activityId', $activityId);
        $this->tpl->assign('activityInfo', $activityInfo);
        $this->tpl->assign('isNewUser', $isNewUser);

        //存管相关
        $supervisionService = new \core\service\SupervisionService();
        $supervisionService->ignoreReqExc = true; //忽略请求异常
        $svInfo = $supervisionService->svInfo($userInfo['id']);

        //用户可用余额
        $svBalance = !empty($svInfo['svBalance']) ? $svInfo['svBalance'] : 0;
        $this->tpl->assign('avaliableBalance',number_format(bcadd($bonus['money'], $svBalance, 2),2));

        $totalMoney = bcadd($userInfo['money'], $bonus['money'], 2);//可用余额
        if (isset($svInfo['isSvUser']) && $svInfo['isSvUser']) {
            if($this->is_firstp2p) {//普惠的总金额为红包加上存管的金额
                $totalMoney = bcadd($bonus['money'], $svInfo['svBalance'], 2);
            } else {
                $totalMoney = bcadd($totalMoney, $svInfo['svBalance'], 2);
            }
            $svInfo['svBalance'] = number_format($svInfo['svBalance'], 2);
        }

        $this->tpl->assign('totalMoney', number_format($totalMoney, 2));
        $this->tpl->assign('svInfo', $svInfo);

        //是否是存管升级用户
        $isUpgradeAccount = $this->rpc->local('SupervisionService\isUpgradeAccount', array($userInfo['id']));
        $this->tpl->assign('isUpgradeAccount', intval($isUpgradeAccount));

        //存管服务降级
        $this->tpl->assign('isServiceDown', \libs\payment\supervision\Supervision::isServiceDown() ? 1 : 0);

        //智多鑫授权
        $grantInfo = $this->rpc->local('SupervisionService\checkAuth', [$userInfo['id'], SupervisionService::GRANT_TYPE_ZDX]);
        $this->tpl->assign('grantInfo', $grantInfo);

        $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id" => $userInfo['id']));
        $this->tpl->assign('isBankcard', empty($bankcard) ? 0 : 1);


        //是否主站登录
        $userTrackService = new UserTrackService();
        $isFromWxlc = $userTrackService->isWxlcLogin($userInfo['id']);
        $this->tpl->assign('isFromWxlc', $isFromWxlc);


        //会员信息
        $isShowVip = 0;
        if ($this->rpc->local("VipService\isShowVip", array($userInfo['id']), "vip")) {
            $vipInfo = $this->rpc->local("VipService\getVipGrade",array($userInfo['id']), "vip");
            $vip['vipGradeName'] = $vipInfo['name'];
            $vip['raiseInterest'] = $vipInfo['raiseInterest'];
            $isShowVip = $vipInfo['service_grade'] != 0 ? 1 : 0;
            $this->tpl->assign('vipInfo', $vip);
        }
        $this->tpl->assign('isShowVip', $isShowVip);
        $this->tpl->assign('appVersion', $this->app_version);
        $showBonusTips = intval(\libs\utils\ABControl::getInstance()->hit('useBonusForDt', $userInfo));
        $this->tpl->assign('showBonusTips', $showBonusTips ? false : true);
        //底层资产募集期
        $loanPeriod = date('Y-m-d').'~'.date('Y-m-d',strtotime('+20 days'));
        //顾问服务费
        $advisorFee = '参考年化费率4.50%~6.00%(以合同约定为准)';
        $this->tpl->assign('loanPeriod', $loanPeriod);
        $this->tpl->assign('advisorFee', $advisorFee);
    }

    public function _after_invoke() {
        $this->afterInvoke();
        if($this->errno != 0){
            parent::_after_invoke();
        }
        $this->tpl->display($this->template);
    }

}
