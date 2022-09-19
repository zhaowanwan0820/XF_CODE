<?php
/**
 * coupon_compound_repay_time_repair.php
 *
 * 通知贷优惠码错误的赎回到账时间修复
 *
 * @date 2015-07-22
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace scripts;

use core\dao\CompoundRedemptionApplyModel;
use core\dao\CouponLogModel;
use core\dao\CouponPayLogModel;
use core\dao\DealModel;
use core\service\CouponLogService;
use libs\utils\Logger;

require_once(dirname(__FILE__) . '/../app/init.php');

set_time_limit(0);

class CouponCompoundRepayTimeRepair {

    public static $count_success = 0;
    public static $count_fail = 0;

    public function run($is_update){
        $log_info = array(__CLASS__, __FUNCTION__, $is_update);
        Logger::info(implode(" | ", array_merge($log_info, array('start'))));
        $sql = "select l.id from firstp2p_compound_redemption_apply a, firstp2p_coupon_log l where a.deal_load_id=l.deal_load_id and a.repay_time!=l.deal_repay_time";
        $list= $GLOBALS['db']->get_slave()->getAll($sql);
        if (empty($list)) {
            Logger::error(implode(" | ", array_merge($log_info, array('empty list'))));
        }
        
        Logger::info(implode(" | ", array_merge($log_info, array('count:', count($list)))));
        foreach($list as $item){
            $rs = $this->fixRepayTime($item['id'], $is_update);
            if (empty($rs)) {
                Logger::info(implode(" | ", array_merge($log_info, array($item['id'],self::$count_fail++,'fail'))));
            }else {
                Logger::info(implode(" | ", array_merge($log_info, array($item['id'],self::$count_success++,'success'))));
            }
        }
        Logger::info(implode(" | ", array_merge($log_info, array(self::$count_success, self::$count_fail, 'done'))));

    } 

    public function fixRepayTime($coupon_log_id, $is_update){
        $log_info = array(__CLASS__, __FUNCTION__, $coupon_log_id);
        Logger::info(implode(" | ", array_merge($log_info, array('start'))));
        $coupon_log = CouponLogModel::instance()->find($coupon_log_id);
        $deal_load_id = $coupon_log['deal_load_id'];
        $deal = DealModel::instance()->find($coupon_log['deal_id'], 'repay_time, repay_start_time');
        $compound_apply = CompoundRedemptionApplyModel::instance()->getApplyByDealLoanId($deal_load_id);
        if (empty($coupon_log) || empty($deal) || empty($compound_apply)) {
            Logger::error(implode(" | ", array_merge($log_info, array('error id'))));
            return false;
        }
        $deal->repay_time = $deal->repay_time * 86400 + $deal->repay_start_time;
        $log_info[] = json_encode($deal->getRow());
        $log_info[] = json_encode($coupon_log->getRow());
        $log_info[] = json_encode($compound_apply->getRow());
        if ($compound_apply['repay_time'] >= $coupon_log['deal_repay_time']) {
            Logger::error(implode(" | ", array_merge($log_info, array('error deal_repay_time'))));
            return false;
        }
        if ($coupon_log['deal_repay_time'] != $deal['repay_time']) {
            Logger::error(implode(" | ", array_merge($log_info, array('error paid deal_repay_time'))));
            return false;
        }

        if ($coupon_log['pay_status'] == 2) {
            if ($coupon_log['rebate_days']){
                Logger::error(implode(" | ", array_merge($log_info, array('error paid rebate_days'))));
                return false;
            }
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $coupon_log_id, 'todo item', 'paid already')));
            $coupon_log->deal_repay_days = round(($coupon_log->deal_repay_time - $deal['repay_start_time']) / 86400);
        } else if ($coupon_log['pay_status'] == 5) {
            if ($coupon_log['rebate_days'] != 4) {
                Logger::error(implode(" | ", array_merge($log_info, array('error rebate_days'))));
                return false;
            }
            $coupon_log->rebate_days_update_time = $coupon_log->rebate_days_update_time - ($coupon_log->rebate_days * 3600 * 24);
            $coupon_log->rebate_days = 0;
            $coupon_log->deal_repay_time = ($compound_apply['repay_time'] >= $coupon_log->rebate_days_update_time) ? $compound_apply['repay_time'] : $coupon_log->rebate_days_update_time;
            $coupon_log->pay_status = ($compound_apply['repay_time'] >= $coupon_log->rebate_days_update_time) ? 5 : 2;
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $coupon_log_id, 'todo item', 'paying')));
        } else if ($coupon_log['pay_status'] == 0) {
            $coupon_log->deal_repay_time = $compound_apply['repay_time'];
            $coupon_log->deal_repay_days = round(($compound_apply['repay_time'] - $deal['repay_start_time']) / 86400);
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $coupon_log_id, 'todo item', 'unpaid')));
        } else {
            Logger::error(implode(" | ", array_merge($log_info, array('error pay_status'))));
            return false;
        }

        $coupon_log->deal_repay_days = round(($coupon_log->deal_repay_time - $deal['repay_start_time']) / 86400);

        // 更新返点比例金额
        $repay_days = round(($coupon_log->deal_repay_time - $deal['repay_start_time']) / 86400);
        $coupon_log->rebate_ratio_amount = CouponLogService::calRatioAmount($coupon_log->rebate_ratio, $coupon_log, $repay_days);
        $coupon_log->referer_rebate_ratio_amount = CouponLogService::calRatioAmount($coupon_log->referer_rebate_ratio, $coupon_log, $repay_days);
        $coupon_log->agency_rebate_ratio_amount = CouponLogService::calRatioAmount($coupon_log->agency_rebate_ratio, $coupon_log, $repay_days);

        $coupon_log->update_time = get_gmtime();

        $log_info[] = json_encode($coupon_log->getRow());

        if ($is_update) {
            $rs = $coupon_log->save();
        }
        Logger::info(implode(" | ", array_merge($log_info, array($rs, 'done'))));
        return $rs;
    }

}

$repair = new CouponCompoundRepayTimeRepair();
$repair->run($argv[1]);


