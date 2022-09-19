<?php
/**
 * 多投宝标的确认页
 * Index.php
 *
 * @author wangyiming@ucfgroup.com
 */

namespace web\controllers\finplan;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\PaymentService;
use core\service\UserCarryService;
use core\service\CouponService;
use core\service\DealService;
use core\service\UserService;
use core\service\SupervisionService;
use libs\utils\Rpc;
use NCFGroup\Protos\Ptp\RequestCoupon;
use NCFGroup\Protos\Ptp\ResponseUserCoupon;
use libs\payment\supervision\Supervision;
use core\dao\EnterpriseModel;
use core\service\UserTrackService;
use core\service\DtBidService;

class Bid extends BaseAction {

    public function init() {

        //网贷拆分直接强跳网信普惠
        $url = sprintf('//%s%s', app_conf('NCFPH_DOMAIN'), $_SERVER['REQUEST_URI']);
        return $this->redirectToP2P($url);
        //强调网信普惠
        if(!$this->is_firstp2p){
            $url = sprintf('//%s%s', app_conf('FIRSTP2P_CN_DOMAIN'), $_SERVER['REQUEST_URI']);
            return $this->redirectToP2P($url);
        }
        if(!$this->check_login()) return false;
        if(app_conf('DUOTOU_SWITCH') == '0') {
            $this->show_tips("系统维护中，请稍后再试！","系统维护");
            exit;
        }
        if(!is_duotou_inner_user()) {
            $this->show_tips("没有权限,仅内部员工可以查看智多新内容！","没有权限");
            exit;
        }

        if(!$this->is_firstp2p){
            $userId = $GLOBALS['user_info']['id'];
            // 验证用户卡状态
            $userService = new UserService($userId);
            $userCheck = $userService->isBindBankCard();
            if ($userCheck['ret'] !== true)
            {
                // 企业用户给提示
                if ($userService->isEnterprise() && ($userCheck['respCode'] == UserService::STATUS_BINDCARD_UNBIND || $userCheck['respCode'] == UserService::STATUS_BINDCARD_UNVALID))
                {
                    return app_redirect(Url::gene('deal','promptCompany'));
                }

                $siteId = \libs\utils\Site::getId();
                $hasPassport = $this->rpc->local('AccountService\hasPassport', array($user_id));
                // 白名单中的分站 大陆用户和已绑卡未验证的港澳台用户跳转到先锋支付绑卡/验卡
                if (($siteId == 1 || \libs\web\Open::checkOpenSwitch()) && (empty($hasPassport) || (!empty($hasPassport) && $userCheck['respCode'] == UserService::STATUS_BINDCARD_UNVALID)))
                {
                    return $this->show_payment_tips($userCheck['respMsg'], '操作失败', 0, 0, '/account/addbank', 3);
                }
                return $this->show_error($userCheck['respMsg'], '操作失败', 0, 0, '/account/addbank', 3);
            }
        }


        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
            'money' => array(
                'filter' => 'reg',
                'message' => "金额格式错误",
                'option' => array(
                    'regexp' => '/^\d+(\.\d{1,2})?$/',
                    'optional' => true,
                ),
            )
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke() {

        $id = $this->form->data['id'];
        $money = $this->form->data['money'];
        $activityId = $id;
        $siteId = \libs\utils\Site::getId();
        $activityInfo = $this->rpc->local('DtEntranceService\getEntranceInfo', array($activityId, $siteId));
        if (empty($activityInfo)) {
            return $this->show_error('活动信息不存在');
        }

        $bankcard = $this->rpc->local('AccountService\getUserBankInfo',array($GLOBALS['user_info']['id']));
        $hasPassport = $this->rpc->local('AccountService\hasPassport', array($GLOBALS['user_info']['id']));

        if ($GLOBALS['user_info']['supervision_user_id'] == 0) {
            if($hasPassport || $bankcard['bankcard'] || $bankcard['newbankcard']) {
                return app_redirect('/account/addbank');
            } else {
                return app_redirect('/account/goRegisterStandard');
            }
        }

        //获取有效项目
        $responseDeal = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DtDealService\getIndexDeal'), 60);
        if (!$responseDeal) {
            $this->show_error("系统繁忙，如有疑问，请拨打客服电话：4008909888");
            return false;
        }

        //智多新只允许投资户投资
        if(!$this->rpc->local('UserService\allowAccountLoan', array($GLOBALS['user_info']['user_purpose']))){
            return $this->show_error($GLOBALS['lang']['ONLY_INVESTMENT_USER_CAN_BID']);
        }

        $dealService = new DealService();
        $age_check = $dealService->allowedBidByCheckAge($GLOBALS['user_info']);

        if($age_check['error'] == true){
            return $this->show_error('本项目仅限18岁及以上用户投资', "", 0, 0, url("index"));
        }

        $grantInfo = $this->rpc->local('SupervisionService\checkAuth', [$GLOBALS['user_info']['id'], SupervisionService::GRANT_TYPE_ZDX]);
        $this->tpl->assign("grantInfo",$grantInfo);


        $rpc = new Rpc('duotouRpc');
        $user_id = $GLOBALS['user_info'] ? $GLOBALS['user_info']['id'] : 0;
        $request = new \NCFGroup\Protos\Duotou\RequestCommon();

        $userService = new UserService($user_id);

        // 首投
        $isNewUser = $this->rpc->local('DtActivityRulesService\isMatchRule', array('loadGte3', array('userId'=>$GLOBALS['user_info']['id'])),'duotou');

        $vars = array(
                'project_id' => intval($responseDeal['data']['id']),
                'user_id' => $user_id,
                'isEnterprise' => $userService->isEnterpriseUser(),
                'isNewUser' => $isNewUser,
        );
        $request->setVars($vars);
        $response = $rpc->go('\NCFGroup\Duotou\Services\Project','getProjectByIdForBid',$request);
        if(!$response) {
            return $this->show_error('系统繁忙，如有疑问，请拨打客服电话：4008909888', "", 0, 0, url("index"));
        }
        $project = $response['data'];
        if (empty($project)) {
            return app_redirect(url("index"));
        }
        if ($project['isFull'] && $project['investCount'] == 0) {
            return $this->show_error('额度已满，仅允许持有用户查看', "", 0, 0, url("index"));
        }


        $advisory_id = $project['manageId'];//取值为管理方
        $advisory_info = $this->rpc->local('DealAgencyService\getDealAgency', array($advisory_id));

        if($GLOBALS['user_info']['idcardpassed'] == 3){
            $info = $this->rpc->local('UserPassportService\getPassport', array($GLOBALS['user_info']['id']));
            return $this->show_error('您的身份信息正在审核中，预计1到3个工作日内审核完成。审核结果将以短信、站内信或者电子邮件等方式通知您。', "", 0);
        }
        //添加强制测评逻辑  绑卡，非企业用户
        $needForceAssess = 0;
        $needReAssess = 0;
        if($GLOBALS['user_info']['idcardpassed'] == 1){
            $riskData = $this->rpc->local('RiskAssessmentService\getUserRiskAssessmentData', array(intval($GLOBALS['user_info']['id'])));
            if($riskData != false){
                $needForceAssess = $riskData['needForceAssess'];

                $riskData2 = $this->rpc->local('DealProjectRiskAssessmentService\checkUserProjectRisk', array($GLOBALS['user_info']['id'], 2, true, $riskData));
                if ($riskData2['result'] == false) {
                    $needReAssess = 1;
                    $remainAssessNum = $riskData2['remaining_assess_num'];
                    $riskLevel = $riskData2['user_risk_assessment'];
                }
            }
        }
        // 限制投资
        $userCarryService = new UserCarryService();
        $user_money_limit = $userCarryService->canWithdrawAmount($GLOBALS['user_info']['id'], $money,true);
        if ($user_money_limit === false){
            return $this->show_error($GLOBALS['lang']['FORBID_BID']);
        }

        //如果未绑定手机
        if(intval($GLOBALS['user_info']['mobilepassed'])==0 || intval($GLOBALS['user_info']['idcardpassed'])!=1 || !$GLOBALS['user_info']['real_name']) {
            return app_redirect(url("account/addbank"));
        }

        if (app_conf('PAYMENT_ENABLE') && empty($GLOBALS['user_info']['payment_user_id'])) {
            return showErr('无法投标',0,'/account',0);
        }

        $bankcard_info = $this->rpc->local("UserBankcardService\getBankcard", array($GLOBALS['user_info']['id']));
        if(!$this->is_firstp2p) {
            if(!$bankcard_info || $bankcard_info['status'] != 1){
                return $this->show_error('请先填写银行卡信息', "", 0,0,url("account/addbank"),3);
            }
        }

        // 如果存管账户未激活
        $hasUnactivatedTag = $this->rpc->local('UserTagService\getTagByConstNameUserId', array('SV_UNACTIVATED_USER', $user_id));
        if ($hasUnactivatedTag) {
            return $this->show_error('请先升级网贷P2P账户', '操作失败', 0, 0, sprintf('/payment/transit?srv=register&return_url=%s', get_domain()), 3);
        }

        $bank = $this->rpc->local("BankService\getBank", array($bankcard_info['bank_id']));

        //地区列表
        $region_lv1 = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DeliveryRegionService\getRegion', array($bankcard_info['region_lv1'])), 3600);
        $this->tpl->assign("region_lv1",$region_lv1['name']);
        $region_lv2 = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DeliveryRegionService\getRegion', array($bankcard_info['region_lv2'])), 3600);
        $this->tpl->assign("region_lv2",$region_lv2['name']);
        $region_lv3 = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DeliveryRegionService\getRegion', array($bankcard_info['region_lv3'])), 3600);
        $this->tpl->assign("region_lv3",$region_lv3['name']);
        $region_lv4 = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DeliveryRegionService\getRegion', array($bankcard_info['region_lv4'])), 3600);
        $this->tpl->assign("region_lv4",$region_lv4['name']);

        //$turn_on_coupon = CouponService::isShowCoupon($id); // 是否显示优惠码
        $turn_on_coupon = 0;//多投不显示邀请码相关信息
        $this->tpl->assign("turn_on_coupon", $turn_on_coupon);
        if ($turn_on_coupon) {
            $this->getCouponLatest(intval($responseDeal['data']['id']));
        }


        //资产中心余额
        $balanceResult = $this->rpc->local('UserThirdBalanceService\getUserSupervisionMoney', array($GLOBALS['user_info']['id']));
        $GLOBALS['user_info']['svCashMoney'] = $balanceResult['supervisionBalance'];

        //是否开户
        $GLOBALS['user_info']['isSvUser'] = $this->rpc->local('SupervisionAccountService\isSupervisionUser', array($GLOBALS['user_info']['id']));

        //存管降级
        $isSvDown = Supervision::isServiceDown();
        // 是否开通协议
        $supervisionAccountService = new \core\service\SupervisionAccountService();
        $supervisionAccountService->ignoreReqExc = true;//忽略请求异常
        $isOwnBankAuth = $supervisionAccountService->isQuickBidAuthorization($GLOBALS['user_info']['id']);

        $user = $this->rpc->local("UserService\getUserViaSlave", array($GLOBALS['user_info']['id']));
        $moneyInfo = $this->rpc->local('UserService\getMoneyInfo',array($user,$money));
        $moneyInfo['wxCashMoney'] = $GLOBALS['user_info']['money'];
        $moneyInfo['svCashMoney'] =  $GLOBALS['user_info']['svCashMoney'];

        $bonus = $this->rpc->local('BonusService\get_useable_money', array($GLOBALS['user_info']['id']));
        // 智多新灵活投屏蔽红包
        $dtBidService = new DtBidService();
        if (!$dtBidService->canDtUseBonus($activityInfo, $user)) {
            $bonus =  array('money' => 0, 'bonuses' => array(), 'accountInfo' => array());
        }
        $lxTotalMoney = bcadd($GLOBALS['user_info']['money'], $bonus['money'], 2);
        $totalMoney = bcadd($lxTotalMoney,$GLOBALS['user_info']['svCashMoney'], 2);
        //普惠的总金额为红包加上存管的金额
        if($this->is_firstp2p) {
            $totalMoney = bcadd($bonus['money'],$GLOBALS['user_info']['svCashMoney'], 2);
        }
        $this->tpl->assign('total_money', $totalMoney);

        //是否主站登录
        $userTrackService = new UserTrackService();
        $isFromWxlc = $userTrackService->isWxlcLogin($GLOBALS['user_info']['id']);
        $this->tpl->assign('isFromWxlc', $isFromWxlc);

        $this->tpl->assign('moneyInfo',$moneyInfo);
        $this->tpl->assign('isSvDown', $isSvDown);
        $this->tpl->assign("isOwnBankAuth", $isOwnBankAuth);
        //是否显示开通快捷投资授权链接
        $this->tpl->assign('isShowQuickBidAuthLink', $this->isSvOpen && !$isSvDown && $GLOBALS['user_info']['isSvUser'] && !$isOwnBankAuth);
        $this->tpl->assign('advisory_name',$advisory_info['name']);
        $this->tpl->assign('bankcard_info',$bankcard_info);
        $this->tpl->assign('user_info', $GLOBALS['user_info']);
        $this->tpl->assign("bank",$bank);
        $this->tpl->assign('bonus', $bonus['money']);
        $this->tpl->assign('needForceAssess', $needForceAssess);
        $this->tpl->assign('needReAssess', $needReAssess);
        $this->tpl->assign('remainAssessNum', $remainAssessNum);
        $this->tpl->assign('riskLevel', $riskLevel);
        $this->tpl->assign("backurl", '//'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);

        $project['rate_year'] = number_format($project['rateYear'], 2);
        $project['rateYearTag'] = '往期年化';
        $project['rate_year_base'] = number_format($project['rateYearBase'], 2);
        $project['need_money_decimal'] = $project['maxLoan'];
        $project['date'] = str_replace(",", "、" ,$project['expiryInterest']);

        $investLimit = ($isNewUser && $activityInfo['new_user_min_invest_money'] > 0)  ? $activityInfo['new_user_min_invest_money'] : ($isEnterprise ? $project['singleEnterpriseMinLoanMoney']
            : $activityInfo['min_invest_money']);
        $minLoanMoney = ($isNewUser && $activityInfo['new_user_min_invest_money'] > 0)   ? $activityInfo['new_user_min_invest_money'] : ($isEnterprise ? $project['singleEnterpriseMinLoanMoney']
            : $project['singleMinLoanMoney']);

        if($vars['isEnterprise'] == true){
            //企业处理
            $project['investLimit'] = number_format(floor($investLimit)) . '元起投<br/>单笔限额' . number_format(floor($project['singleEnterpriseMaxLoanMoney'])) . '元';
            $project['min_loan_money'] = floatval($minLoanMoney);
            $project['max_loan_money'] = number_format($project['singleEnterpriseMaxLoanMoney'], 0, ",", "");
            $project['day_redemption'] = number_format($project['enterpriseMaxDayRedemption'], 0, ",", "");
            $project['single'] = $project['enterpriseLoanCount'];
            if (!$money) {
                $money = $project['singleEnterpriseMinLoanMoney'];
            }
        }else{
            //个人处理
            $project['investLimit'] = number_format(floor($investLimit)) . '元起投<br/>单笔限额' . number_format(floor($project['singleMaxLoanMoney'])) . '元';
            $project['min_loan_money'] = floatval($minLoanMoney);
            $project['max_loan_money'] = number_format($project['singleMaxLoanMoney'], 0, ",", "");
            $project['day_redemption'] =  number_format($project['maxDayRedemption'], 0, ",", "");
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
        if($nowTime < $loanStartTime || $nowTime > $loanEndTime) {
           $this->tpl->assign('is_open', 1);
        }

        $contpre = $this->rpc->local("ContractPreService\getDealContPreTemplate", array(intval($responseDeal['data']['id']),'duotou'));

        //底层资产募集期
        $loanPeriod = date('Y-m-d').'~'.date('Y-m-d',strtotime('+20 days'));
        //顾问服务费
        $advisorFee = '参考年化费率4.50%~6.00%(以合同约定为准)';

        $this->tpl->assign('loanPeriod', $loanPeriod);
        $this->tpl->assign('advisorFee', $advisorFee);
        $this->tpl->assign('siteId', $siteId);
        $this->tpl->assign('id', intval($responseDeal['data']['id']));
        $this->tpl->assign("deal", $project);
        $this->tpl->assign('activityInfo', $activityInfo);
        $this->tpl->assign("money",$money);
        $this->tpl->assign("contpre",$contpre);
        $this->tpl->assign('isNewUser', $isNewUser);
        $this->tpl->assign('isFull', $isFull);

        $showBonusTips = intval(\libs\utils\ABControl::getInstance()->hit('useBonusForDt'));
        $this->tpl->assign('showBonusTips', $showBonusTips ? false : true);

        $this->template = "web/views/v3/finplan/detail.html";
    }

    /**
     * 获取最近使用优惠码
     * 优先级：1.被绑定的优惠码；2.邀请链接的优惠码；3.最近一次使用的优惠码
     */
    protected function getCouponLatest() {
        $userId = $GLOBALS['user_info']['id'];
        $request = new RequestCoupon();
        $request->setUserId($userId);
        $response = $GLOBALS['rpc']->callByObject(array(
                'service' => 'NCFGroup\Ptp\services\PtpCoupon',
                'method' => 'getCouponLatest',
                'args' => $request
        ));

        \FP::import("libs.utils.logger");
        \logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, __LINE__, $userId, $dt_deal_id, 'getCouponLatest result',
                json_encode($response))));
        $coupon = $response['coupon'];
        $coupon['is_fixed'] = $response['is_fixed'];
        \logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, __LINE__, $userId, $dt_deal_id, 'result', json_encode($coupon))));
        $this->tpl->assign("coupon", $coupon);
    }
}
