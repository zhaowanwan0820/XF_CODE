<?php
/**
 * 多投宝标的确认页
 * Index.php.
 *
 * @author wangyiming@ucfgroup.com
 */

namespace web\controllers\finplan;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\user\UserCarryService;
use core\service\deal\DealService;
use core\service\user\UserService;
use core\service\user\UserBindService;
use core\service\duotou\DtEntranceService;
use core\service\user\BankService;
use core\service\duotou\DtActivityRulesService;
use core\service\deal\DealAgencyService;
use core\service\risk\RiskAssessmentService;
use core\service\project\DealProjectRiskAssessmentService;
use core\service\supervision\SupervisionAccountService;
use core\service\user\UserTrackService;
use core\service\duotou\DtDealService;
use core\enum\AccountAuthEnum;
use core\enum\UserAccountEnum;
use core\service\account\AccountAuthService;
use core\service\account\AccountService;
use core\service\duotou\DuotouService;
use core\service\coupon\CouponService;
use core\service\supervision\SupervisionService;
use core\service\contract\ContractPreService;
use core\service\bonus\BonusService;
use core\service\duotou\DtBidService;
use core\service\payment\PaymentUserAccountService;
use libs\utils\Logger;

class Bid extends BaseAction
{
    public function init()
    {
        //强调网信普惠

        if (!$this->check_login()) {
            return false;
        }
        if ('0' == app_conf('DUOTOU_SWITCH')) {
            $this->show_tips('系统维护中，请稍后再试！', '系统维护');
            exit;
        }
        if (!is_duotou_inner_user()) {
            $this->show_tips('没有权限,仅内部员工可以查看智多鑫内容！', '没有权限');
            exit;
        }

        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
            'money' => array(
                'filter' => 'reg',
                'message' => '金额格式错误',
                'option' => array(
                    'regexp' => '/^\d+(\.\d{1,2})?$/',
                    'optional' => true,
                ),
            ),
        );
        if (!$this->form->validate()) {
            return app_redirect(url('index'));
        }
    }

    public function invoke()
    {
        $id = $this->form->data['id'];
        $money = $this->form->data['money'];
        $activityId = $id;
        $siteId = \libs\utils\Site::getId();
        $dtEntranceService = new DtEntranceService();
        $dealService = new DealService();
        $activityInfo = $dtEntranceService->getEntranceInfo($activityId, $siteId);
        if (empty($activityInfo)||$activityInfo['status']==2) {
            return $this->show_error('活动信息不存在');
        }

        $accountId = AccountService::getUserAccountId($GLOBALS['user_info']['id'], UserAccountEnum::ACCOUNT_INVESTMENT);
        if (empty($accountId)) {
            return app_redirect('/account/goRegisterStandard');
        }

        //获取有效项目
        $responseDeal = \SiteApp::init()->dataCache->call(new DtDealService(), 'getIndexDeal', array(), 60);
        if (!$responseDeal) {
            $this->show_error('系统繁忙，如有疑问，请拨打客服电话：4008909888');
            return false;
        }

        //智多鑫只允许投资户投资
        if (!UserService::allowAccountLoan($GLOBALS['user_info']['user_purpose'])) {
            return $this->show_error($GLOBALS['lang']['ONLY_INVESTMENT_USER_CAN_BID']);
        }

        $age_check = $dealService->allowedBidByCheckAge($GLOBALS['user_info']);

        if (true == $age_check['error']) {
            return $this->show_error('本项目仅限18岁及以上用户投资', '', 0, 0, url('index'));
        }

        $accountAuthService = new AccountAuthService();
        $grantInfo = $accountAuthService->checkAccountAuth($accountId, AccountAuthEnum::BIZ_TYPE_ZDX);
        $this->tpl->assign('grantInfo', $grantInfo);

        $user_id = $GLOBALS['user_info'] ? $GLOBALS['user_info']['id'] : 0;

        // 首投
        $dtActivityRulesService = new DtActivityRulesService();
        $isNewUser = $dtActivityRulesService->isMatchRule('loadGte3', array('userId' => $GLOBALS['user_info']['id']));

        $isEnterprise = UserService::isEnterprise($user_id);
        $request = array(
                'project_id' => intval($responseDeal['data']['id']),
                'user_id' => $user_id,
                'isEnterprise' => $isEnterprise,
                'isNewUser' => $isNewUser,
                'activity_id' => $activityId,
        );

        $response = DuotouService::callByObject(array('\NCFGroup\Duotou\Services\Project', 'getProjectByIdForBid', $request));
        if (!$response) {
            return $this->show_error('系统繁忙，如有疑问，请拨打客服电话：4008909888', '', 0, 0, url('index'));
        }
        $project = $response['data'];
        if (empty($project)) {
            return app_redirect(url('index'));
        }
        if ($project['isFull'] && 0 == $project['investCount']) {
            return $this->show_error('额度已满，仅允许持有用户查看', '', 0, 0, url('index'));
        }

        $advisory_id = $project['manageId']; //取值为管理方
        $dealAgencyService = new DealAgencyService();
        $advisory_info = $dealAgencyService->getDealAgency($advisory_id);

        //添加强制测评逻辑  绑卡，非企业用户
        $needForceAssess = 0;
        $needReAssess = 0;
        $isRiskValid = 1;
        $totalLimitMoneyData = array();
        if ($GLOBALS['user_info']['idcardpassed'] == 1 && !$isEnterprise) {
            $riskAssessmentService = new RiskAssessmentService();
            $riskData = $riskAssessmentService->getUserRiskAssessmentData($GLOBALS['user_info']['id']);
            if (false != $riskData) {
                $needForceAssess = $riskData['needForceAssess'];
                $isRiskValid = $riskData['isRiskValid'];
                $totalLimitMoneyData = !empty($riskData['totalLimitMoneyData']) ? $riskData['totalLimitMoneyData'] : array();
                $dealProjectRiskAssessmentService = new DealProjectRiskAssessmentService();
                $riskData2 = $dealProjectRiskAssessmentService->checkUserProjectRisk($GLOBALS['user_info']['id'], 2, true, $riskData);
                if (false == $riskData2['result']) {
                    $needReAssess = 1;
                    $remainAssessNum = $riskData2['remaining_assess_num'];
                    $riskLevel = $riskData2['user_risk_assessment'];
                }
            }
        }
        // 限制投资
        $userCarryService = new UserCarryService();
        $user_money_limit = $userCarryService->canWithdrawAmount($GLOBALS['user_info']['id'], $money, true);
        if (false === $user_money_limit) {
            return $this->show_error($GLOBALS['lang']['FORBID_BID']);
        }


        if (empty($GLOBALS['user_info']['payment_user_id'])) {
            return showErr('无法投标', 0, '/account', 0);
        }

        // 如果存管账户未激活
        // 存管标的，如果未激活，走存管激活
        $hasUnactivatedTag = UserService::checkUserTag('SV_UNACTIVATED_USER', $user_id);
        if ($hasUnactivatedTag) {
            return $this->show_error('请先升级网贷P2P账户', '操作失败', 0, 0, sprintf('/payment/transit?srv=register&return_url=%s', get_domain()), 3);
        }


        $bankcard_info = BankService::getUserBankInfo($GLOBALS['user_info']['id']);
        $bank = BankService::getBankInfoByBankId($bankcard_info['bank_id']);

        //地区列表
        //TODO
        /*
        $region_lv1 = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DeliveryRegionService\getRegion', array($bankcard_info['region_lv1'])), 3600);
        $this->tpl->assign("region_lv1",$region_lv1['name']);
        $region_lv2 = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DeliveryRegionService\getRegion', array($bankcard_info['region_lv2'])), 3600);
        $this->tpl->assign("region_lv2",$region_lv2['name']);
        $region_lv3 = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DeliveryRegionService\getRegion', array($bankcard_info['region_lv3'])), 3600);
        $this->tpl->assign("region_lv3",$region_lv3['name']);
        $region_lv4 = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DeliveryRegionService\getRegion', array($bankcard_info['region_lv4'])), 3600);
        $this->tpl->assign("region_lv4",$region_lv4['name']);
        */

        //$turn_on_coupon = CouponService::isShowCoupon($id); // 是否显示优惠码
        $turn_on_coupon = 0; //多投不显示邀请码相关信息
        $this->tpl->assign('turn_on_coupon', $turn_on_coupon);
        if ($turn_on_coupon) {
            $this->getCouponLatest();
        }

        //资产中心余额
        //$balanceResult = $this->rpc->local('UserThirdBalanceService\getUserSupervisionMoney', array($GLOBALS['user_info']['id']));
        //$GLOBALS['user_info']['svCashMoney'] = $balanceResult['supervisionBalance'];

        //是否开户
        $supervisionAccountService = new SupervisionAccountService();
        $GLOBALS['user_info']['isSvUser'] = $supervisionAccountService->isSupervisionUser($accountId);

        //存管降级
        $isSvDown = SupervisionService::isServiceDown();
        // 是否开通协议
        $supervisionAccountService = new SupervisionAccountService();
        $supervisionAccountService->ignoreReqExc = true; //忽略请求异常
        $isOwnBankAuth = $supervisionAccountService->isQuickBidAuthorization($GLOBALS['user_info']['id']);

        $user = $GLOBALS['user_info'];

        $accountMoneyInfo = AccountService::getAccountMoneyById($accountId);
        //$moneyInfo = $this->rpc->local('UserService\getMoneyInfo',array($user,$money));
        //$moneyInfo['wxCashMoney'] = $GLOBALS['user_info']['money'];
        //$moneyInfo['svCashMoney'] =  $GLOBALS['user_info']['svCashMoney'];

        $bonus = BonusService::getUsableBonus($GLOBALS['user_info']['id'], false, 0, false, $user['is_enterprise_user']);
        // 智多新灵活投屏蔽红包
        $dtBidService = new DtBidService();
        if (!$dtBidService->canDtUseBonus($activityId, $GLOBALS['user_info']['id'])) {
            $GLOBALS['user_info']['canUseBonus'] = false;
            $bonus =  array('money' => 0, 'bonuses' => array(), 'accountInfo' => array());
        }
        //$lxTotalMoney = bcadd($GLOBALS['user_info']['money'], $bonus['money'], 2);
        //$totalMoney = bcadd($lxTotalMoney,$GLOBALS['user_info']['svCashMoney'], 2);
        //普惠的总金额为红包加上存管的金额
        $totalMoney = bcadd($accountMoneyInfo['money'], $bonus['money'], 2);
        $this->tpl->assign('total_money', $totalMoney);

        $this->tpl->assign('moneyInfo', $accountMoneyInfo);
        $this->tpl->assign('isSvDown', $isSvDown);
        $this->tpl->assign('isOwnBankAuth', $isOwnBankAuth);
        //是否显示开通快捷投资授权链接
        $this->tpl->assign('isShowQuickBidAuthLink', $this->isSvOpen && !$isSvDown && $GLOBALS['user_info']['isSvUser'] && !$isOwnBankAuth);
        $this->tpl->assign('advisory_name', $advisory_info['name']);
        $this->tpl->assign('bankcard_info', $bankcard_info);
        $this->tpl->assign('user_info', $GLOBALS['user_info']);
        $this->tpl->assign('bank', $bank);
        $this->tpl->assign('bonus', $bonus['money']);
        $this->tpl->assign('needForceAssess', $needForceAssess);
        $this->tpl->assign('totalLimitMoneyData', $totalLimitMoneyData);
        $this->tpl->assign('isRiskValid',$isRiskValid);
        $this->tpl->assign('needReAssess', $needReAssess);
        $this->tpl->assign('remainAssessNum', $remainAssessNum);
        $this->tpl->assign('riskLevel', $riskLevel);
        $this->tpl->assign('backurl', '//'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);

        $project['rate_year'] = number_format($project['rateYear'], 2);
        $project['rateYearTag'] = '往期年化';
        $project['rate_year_base'] = number_format($project['rateYearBase'], 2);
        $project['need_money_decimal'] = $project['maxLoan'];
        $project['date'] = str_replace(',', '、', $project['expiryInterest']);

        $isEnterprise = UserService::isEnterprise($GLOBALS['user_info']['id']);
        $investLimit = ($isNewUser && $activityInfo['new_user_min_invest_money'] > 0) ? $activityInfo['new_user_min_invest_money'] : ($isEnterprise ? $project['singleEnterpriseMinLoanMoney']
            : $activityInfo['min_invest_money']);
        $minLoanMoney = ($isNewUser && $activityInfo['new_user_min_invest_money'] > 0) ? $activityInfo['new_user_min_invest_money'] : ($isEnterprise ? $project['singleEnterpriseMinLoanMoney']
            : $project['singleMinLoanMoney']);

        if (true == $isEnterprise) {
            //企业处理
            $project['investLimit'] = number_format(floor($investLimit)).'元起投<br/>单笔限额'.number_format(floor($project['singleEnterpriseMaxLoanMoney'])).'元';
            $project['min_loan_money'] = floatval($minLoanMoney);
            $project['max_loan_money'] = number_format($project['singleEnterpriseMaxLoanMoney'], 0, ',', '');
            $project['day_redemption'] = number_format($project['enterpriseMaxDayRedemption'], 0, ',', '');
            $project['single'] = $project['enterpriseLoanCount'];
            if (!$money) {
                $money = $project['singleEnterpriseMinLoanMoney'];
            }
        } else {
            //个人处理
            $project['investLimit'] = number_format(floor($investLimit)).'元起投<br/>单笔限额'.number_format(floor($project['singleMaxLoanMoney'])).'元';
            $project['min_loan_money'] = floatval($minLoanMoney);
            $project['max_loan_money'] = number_format($project['singleMaxLoanMoney'], 0, ',', '');
            $project['day_redemption'] = number_format($project['maxDayRedemption'], 0, ',', '');
            $project['single'] = $project['loanCount'];
            if (!$money) {
                $money = $project['singleMinLoanMoney'];
            }
        }

        $fullField = ($isNewUser) ? $responseDeal['data']['moneyLimitDay'] : $responseDeal['data']['oldUserMoneyLimitDay'];
        $isFull = ($responseDeal['data']['hasLoanMoneyDay'] >= $fullField && $responseDeal['data']['moneyLimitDay'] > 0) ? 1 : 0;

        //判断是否开启投资
        $loanStartTime = strtotime($project['loanStartTime']);
        $loanEndTime = strtotime($project['loanEndTime']);
        $nowTime = strtotime(date('H:i:s'));
        $this->tpl->assign('is_open', 0);
        if ($nowTime < $loanStartTime || $nowTime > $loanEndTime) {
            $this->tpl->assign('is_open', 1);
        }

        $contractPreService = new ContractPreService();
        $contpre = $contractPreService->getDealContPreTemplate(intval($responseDeal['data']['id']), 'duotou');
        //$contpre = $this->rpc->local("ContractPreService\getDealContPreTemplate", array(intval($responseDeal['data']['id']),'duotou'));

        //底层资产募集期
        $loanPeriod = date('Y-m-d').'~'.date('Y-m-d', strtotime('+20 days'));
        //顾问服务费
        $advisorFee = '参考年化费率4.50%~6.00%(以合同约定为准)';

        $this->tpl->assign('loanPeriod', $loanPeriod);
        $this->tpl->assign('advisorFee', $advisorFee);
        $this->tpl->assign('siteId', $siteId);
        $this->tpl->assign('id', intval($responseDeal['data']['id']));
        $this->tpl->assign('deal', $project);
        $this->tpl->assign('activityInfo', $activityInfo);
        $this->tpl->assign('money', $money);
        $this->tpl->assign('contpre', $contpre);
        $this->tpl->assign('isNewUser', $isNewUser);
        $this->tpl->assign('isFull', $isFull);
        $this->template = 'web/views/finplan/detail.html';
    }

    /**
     * 获取最近使用优惠码
     * 优先级：1.被绑定的优惠码；2.邀请链接的优惠码；3.最近一次使用的优惠码
     */
    protected function getCouponLatest()
    {
        $userId = $GLOBALS['user_info']['id'];
        $response = CouponService::getCouponLatest($userId);

        \FP::import('libs.utils.logger');
        logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, __LINE__, $userId,'getCouponLatest result',
                json_encode($response), )));
        $coupon = $response['coupon'];
        $coupon['is_fixed'] = $response['is_fixed'];
        logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, __LINE__, $userId,'result', json_encode($coupon))));
        $this->tpl->assign('coupon', $coupon);
    }
}
