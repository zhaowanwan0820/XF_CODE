<?php
/**
 * 短期标预约-提交预约的页面
 *
 */

namespace openapi\controllers\deal;

use libs\web\Form;
use openapi\controllers\ReserveBaseAction;
use core\service\ReservationConfService;
use core\service\UserReservationService;
use core\service\SupervisionAccountService;
use core\dao\ReservationConfModel;
use core\service\DealProjectRiskAssessmentService;
use core\dao\UserReservationModel;
use core\dao\UserModel;
use core\dao\AccountAuthorizationModel;
use libs\payment\supervision\Supervision;

class Reserve extends ReserveBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = $this->sys_param_rules;

        if(!$this->form->validate()){
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        if(!$this->isOpenReserve()){
            $this->setErr('ERR_RESERVE_CLOSE');
            return false;
        }

        if(!$userInfo = $this->getUserByAccessToken()){
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }

        $needForceAssess = 0; //强制风险评测
        $is_check_project_risk = 0; //检查项目风险承受能力
        $user_risk = array('user_risk_assessment'=>'', 'remaining_assess_num'=>0);

        if($userInfo->idcardPassed == 1 && $this->checkEnterpriseUser($userInfo->mobile, $userInfo->userType) != 1){
            $riskData = $this->rpc->local('RiskAssessmentService\getUserRiskAssessmentData', array(intval($userInfo->userId)));
            $needForceAssess = $riskData['needForceAssess'];

            //投资期限的项目评级和个人评级
            $dealProjectRisk = new DealProjectRiskAssessmentService();
            if($dealProjectRisk::$is_reserve_check_enable == $dealProjectRisk::CHECK_ENABLE){
                $is_check_project_risk = 1;
                if(!empty($riskData['last_level_name'])){
                    $user_risk['user_risk_assessment'] = $riskData['last_level_name'];
                    $user_risk['remaining_assess_num'] = $riskData['remaining_assess_num'];
                    $user_risk_project_level = $dealProjectRisk->getAssesmentIdByName($riskData['last_level_name']);
                }
            }
        }

        //检查是否开启存管预约
        $isSupervisionReserve = (int) $this->isOpenSupervisionReserve();
        if($isSupervisionReserve){
            //检查用户是否开通存管账户
            $supervisionAccountObj = new SupervisionAccountService();
            $isSupervisionData = $supervisionAccountObj->isSupervision($userInfo->userId);
            $isOpenAccount = isset($isSupervisionData['isSvUser']) ? (int) $isSupervisionData['isSvUser'] : 0;

            //存管降级不校验
            $isQuickBidAuth = Supervision::isServiceDown() ? 1 : (int)$supervisionAccountObj->isQuickBidAuthorization($userInfo->userId); //检查用户是否开通快捷投资服务

            $isUpgradeAccount = $this->rpc->local('SupervisionService\isUpgradeAccount', array($userInfo->userId)); //是否是存管升级用户

            $bonusData = $this->rpc->local('BonusService\get_useable_money', array($userInfo->userId)); //理财的红包余额
        }

        $data = $this->form->data;

        //获取后台配置的预约标配置信息
        $reservationConfService = new ReservationConfService();
        $reserveConf = $reservationConfService->getReserveInfoByType(ReservationConfModel::TYPE_CONF);
        if(empty($reserveConf) || empty($reserveConf['min_amount'])){
            $this->setErr('ERR_MANUAL_REASON', '尚未配置预约信息');
            return false;
        }

        if(empty($reserveConf['invest_conf'])){
            $this->setErr('ERR_MANUAL_REASON', '尚未配置投资期限');
            return false;
        }

        if(empty($reserveConf['reserve_conf'])){
            $this->setErr('ERR_MANUAL_REASON', '尚未配置预约有效期');
            return false;
        }

        //最低预约金额,单位元
        $minAmount = !empty($reserveConf['min_amount']) ? bcdiv($reserveConf['min_amount'], 100) : 1;
        $authorizeAmountString = sprintf('%s元起', number_format($minAmount));
        //最高预约金额,单位元
        $maxAmount = '0.00';
        if(!empty($reserveConf['max_amount'])){
            $maxAmount = bcdiv($reserveConf['max_amount'], 100);
            $authorizeAmountString .= sprintf('，最高%s元', number_format($maxAmount));
        }

        //预约金额配置
        $amountConf = !empty($reserveConf['amount_conf']) ? $reserveConf['amount_conf'] : [];

        $investData = $reserveData = array();
        //同一投资期限，是否只能预约1次
        if(UserReservationService::IS_ONLY_ONE){
            $userReservationService = new UserReservationService(); //获取用户[预约中+有效期内]的预约记录列表
            $userValidReservelist = $userReservationService->getUserValidReserveList($userInfo->userId);
        }

        //投资期限配置，检查用户是否已预约
        foreach ($reserveConf['invest_conf'] as $key => $item) {
            //组白名单可见
            if(!empty($item['visiableGroupIds'])){
                $groupIds = explode(',', $item['visiableGroupIds']);
                if(!in_array($userInfo->groupId, $groupIds)){
                    continue;
                }
            }

            $investData[$key] = $item;
            $dealType = isset($item['deal_type']) ? $item['deal_type'] : 0;//借款类型
            $investData[$key]['deal_type'] = $dealType;

            //预约最小最大值
            $reserveLimitAmount = $reservationConfService->getReserveLimitAmountByDealType($dealType);
            $investData[$key]['min_amount'] = $reserveLimitAmount['min_amount'];
            $investData[$key]['max_amount'] = $reserveLimitAmount['max_amount'];

            //用于页面显示的授权金额
            $investData[$key]['authorizeAmountString'] = $reservationConfService->getAuthorizeAmountString($reserveLimitAmount['min_amount'], $reserveLimitAmount['max_amount']);

            $investData[$key]['deadline_tag'] = intval($item['deadline']) . '_' . intval($item['deadline_unit']);
            $investData[$key]['deadline_days'] = $reservationConfService->convertToDays($item['deadline'], $item['deadline_unit']); //预约期限天数

            //年化投资率
            $investData[$key]['rate'] = $item['rate'];
            $investData[$key]['rate_factor'] = isset($item['rate_factor']) ? $item['rate_factor'] : 1; //系数默认是1

            //投资期限的单位
            if(!empty(UserReservationModel::$investDeadLineUnitConfig[$item['deadline_unit']])){
                $investData[$key]['deadline_unit_string'] = $item['deadline_unit'] == UserReservationModel::INVEST_DEADLINE_UNIT_MONTH ? '个' . UserReservationModel::$investDeadLineUnitConfig[$item['deadline_unit']] : UserReservationModel::$investDeadLineUnitConfig[$item['deadline_unit']];
            }else{
                $investData[$key]['deadline_unit_string'] = UserReservationModel::$investDeadLineUnitConfig[UserReservationModel::INVEST_DEADLINE_UNIT_DAY];
            }

            $investData[$key]['is_check_project_risk'] = 0; //检查投资期限风险承受能力和个人评估
            if($is_check_project_risk){
                $projectScore = $reservationConfService->getScoreByDeadLine(intval($item['deadline']),intval($item['deadline_unit']));
                if($projectScore == false){
                    $investData[$key]['is_check_project_risk'] = 1;
                }

                $projectLevel = $dealProjectRisk->getByScoreAssesment($projectScore);
                $projectLevel['id'] = empty($projectLevel['id']) ? 0 : $projectLevel['id'];
                if($user_risk_project_level < $projectLevel['id']){
                    $investData[$key]['is_check_project_risk'] = 1;
                }
            }

            //同一投资期限，是否只能预约1次
            if(!UserReservationService::IS_ONLY_ONE){
                $investData[$key]['can_reserve'] = 1; //可预约
                continue;
            }

            //有效期内有预约记录，则检查是否是同一个投资期限内
            if(empty($userValidReservelist['userReserveList'])){
                $investData[$key]['can_reserve'] = 1; //可预约
                continue;
            }

            foreach($userValidReservelist['userReserveList'] as $reserveItem){
                //投资期限相同、投资期限单位相同，则不能预约
                if(intval($reserveItem['invest_deadline']) == intval($item['deadline']) && intval($reserveItem['invest_deadline_unit']) == intval($item['deadline_unit'])){
                    $investData[$key]['can_reserve'] = 0; // 不可预约
                    break;
                }

                $investData[$key]['can_reserve'] = 1; // 可预约
            }
        }

        //预约期限配置
        $reserveTmp = array();
        foreach($reserveConf['reserve_conf'] as $key => $item){
            $expireTag = intval($item['expire']) . '_' . intval($item['expire_unit']);
            if(!empty($reserveTmp[$expireTag])){
                continue;
            }

            $reserveTmp[$expireTag] = 1;
            $reserveData[$key] = $item;
            $reserveData[$key]['expire_tag'] = $expireTag;
            $reserveData[$key]['expire_unit_string'] = !empty(UserReservationModel::$expireUnitConfig[$item['expire_unit']]) ? UserReservationModel::$expireUnitConfig[$item['expire_unit']] : UserReservationModel::$expireUnitConfig[UserReservationModel::EXPIRE_UNIT_HOUR];
        }

        $userMoney = $userInfo->unFormatted; //原money字段是格式化的货币金额,不能参与运算,改用unFormatted字段
        $userMoney = !empty($userMoney) ? $userMoney : 0; //理财的余额
        $bonusMoney = !empty($bonusData['money']) ? $bonusData['money'] : 0; //红包的余额
        $userTotalMoney = bcadd($userMoney, $bonusMoney, 2); //理财的余额+红包的余额

        $balanceResult = $this->rpc->local('UserThirdBalanceService\getUserSupervisionMoney', array((int)$userInfo->userId));
        $bankMoney = !empty($balanceResult['supervisionBalance']) ? $balanceResult['supervisionBalance'] : 0;
        $totalMoney = bcadd($userTotalMoney, $bankMoney, 2); //理财+存管的总余额

        //存管免密授权
        $grantInfo = $this->rpc->local('SupervisionService\checkAuth', [$userInfo->userId]);

        $resData['authorizeAmountString'] = $authorizeAmountString; //弃用
        $resData['min_amount'] = $minAmount; //弃用
        $resData['max_amount'] = $maxAmount; //弃用
        $resData['invest_conf']  = array_values($investData);
        $resData['reserve_conf'] = array_values($reserveData);
        $resData['needForceAssess'] =  $needForceAssess; //强制风险评测
        $resData['user_risk'] = $user_risk;
        $resData['isSupervisionReserve'] = intval($isSupervisionReserve);  // 检查是否开启存管预约
        $resData['isQuickBidAuth'] = isset($isQuickBidAuth) ? intval($isQuickBidAuth) : 0;  // 检查用户是否开通快捷投资服务
        $resData['isUpgradeAccount'] = isset($isUpgradeAccount) ? intval($isUpgradeAccount) : 0; //是否是存管升级用户
        $resData['user_money']  = !empty($userMoney) ? number_format($userMoney, 2) : '0.00';
        $resData['bank_money']  = !empty($bankMoney) ? number_format($bankMoney, 2) : '0.00';
        $resData['bonus_money'] = !empty($bonusMoney) ? number_format($bonusMoney, 2) : '0.00';
        $resData['total_money'] = !empty($totalMoney) ? number_format($totalMoney, 2) : '0.00';
        $resData['user_total_money'] = !empty($userTotalMoney) ? number_format($userTotalMoney, 2) : '0.00';
        $resData['new_bonus_title']  =  app_conf('NEW_BONUS_TITLE');
        $resData['new_bonus_unit']   = app_conf('NEW_BONUS_UNIT');
        $resData['isOpenAccount'] = isset($isOpenAccount) ? $isOpenAccount : 0;

        $resData['isServiceDown'] = Supervision::isServiceDown() ? 1 : 0;;//存管服务是否降级

        $resData['grantInfo'] = $grantInfo;
        $this->json_data = $resData;
    }
}
