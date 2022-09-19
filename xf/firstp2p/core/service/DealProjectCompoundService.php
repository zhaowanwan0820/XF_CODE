<?php

 /**
 * DealProjectCompound class file.
 * 利滚利项目业务逻辑
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace core\service;

use core\dao\CompoundRedemptionApplyModel;
use core\dao\DealLoanRepayModel;
use core\dao\DealProjectCompoundModel;
use core\dao\DealProjectModel;
use libs\utils\Logger;

/**
 * DealProjectCompound service
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/
class DealProjectCompoundService extends BaseService {
    /**
     * 根据project_id获取已赎回本金
     * @param int $project_id
     * @return float 
     */
    public static function getPayedProjectCompoundPrincipal($project_id) {
        $rs = DealLoanRepayModel::instance()->getTotalMoneyByTypeProjectId($project_id, DealLoanRepayModel::MONEY_COMPOUND_PRINCIPAL, DealLoanRepayModel::STATUS_ISPAYED);  
        return empty($rs) ? 0 : $rs;
    }
 
    /**
     * 根据project_id获取未赎回本金
     * @param int $project_id
     * @return float 
     */
    public static function getUnpayedCompoundPrincipal($project_id) {
        $rs = DealLoanRepayModel::instance()->getTotalMoneyByTypeProjectId($project_id, DealLoanRepayModel::MONEY_COMPOUND_PRINCIPAL, DealLoanRepayModel::STATUS_NOTPAYED);  
        return empty($rs) ? 0 : $rs;
    }

    /**
     * 根据project_id获取已赎回利息
     * @param int $project_id
     * @return float 
     */
    public static function getPayedCompoundInterest($project_id) {
        $rs = DealLoanRepayModel::instance()->getTotalMoneyByTypeProjectId($project_id, DealLoanRepayModel::MONEY_COMPOUND_INTEREST, DealLoanRepayModel::STATUS_ISPAYED);  
        return empty($rs) ? 0 : $rs;
    }
 
    /**
     * 根据project_id获取未赎回利息
     * @param int $project_id
     * @return float 
     */
    public static function getUnpayedCompoundInterest($project_id) {
        $rs = DealLoanRepayModel::instance()->getTotalMoneyByTypeProjectId($project_id, DealLoanRepayModel::MONEY_COMPOUND_INTEREST, DealLoanRepayModel::STATUS_NOTPAYED);  
        return empty($rs) ? 0 : $rs;
    }

    /**
     * 项目的还款计划
     * @param $project_id
     * @return array
     */
    public function getRepaySchedule($project_id) {
        $deal_project_compound = DealProjectCompoundModel::instance()->getInfoByProId($project_id);
        $now = to_timespan(date('Y-m-d'));
        $repay_day_start = $now + 86400; // 第二天
        $repay_day_end = $now + $deal_project_compound['redemption_period'] * 86400;
//        $deal_compound_service = new DealCompoundService();
//        while ($deal_compound_service->checkIsHoliday(to_date($repay_day_end, 'Y-m-d'))) {
//            $repay_day_end += 86400; //叠加顺延时间
//        }
        $compound_redemption_apply_model = new CompoundRedemptionApplyModel();
        $apply_stat_list = $compound_redemption_apply_model->getRepayScheduleByProjectId($project_id, $repay_day_start, $repay_day_end);
        $result = array();
        for ($repay_day = $repay_day_start; $repay_day < $repay_day_end; $repay_day += 86400) {
            $repay_day_str = to_date($repay_day, $format = 'Y-m-d');
            $repay_money = (empty($apply_stat_list) || empty($apply_stat_list[$repay_day_str])) ? 0 : $apply_stat_list[$repay_day_str];
            $result[] = array('day' => $repay_day_str, 'money' => $repay_money);
        }
        return $result;
    }

}
