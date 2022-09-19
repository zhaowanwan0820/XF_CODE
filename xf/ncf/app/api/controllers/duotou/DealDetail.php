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
use core\service\account\AccountService;
use core\service\deal\DealService;
use core\service\risk\RiskAssessmentService;
use core\service\project\DealProjectRiskAssessmentService;
use core\service\duotou\DtEntranceService;
use core\service\user\UserCarryService;
use core\service\duotou\DtDealService;
use core\service\duotou\DtActivityRulesService;
use core\service\duotou\DuotouService;
use core\service\bonus\BonusService;
use core\service\conf\ApiConfService;
use core\service\supervision\SupervisionService;
use core\service\account\AccountAuthService;
use core\enum\AccountAuthEnum;
use core\service\user\UserTrackService;
use core\enum\UserAccountEnum;
use core\service\user\BankService;
use core\service\duotou\DtBidService;

/**
 * 多投标的详情页
 *
 * @packaged default
 * @author 赵辉<zhaohui3@ucfgroup.com>
 **/
class DealDetail extends DuotouBaseAction {
    protected $redirectWapUrl = '/duotou/DealDetail';

    public function init() {
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
        );

        if (!$this->form->validate()) {
            $this->assignError("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            $this->par_validate =false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->user;

        $allow_bid = 1;
        if (!AccountService::allowAccountLoan($userInfo['user_purpose'])) {
            $allow_bid = 0;
        }

        if ($userInfo['idcardpassed'] == 3) {
            return $this->assignError('ERR_MANUAL_REASON', '您的身份信息正在审核中，预计1到3个工作日内审核完成');
        }

        $oDealService = new DealService();
        $age_check = $oDealService->allowedBidByCheckAge($userInfo);
        if ($age_check['error'] == true) {
            return $this->assignError('ERR_IDENTITY_NO_VERIFY', '本项目仅限18岁及以上用户投资');
        }

        $isEnterprise = $userInfo['is_enterprise_user'];
        if (!$isEnterprise) {
            // 如果未绑定手机，对于非企业用户需要进行身份认证
            if (intval($userInfo['mobilepassed']) == 0 || intval($userInfo['idcardpassed']) != 1 || !$userInfo['real_name']) {
                return $this->assignError('ERR_MANUAL_REASON', '请进行身份认证');
            }
        }

        if (empty($userInfo['payment_user_id'])) {
            return $this->assignError('ERR_MANUAL_REASON', '无法投标');
        }

        $accountId  = AccountService::getUserAccountId($userInfo['id'], UserAccountEnum::ACCOUNT_INVESTMENT);

        //强制风险评测
        $needForceAssess = 0;
        $needReAssess = 0;
        $isRiskValid = 1;
        $riskLevel = 0;
        $remainAssessNum = 0;
        $totalLimitMoneyData = array();//总出借限额
        if ($userInfo['idcardpassed'] == 1 && $userInfo['is_enterprise_user'] != 1) {
            $oRiskAssessmentService = new RiskAssessmentService();
            $riskData = $oRiskAssessmentService->getUserRiskAssessmentData(intval($userInfo['id']), 0, 0, $isEnterprise);

            if ($riskData != false) {
                $needForceAssess = $riskData['needForceAssess'];
                $isRiskValid = $riskData['isRiskValid'];
                $totalLimitMoneyData = !empty($riskData['totalLimitMoneyData']) ? $riskData['totalLimitMoneyData'] : array();
                $oDealProjectRiskAssessmentService = new DealProjectRiskAssessmentService();
                $riskData2 = $oDealProjectRiskAssessmentService->checkUserProjectRisk(
                    $userInfo['id'],
                    2,
                    true,
                    $riskData,
                    $isEnterprise
                );

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
        $siteId = isset($data['site_id']) ? $data['site_id'] : $this->defaultSiteId;
        $oDtEntranceService = new DtEntranceService();
        $activityInfo = $oDtEntranceService->getEntranceInfo($activityId, $siteId);
        if (empty($activityInfo)||$activityInfo['status']==2) {
            return $this->assignError('ERR_MANUAL_REASON', '活动信息不存在');
        }

        // 限制投资
        $oUserCarryService = new UserCarryService();
        $userMoneyLimit = $oUserCarryService->canWithdrawAmount(intval($userInfo['id']),$money,true);
        if ($userMoneyLimit === false){
            return $this->assignError('ERR_DEAL_FORBID_BID');
        }

        //获取有效项目
        $oDtDealService = new DtDealService();
        $responseDeal = \SiteApp::init()->dataCache->call($oDtDealService, 'getIndexDeal', array($userInfo['id']), 60);
        if (!$responseDeal) {
            $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
            return false;
        }
        $oDtActivityRulesService = new DtActivityRulesService();
        $isNewUser = $oDtActivityRulesService->isMatchRule('loadGte3', array('userId'=>$userInfo['id']));

        $vars = array(
            'project_id' => intval($responseDeal['data']['id']),
            'user_id' => $userInfo['id'],
            'isEnterprise' => $isEnterprise,
            'isNewUser' => $isNewUser,
            'activity_id' => $activityId,
        );
        $response = DuotouService::callByObject(array('\NCFGroup\Duotou\Services\Project', 'getProjectByIdForBid', $vars));

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
            $deal['limitNum'] = '每笔最多加入'. number_format(floor($deal['singleEnterpriseMaxLoanMoney']),2) .'元，每人最多加入' . $deal['enterpriseLoanCount'] . '笔(转让/退出后可再加入)';
            $deal['minLoanMoney'] = floatval($minLoanMoney);
            $deal['maxLoanMoney'] = floatval($deal['singleEnterpriseMaxLoanMoney']);
        } else {
            $deal['investLimit'] = number_format(floor($investLimit)) . '元起<br/>单笔限额' . number_format(floor($deal['singleMaxLoanMoney'])) . '元';

            $deal['maxDayRedemptionText'] = floor($deal['maxDayRedemption'] / 10000);
            $deal['limitNum'] = '每笔最多加入'. number_format(floor($deal['singleMaxLoanMoney']),2) .'元，每人最多加入' . $deal['loanCount'] . '笔(转让/退出后可再加入)';
            $deal['minLoanMoney'] = floatval($minLoanMoney);
            $deal['maxLoanMoney'] = floatval($deal['singleMaxLoanMoney']);
        }

        $fullField = ($isNewUser) ? $responseDeal['data']['moneyLimitDay'] : $responseDeal['data']['oldUserMoneyLimitDay'];
        $isFull = ($responseDeal['data']['hasLoanMoneyDay'] >= $fullField && $responseDeal['data']['moneyLimitDay'] > 0) ? 1 : 0;

        $isOpen = $this->isOpen(strtotime($deal['loanStartTime']), strtotime($deal['loanEndTime']));//是否是在开放时间
        $deal['isOpen'] = $isOpen ? 1 : 0;
        $deal['openTime'] = '每日' . date('G:i', strtotime($deal['loanStartTime'])) . '-' . date('G:i', strtotime($deal['loanEndTime'])) . '开放加入';


        $deal['investUserNum'] = !empty($deal['peopleCount']) ? $deal['peopleCount'] : 0;//当前加入的人数
        $deal['rateYear'] = !empty($deal['rateYear']) ? number_format($deal['rateYear'], 2) : 0;//预期年化利率/收益率
        $deal['rateYearBase'] = !empty($deal['rateYearBase']) ? number_format($deal['rateYearBase'], 2) : 0;//基础年化收益率

        $discountSign = isset($data['discount_sign']) ? $data['discount_sign'] : '';
        // 通过discount_id 获取discount_sign
        if(!empty($data['discount_id']) && empty($data['discount_sign'])) {
            $params = array('user_id'=> $userInfo['id'], 'deal_id'=> $activityId, 'discount_id' => $data['discount_id'], 'discount_group_id' => $data['discount_group_id']);
        }

        //账户类型名称
        $oApiConfService = new ApiConfService();
        $accountInfo = $oApiConfService->getAccountNameConf();
        $deal['wxAccountConfig'] = $accountInfo[0];
        $deal['p2pAccountConfig'] = $accountInfo[1];

        $bonus = BonusService::getUsableBonus($userInfo['id'], false, 0, false, $isEnterprise);
        $dtBidService = new DtBidService();
        $canDtUseBonus = $dtBidService->canDtUseBonus($activityId, $userInfo['id']);
        if (!$canDtUseBonus) {
            $bonus =  array('money' => 0, 'bonuses' => array(), 'accountInfo' => array());
        }
        $riskBackurl = sprintf('/duotou/DealDetail/?token=%s&is_detail=1&is_allow_access=1&activity_id=%u', $data['token'], intval($data['activity_id']));
        //用户银行信息
        $bankcard = BankService::getNewCardByUserId($userInfo['id']);
        $res = array(
            "bnousMoney" => number_format($bonus['money'], 2),
            "siteId" => $siteId,
            "userId" => isset($userInfo['id']) ? $userInfo['id'] : '',
            "allowBid" => $allow_bid,
            "discount_id" => isset($data['discount_id']) ? $data['discount_id'] : '',
            "discount_group_id" => isset($data['discount_group_id']) ? $data['discount_group_id'] : '',
            "discount_type" => isset($data['discount_type']) ? $data['discount_type'] : '',
            "discount_sign" => $discountSign,
            "discount_bidAmount" => isset($data['discount_bidAmount']) ? $data['discount_bidAmount'] : '',
            "o2oDiscountSwitch" => 1,   // 获取投资券开关状态
            "deal" => $deal,
            "needForceAssess" => $needForceAssess,
            "needReAssess" => $needReAssess,
            "remainAssessNum" => $remainAssessNum,
            "riskLevel" => $riskLevel,
            "isRiskValid" => $isRiskValid,//风险评估是否在有效期内
            "totalLimitMoneyData" => $totalLimitMoneyData,
            "backurl" => $riskBackurl,
            "defaultMoney" => $money,
            "new_bonus_title" => app_conf('NEW_BONUS_TITLE'),
            "new_bonus_unit" => app_conf('NEW_BONUS_UNIT'),
            "isFull" => $isFull,
            "activityId" => $activityId,
            "activityInfo" => $activityInfo,
            "isNewUser" => $isNewUser,
            'isBankcard' => empty($bankcard) ? 0 : 1,
        );

        //存管相关
        $svInfo = SupervisionService::svInfo($userInfo['id']);

        //用户可用余额
        $svBalance = !empty($svInfo['svBalance']) ? $svInfo['svBalance'] : 0;
        $res['avaliableBalance'] = number_format(bcadd($bonus['money'], $svBalance, 2),2);

        if (isset($svInfo['isSvUser']) && $svInfo['isSvUser']) {
            $totalMoney = bcadd($bonus['money'], $svInfo['svBalance'], 2);
            $svInfo['svBalance'] = number_format($svInfo['svBalance'], 2);
        }

        $res['totalMoney'] = number_format($totalMoney, 2);
        $res['svInfo'] = $svInfo;

        //是否是存管升级用户
        $res['isUpgradeAccount'] = SupervisionService::isUpgradeAccount($userInfo['id']);
        //智多鑫授权
        $res['grantInfo'] = AccountAuthService::checkAccountAuth($accountId, AccountAuthEnum::BIZ_TYPE_ZDX);

        $res['showBonusTips'] = $canDtUseBonus ? false : true;
        $res['userNum'] = numTo32($userInfo['id'], 0);//会员编号
        //底层资产募集期
        $res['loanPeriod'] = date('Y-m-d').'~'.date('Y-m-d',strtotime('+20 days'));
        //顾问服务费
        $res['advisorFee'] = '参考年化费率4.50%~6.00%(以合同约定为准)';
        $this->json_data = $res;
    }
}
