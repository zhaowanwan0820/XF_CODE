<?php
/**
 * 短期标预约-提交预约的页面
 *
 * @date 2016-11-17
 * @author guofeng@ucfgroup.com
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\ReserveBaseAction;
use core\service\reserve\ReservationConfService;
use core\service\reserve\UserReservationService;
use core\service\reserve\ReservationEntraService;
use core\service\project\DealProjectRiskAssessmentService;
use core\service\risk\RiskAssessmentService;
use core\service\supervision\SupervisionAccountService;
use core\service\supervision\SupervisionService;
use core\service\account\AccountAuthService;
use core\service\conf\ApiConfService;
use core\service\bonus\BonusService;
use core\service\account\AccountService;
use core\service\user\BankService;
use core\enum\ReserveConfEnum;
use core\enum\ReserveEnum;
use core\enum\UserAccountEnum;
use core\enum\DealEnum;

class Reserve extends ReserveBaseAction {
    // 对于原有的app的h5页面对应的wap页面，如果可以跳转，尝试跳转，否则更改对应的路由
    protected $redirectWapUrl = '/deal/reserve';

    protected $needAuth = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'string',
                'option' => array('optional' => true)
            ),
            'investLine' => array(
                'filter' => 'int',
                'option' => array('optional' => true)
            ),
            'investUnit' => array(
                'filter' => 'int',
                'option' => array('optional' => true)
            ),
            'deal_type' => array('filter' => 'int'),
            'site_id' => array('filter' => 'int'),
            'loantype' => array('filter' => 'int'),
            'rate' => array('filter' => 'string'),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }

        if (!$this->isWapCall()) {
            // wap跳转需要加密的id参数
            // wap选券后回跳，防止dealId被二次加密
            $wapData = $_GET;
            if (is_numeric($wapData['id'])) {
                $dealId = Aes::encryptForDeal($wapData['id']);
            } else {
                $dealId = $wapData['id'];
            }

            $wapData['dealid'] = $dealId;
            unset($wapData['id']);
            unset($wapData['signature']);
            $this->redirectWapUrl .= "?".http_build_query($wapData, '', '&');
        }
    }

    public function invoke() {
        if (!$this->isOpenReserve()) {
            return false;
        }

        $userInfo = $this->getUserBaseInfo();
        if (empty($userInfo))
        {
            //用户未登陆
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        //获取投资账户
        $accountId = AccountService::getUserAccountId($userInfo['id'], UserAccountEnum::ACCOUNT_INVESTMENT);
        //强制风险评测
        $needForceAssess = 0;
        // 检查项目风险承受能力
        $is_check_project_risk = 0;
        $user_risk_project_level = 0;
        $user_risk = array(
            'user_risk_assessment' => '',
            'remaining_assess_num' => 0,
        );
        //出借总限额
        $totalLimitMoneyData = [];
        $isRiskValid = 1;//风险评估是否在有效期内 1为在有效期内

        if($userInfo['idcardpassed'] == 1 && $userInfo['is_enterprise_user'] != 1){
            $riskAssessment = new RiskAssessmentService();
            $riskData = $riskAssessment->getUserRiskAssessmentData(intval($userInfo['id']));
            $needForceAssess = $riskData['needForceAssess'];

            // 投资期限的项目评级和个人评级
            $dealProjectRisk = new DealProjectRiskAssessmentService();
            if ($dealProjectRisk::$is_reserve_check_enable == $dealProjectRisk::CHECK_ENABLE){
                $is_check_project_risk = 1;
                if (!empty($riskData['last_level_name'])){
                    $user_risk['user_risk_assessment'] = $riskData['last_level_name'];
                    $user_risk['remaining_assess_num'] = $riskData['remaining_assess_num'];
                    $user_risk_project_level = $dealProjectRisk->getAssesmentIdByName($riskData['last_level_name']);
                }
            }
            //出借总限额
            $totalLimitMoneyData = !empty($riskData['totalLimitMoneyData']) ? $riskData['totalLimitMoneyData'] : [];
            $isRiskValid = $riskData['isRiskValid'];
        }

        // 检查是否开启存管预约
        $isSupervisionReserve = (int)$this->isOpenSupervisionReserve();
        if ($isSupervisionReserve) {
            // 检查用户是否开通存管账户
            $supervisionAccountObj = new SupervisionAccountService();
            $isSupervisionData = $supervisionAccountObj->isSupervision($userInfo['id']);
            $isOpenAccount = isset($isSupervisionData['isSvUser']) ? (int)$isSupervisionData['isSvUser'] : 0;
            $svInfo = SupervisionService::svInfo($userInfo['id']);
            // 检查用户是否开通快捷投资服务
            // 存管降级不校验
            $isQuickBidAuth = SupervisionService::isServiceDown() ? 1 : (int)$supervisionAccountObj->isQuickBidAuthorization($userInfo['id']);
            //是否是存管升级用户
            $isUpgradeAccount = SupervisionService::isUpgradeAccount($userInfo['id']);
            // 理财的红包余额
            $bonusData = BonusService::getUsableBonus($userInfo['id'], false, 0, false, $userInfo['is_enterprise_user']);
        }

        $data = $this->form->data;

        $dealType = !empty($data['deal_type']) ? (int) $data['deal_type'] : 0;
        $investLine = isset($data['investLine']) ? $data['investLine'] : 0;
        $investUnit = isset($data['investUnit']) ? $data['investUnit'] : 0;
        $loantype = isset($data['loantype']) ? (int) $data['loantype'] : 0;
        $investRate = isset($data['rate']) ? $data['rate'] : 0;

        // 获取后台配置预约入口
        $entraService = new ReservationEntraService();
        $reservationConfService = new ReservationConfService();
        $reserveEntra = $entraService->getReserveEntra($investLine, $investUnit, $dealType, $investRate, $loantype);
        if (empty($reserveEntra)) {
            $this->setErr('ERR_MANUAL_REASON', '尚未配置预约入口');
        }

        $investData = $reserveData = array();
        $userReservationService = new UserReservationService();
        // 同一投资期限，是否只能预约1次
        if (UserReservationService::IS_ONLY_ONE) {
            // 获取用户[预约中+有效期内]的预约记录列表
            $userValidReservelist = $userReservationService->getUserValidReserveList($accountId);
        }

        //组白名单可见
        $visiableGroupIds = array_filter(explode(',', $reserveEntra['visiable_group_ids']));
        if (!empty($visiableGroupIds) && !in_array($userInfo['group_id'], $visiableGroupIds)) {
            $this->setErr('ERR_MANUAL_REASON', '服务暂不可用，请稍后再试！');
        }

        $minAmount = bcdiv($reserveEntra['min_amount'], 100, 2);
        $maxAmount = bcdiv($reserveEntra['max_amount'], 100, 2);
        $authorizeAmountString = sprintf('%s元起', number_format($minAmount));

        // 投资期限配置，检查用户是否已预约
        for ($key = 0; $key < 1; $key ++) {
            $investData[$key] = $reserveEntra;
            $dealType = isset($reserveEntra['deal_type']) ? $reserveEntra['deal_type'] : 0;;
            $investData[$key]['deal_type'] = $dealType; //借款类型

            $investData[$key]['deadline'] = $reserveEntra['invest_line'];
            $investData[$key]['deadline_unit'] = $reserveEntra['invest_unit'];

            //预约最小最大值
            $investData[$key]['min_amount'] = $minAmount;
            $investData[$key]['max_amount'] = $maxAmount;

            //用于页面显示的授权金额
            $investData[$key]['authorizeAmountString'] = $reservationConfService->getAuthorizeAmountString($minAmount, $maxAmount);

            $investData[$key]['deadline_tag'] = intval($reserveEntra['invest_line']) . '_' . intval($reserveEntra['invest_unit']);
            $investData[$key]['deadline_days'] = $reservationConfService->convertToDays($reserveEntra['invest_line'], $reserveEntra['invest_unit']); //预约期限天数

            //年化投资率
            $investData[$key]['rate'] = $reserveEntra['invest_rate'];
            $investData[$key]['rate_factor'] = isset($reserveEntra['rate_factor']) ? $reserveEntra['rate_factor'] : 1; //系数默认是1

            $investData[$key]['loantype'] = $reserveEntra['loantype'];
            $investData[$key]['loantype_name'] = $entraService->getLoantypeName($reserveEntra['loantype']);

            // 投资期限的单位
            if (!empty(ReserveEnum::$investDeadLineUnitConfig[$reserveEntra['invest_unit']])) {
                $investData[$key]['deadline_unit_string'] = $reserveEntra['invest_unit'] == ReserveEnum::INVEST_DEADLINE_UNIT_MONTH ? '个' . ReserveEnum::$investDeadLineUnitConfig[$reserveEntra['invest_unit']] : ReserveEnum::$investDeadLineUnitConfig[$reserveEntra['invest_unit']];
            }else{
                $investData[$key]['deadline_unit_string'] = ReserveEnum::$investDeadLineUnitConfig[ReserveEnum::INVEST_DEADLINE_UNIT_DAY];
            }
            $investData[$key]['is_check_project_risk'] = 0;
            // 检查投资期限风险承受能力和个人评估
            if ($is_check_project_risk){
                $projectScore = $reservationConfService->getScoreByDeadLine(intval($reserveEntra['invest_line']), intval($reserveEntra['invest_unit']), $reserveEntra['deal_type'], $reserveEntra['invest_rate'], $reserveEntra['loantype']);
                if ($projectScore == false){
                    $investData[$key]['is_check_project_risk'] = 1;
                }
                $projectLevel = $dealProjectRisk->getByScoreAssesment($projectScore);
                $projectLevel['id'] = empty($projectLevel['id']) ? 0 : $projectLevel['id'];
                if ($user_risk_project_level < $projectLevel['id']){
                    $investData[$key]['is_check_project_risk'] = 1;
                }
            }
            // 同一投资期限，是否只能预约1次
            if (!UserReservationService::IS_ONLY_ONE) {
                $investData[$key]['can_reserve'] = 1; // 可预约
                continue;
            }

            // 有效期内有预约记录，则检查是否是同一个投资期限内
            if (empty($userValidReservelist['userReserveList'])) {
                $investData[$key]['can_reserve'] = 1; // 可预约
                continue;
            }
            foreach ($userValidReservelist['userReserveList'] as $reserveItem) {
                // 投资期限相同、投资期限单位相同，则不能预约
                if (intval($reserveItem['invest_deadline']) == intval($reserveEntra['invest_line']) && intval($reserveItem['invest_deadline_unit']) == intval($reserveEntra['invest_unit'])) {
                    $investData[$key]['can_reserve'] = 0; // 不可预约
                    break;
                }
                $investData[$key]['can_reserve'] = 1; // 可预约
            }
        }

        // 预约期限配置
        $reserveConf = $reservationConfService->getReserveInfoByType(ReserveConfEnum::TYPE_NOTICE_P2P);
        $reserveTmp = array();
        foreach ($reserveConf['reserve_conf'] as $key => $item) {
            $expireTag = intval($item['expire']) . '_' . intval($item['expire_unit']);
            if (!empty($reserveTmp[$expireTag])) {
                continue;
            }
            $reserveTmp[$expireTag] = 1;
            $reserveData[$key] = $item;
            $reserveData[$key]['expire_tag'] = $expireTag;
            $reserveData[$key]['expire_unit_string'] = !empty(ReserveEnum::$expireUnitConfig[$item['expire_unit']]) ? ReserveEnum::$expireUnitConfig[$item['expire_unit']] : ReserveEnum::$expireUnitConfig[ReserveEnum::EXPIRE_UNIT_HOUR];
        }

        // 用户身份标识
        if (empty($data['userClientKey'])) {
            $userClientKey = parent::genUserClientKey($data['token'], $userInfo['id']);
        } else {
            $userClientKey = $data['userClientKey'];
        }
        // 理财的余额
        $userMoney = !empty($userInfo['money']) ? $userInfo['money'] : 0;
        // 红包的余额
        $bonusMoney = !empty($bonusData['money']) ? $bonusData['money'] : 0;
        // 理财的余额+红包的余额
        $userTotalMoney = bcadd($userMoney, $bonusMoney, 2);
        // 获取用户存管余额
        $balanceResult = AccountService::getAccountMoney((int)$userInfo['id'], UserAccountEnum::ACCOUNT_INVESTMENT);
        $bankMoney = !empty($balanceResult['money']) ? $balanceResult['money'] : 0;
        // 理财+存管的总余额
        $totalMoney = bcadd($userTotalMoney, $bankMoney, 2);
        // 存管+红包
        $isBonusEnable = BonusService::isBonusEnable();
        $userBankMoney = $isBonusEnable ? bcadd($bankMoney, $bonusMoney, 2) : $bankMoney;

        // 投资券开关
        // $siteId = \libs\utils\Site::getId();
        $siteId = isset($data['site_id']) ? $data['site_id'] : $this->defaultSiteId;
        $o2oDiscountSwitch = intval(get_config_db('O2O_DISCOUNT_SWITCH', $siteId));
        $o2oReserveDiscountSwitch = intval(get_config_db('O2O_RESERVE_DISCOUNT_SWITCH', $siteId));

        //是否可以预约 账户用途检查
        $allowReserve = AccountService::allowAccountLoan($userInfo['user_purpose']);

        //随心约授权
        $grantInfo = AccountAuthService::checkAccountAuth($userInfo['id']);;

        //账户类型名称
        $apiConfService = new ApiConfService();
        $accountInfo = $apiConfService->getAccountNameConf();

        //可用余额
        $p2pAvaliableBalance = number_format(bcadd($bonusMoney, $svInfo['svBalance'], 2),2);
        $wxAvaliableBalance = number_format(bcadd($bonusMoney, $userMoney, 2),2);

        $appLoginUrl = $this->getAppScheme('native', array('name'=>'login'));

        //用户银行信息
        $bankcard = BankService::getNewCardByUserId($userInfo['id']);

        $this->json_data = array(
            'wxAvaliableBalance' => $wxAvaliableBalance,
            'p2pAvaliableBalance' => $p2pAvaliableBalance,
            'userClientKey' => $userClientKey,
            'returnLoginUrl' =>  $appLoginUrl,
            'invest_conf' => array_values($investData),
            'invest_line' => $investLine,
            'invest_unit' => $investUnit,
            'reserve_conf' => array_values($reserveData),
            'user_money' => !empty($userMoney) ? number_format($userMoney, 2) : '0.00',
            'bank_money' => !empty($bankMoney) ? number_format($bankMoney, 2) : '0.00',
            'bonus_money' => !empty($bonusMoney) ? number_format($bonusMoney, 2) : '0.00',
            'total_money' => !empty($totalMoney) ? number_format($totalMoney, 2) : '0.00',
            'user_bank_money' => !empty($userBankMoney) ? number_format($userBankMoney, 2) : '0.00',
            'user_total_money' => !empty($userTotalMoney) ? number_format($userTotalMoney, 2) : '0.00',
            'needForceAssess' => $needForceAssess, // 强制风险评测
            'isSupervisionReserve' => $isSupervisionReserve, // 是否开启存管预约
            'isOpenAccount' => isset($isOpenAccount) ? $isOpenAccount : 0, // 是否开通存管账户
            'svInfo' => $svInfo, //存管账户
            'isQuickBidAuth' => isset($isQuickBidAuth) ? $isQuickBidAuth : 0, // 是否开通快捷投资服务
            'isUpgradeAccount' => isset($isUpgradeAccount) ? intval($isUpgradeAccount) : 0, // 是否是存管升级用户
            'token' => !empty($data['token']) ? $data['token'] : $this->_userRedisInfo['token'],
            'riskBackurl' => sprintf('/deal/reserve/?userClientKey=%s&site_id=%s&investLine=%s&investUnit=%s&deal_type=%s&loantype=%s&rate=%s', $userClientKey, $siteId, $investLine, $investUnit, $dealType, $loantype, $investRate),
            'reserveRuleUrl' => sprintf('/deal/reserveRule?userClientKey=%s', $userClientKey),
            'user_risk' => $user_risk,
            'siteId' => $siteId,//分站id
            'o2oDiscountSwitch' => $o2oDiscountSwitch,//投资券开关
            'o2oReserveDiscountSwitch' => $o2oReserveDiscountSwitch,//随心约投资券开关
            'isServiceDown' => SupervisionService::isServiceDown() ? 1 : 0,//存管服务降级
            'user_id' => $userInfo['id'],
            'allowReserve' => intval($allowReserve),
            'isBankcard' => empty($bankcard) ? 0 : 1,
            'wxAccountConfig' => $accountInfo[0],
            'p2pAccountConfig' => $accountInfo[1],
            'isFirstp2p' => 1,
            'grantInfo' => $grantInfo,
            'asgn' => md5(uniqid()), // 临时Token
            'new_bonus_unit' => app_conf('NEW_BONUS_UNIT'),
            'new_bonus_title' => app_conf('NEW_BONUS_TITLE'),
            'userNum' => numTo32($userInfo['id'], 0),    //会员编号
            'totalLimitMoneyData' => $totalLimitMoneyData,
            'isRiskValid' => $isRiskValid,
        );

    }
}

