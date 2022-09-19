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
use core\service\ReservationConfService;
use core\service\UserReservationService;
use core\service\ReservationEntraService;
use core\service\DealProjectRiskAssessmentService;
use core\service\RiskAssessmentService;
use core\dao\ReservationConfModel;
use core\dao\UserReservationModel;
use core\service\SupervisionAccountService;
use libs\payment\supervision\Supervision;

class Reserve extends ReserveBaseAction {

    const IS_H5 = true;

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
            'loantype' => array('filter' => 'int'),
            'rate' => array('filter' => 'string'),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        if (!$this->isOpenReserve()) {
            return false;
        }

        $userInfo = $this->getUserBaseInfo();
        if (empty($userInfo))
        {
            // 登录token失效，跳到App登录页
            header('Location:' . $this->getAppScheme('native', array('name'=>'login')));
            return false;
        }

        //强制风险评测
        $needForceAssess = 0;
        // 检查项目风险承受能力
        $is_check_project_risk = 0;
        $user_risk_project_level = 0;
        $user_risk = array(
            'user_risk_assessment' => '',
            'remaining_assess_num' => 0,
        );

        if($userInfo['idcardpassed'] == 1 && $userInfo['is_enterprise_user'] != 1){
            $riskData = $this->rpc->local('RiskAssessmentService\getUserRiskAssessmentData', array(intval($userInfo['id'])));
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
        }

        // 检查是否开启存管预约
        $isSupervisionReserve = (int)$this->isOpenSupervisionReserve();
        if ($isSupervisionReserve) {
            // 检查用户是否开通存管账户
            $supervisionAccountObj = new SupervisionAccountService();
            $isSupervisionData = $supervisionAccountObj->isSupervision($userInfo['id']);
            $isOpenAccount = isset($isSupervisionData['isSvUser']) ? (int)$isSupervisionData['isSvUser'] : 0;
            $svInfo = $this->rpc->local('SupervisionService\svInfo', array($userInfo['id']));
            // 检查用户是否开通快捷投资服务
            // 存管降级不校验
            $isQuickBidAuth = Supervision::isServiceDown() ? 1 : (int)$supervisionAccountObj->isQuickBidAuthorization($userInfo['id']);
            //是否是存管升级用户
            $isUpgradeAccount = $this->rpc->local('SupervisionService\isUpgradeAccount', array($userInfo['id']));
            // 理财的红包余额
            $bonusData = $this->rpc->local('BonusService\get_useable_money', array($userInfo['id']));
        }

        $data = $this->form->data;
        //随心约产品类型
        $productType = UserReservationModel::PRODUCT_TYPE_EXCLUSIVE;
        $dealType = !empty($data['deal_type']) ? (int) $data['deal_type'] : 0;
        $dealTypeList = $this->rpc->local("UserReservationService\getDealTypeListByProduct", array($productType, $userInfo['id']));
        $dealTypeList = array_intersect($dealTypeList, [$dealType]);
        if (empty($dealTypeList)) {
            $this->setErr('ERR_MANUAL_REASON', '服务暂不可用，请稍后再试！');
            return false;
        }

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
        // 同一投资期限，是否只能预约1次
        if (UserReservationService::IS_ONLY_ONE) {
            // 获取用户[预约中+有效期内]的预约记录列表
            $userReservationService = new UserReservationService();
            $userValidReservelist = $userReservationService->getUserValidReserveList($userInfo['id']);
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
            if (!empty(UserReservationModel::$investDeadLineUnitConfig[$reserveEntra['invest_unit']])) {
                $investData[$key]['deadline_unit_string'] = $reserveEntra['invest_unit'] == UserReservationModel::INVEST_DEADLINE_UNIT_MONTH ? '个' . UserReservationModel::$investDeadLineUnitConfig[$reserveEntra['invest_unit']] : UserReservationModel::$investDeadLineUnitConfig[$reserveEntra['invest_unit']];
            }else{
                $investData[$key]['deadline_unit_string'] = UserReservationModel::$investDeadLineUnitConfig[UserReservationModel::INVEST_DEADLINE_UNIT_DAY];
            }
            $investData[$key]['is_check_project_risk'] = 0;
            // 检查投资期限风险承受能力和个人评估
            if ($is_check_project_risk){
                $projectScore = $reservationConfService->getScoreByDeadLine(intval($reserveEntra['invest_line']),intval($reserveEntra['invest_unit']), $reserveEntra['deal_type'], $reserveEntra['invest_rate'], $reserveEntra['loantype']);
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
        $reserveConf = $reservationConfService->getReserveInfoByType(ReservationConfModel::TYPE_NOTICE);
        $reserveTmp = array();
        foreach ($reserveConf['reserve_conf'] as $key => $item) {
            $expireTag = intval($item['expire']) . '_' . intval($item['expire_unit']);
            if (!empty($reserveTmp[$expireTag])) {
                continue;
            }
            $reserveTmp[$expireTag] = 1;
            $reserveData[$key] = $item;
            $reserveData[$key]['expire_tag'] = $expireTag;
            $reserveData[$key]['expire_unit_string'] = !empty(UserReservationModel::$expireUnitConfig[$item['expire_unit']]) ? UserReservationModel::$expireUnitConfig[$item['expire_unit']] : UserReservationModel::$expireUnitConfig[UserReservationModel::EXPIRE_UNIT_HOUR];
        }

        // 用于页面显示的授权金额
        $this->tpl->assign('authorizeAmountString', $authorizeAmountString); //弃用，兼容老版本
        // 临时Token
        $this->tpl->assign('asgn', md5(uniqid()));
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
        $balanceResult = $this->rpc->local('UserThirdBalanceService\getUserSupervisionMoney', array((int)$userInfo['id']));
        $bankMoney = !empty($balanceResult['supervisionBalance']) ? $balanceResult['supervisionBalance'] : 0;
        // 理财+存管的总余额
        $totalMoney = bcadd($userTotalMoney, $bankMoney, 2);
        // 存管+红包
        $userBankMoney = bcadd($bankMoney, $bonusMoney, 2);

        // 投资券开关
        $siteId = \libs\utils\Site::getId();
        $o2oDiscountSwitch = intval(get_config_db('O2O_DISCOUNT_SWITCH', $siteId));
        $o2oReserveDiscountSwitch = intval(get_config_db('O2O_RESERVE_DISCOUNT_SWITCH', $siteId));

        //是否可以预约 账户用途检查
        $allowReserve = $this->rpc->local('UserService\allowAccountLoan', [$userInfo['user_purpose']]);

        //随心约授权
        $grantInfo = $this->rpc->local('SupervisionService\checkAuth', [$userInfo['id']]);
        $this->tpl->assign('grantInfo', $grantInfo);

        //账户类型名称
        $accountInfo = $this->rpc->local('ApiConfService\getAccountNameConf');

        $this->tpl->assign('product_type', $productType);

        //可用余额
        $p2pAvaliableBalance = number_format(bcadd($bonusMoney, $svInfo['svBalance'], 2),2);
        $wxAvaliableBalance = number_format(bcadd($bonusMoney, $userInfo['money'], 2),2);
        $this->tpl->assign('wxAvaliableBalance',$wxAvaliableBalance);
        $this->tpl->assign('p2pAvaliableBalance',$p2pAvaliableBalance);

        $this->tpl->assign('userClientKey', $userClientKey);
        $appLoginUrl = $this->getAppScheme('native', array('name'=>'login'));
        $this->tpl->assign('returnLoginUrl', $appLoginUrl);
        $this->tpl->assign('min_amount', $minAmount); //弃用，兼容老版本
        $this->tpl->assign('max_amount', $maxAmount); //弃用，兼容老版本
        $this->tpl->assign('invest_conf', array_values($investData));
        $this->tpl->assign('invest_line', $investLine);
        $this->tpl->assign('invest_unit', $investUnit);
        $this->tpl->assign('reserve_conf', array_values($reserveData));
        $this->tpl->assign('user_money', !empty($userMoney) ? number_format($userMoney, 2) : '0.00');
        $this->tpl->assign('bank_money', !empty($bankMoney) ? number_format($bankMoney, 2) : '0.00');
        $this->tpl->assign('bonus_money', !empty($bonusMoney) ? number_format($bonusMoney, 2) : '0.00');
        $this->tpl->assign('total_money', !empty($totalMoney) ? number_format($totalMoney, 2) : '0.00');
        $this->tpl->assign('user_bank_money', !empty($userBankMoney) ? number_format($userBankMoney, 2) : '0.00');
        $this->tpl->assign('user_total_money', !empty($userTotalMoney) ? number_format($userTotalMoney, 2) : '0.00');

        $this->tpl->assign('needForceAssess', $needForceAssess); // 强制风险评测
        $this->tpl->assign('isSupervisionReserve', $isSupervisionReserve); // 是否开启存管预约
        $this->tpl->assign('isOpenAccount', isset($isOpenAccount) ? $isOpenAccount : 0); // 是否开通存管账户
        $this->tpl->assign('svInfo', $svInfo); //存管账户
        $this->tpl->assign('isQuickBidAuth', isset($isQuickBidAuth) ? $isQuickBidAuth : 0); // 是否开通快捷投资服务
        $this->tpl->assign('isUpgradeAccount', isset($isUpgradeAccount) ? intval($isUpgradeAccount) : 0); // 是否是存管升级用户
        $token = !empty($data['token']) ? $data['token'] : $this->_userRedisInfo['token'];
        $this->tpl->assign('token', $token);
        $this->tpl->assign('riskBackurl', sprintf('/deal/reserve/?userClientKey=%s&site_id=%s&investLine=%s&investUnit=%s&deal_type=%s&loantype=%s&rate=%s', $userClientKey, $siteId, $investLine, $investUnit, $dealType, $loantype, $investRate));
        $this->tpl->assign('reserveRuleUrl', sprintf('/deal/reserveRule?userClientKey=%s', $userClientKey));

        $this->tpl->assign('user_risk', $user_risk);

        $this->tpl->assign('siteId', $siteId);//分站id
        $this->tpl->assign('o2oDiscountSwitch', $o2oDiscountSwitch);//投资券开关
        $this->tpl->assign('o2oReserveDiscountSwitch', $o2oReserveDiscountSwitch);//随心约投资券开关

        //存管服务降级
        $this->tpl->assign('isServiceDown', Supervision::isServiceDown() ? 1 : 0);

        $this->tpl->assign('user_id', $userInfo['id']);
        $this->tpl->assign('allowReserve', intval($allowReserve));

        $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id" => $userInfo['id']));
        $this->tpl->assign('isBankcard', empty($bankcard) ? 0 : 1);

        $this->tpl->assign('wxAccountConfig', $accountInfo[0]);
        $this->tpl->assign('p2pAccountConfig', $accountInfo[1]);

        $this->tpl->assign('is_firstp2p', $this->is_firstp2p);
        return true;
    }
}

