<?php
/**
 * 工作日自动放款脚本
 * 规则：
 *      1. 工作日；
 *      2. 时间段：00:00-16：00；
 */

require_once(dirname(__FILE__) . '/../app/init.php');

use core\dao\DealModel;
use core\dao\DealLoanTypeModel;

use core\service\DealService;

use libs\utils\Logger;

set_time_limit(0);
ini_set('memory_limit', '2048M');
FP::import("libs.common.dict");

$deal_loans_obj = new DealLoansMake();
if ($deal_loans_obj->isTimeToDo()) {
    $deal_loans_obj->run();
} else {
    Logger::info('this is not valid time! [%s:%s]', __FILE__, __LINE__);
}
exit(0);

/**
 * 放款
 */
class DealLoansMake
{
    /**
     * 是否为可执行时间
     * @return boolean
     */
    public function isTimeToDo()
    {
        return true;

        $begin_time = strtotime(date('Y-m-d') . ' 00:00:00 ');
        $end_time = strtotime(date('Y-m-d') . ' 16:00:00 ');
        $now = time();
        if ($now < $begin_time || $now > $end_time) {
            return false;
        }

        // 看假期表中是否有这条日期记录
        $holidays_info = dict::get('REDEEM_HOLIDAYS');

        return !in_array(date('Y-m-d'), $holidays_info);
    }

    public function run()
    {
        $deal_type_kongfu = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_XJDGFD);
        if(empty($deal_type_kongfu)) {
            Logger::warn(sprintf('no this loan_type:%s [%s:%s]', DealLoanTypeModel::TYPE_XJDGFD, __FILE__, __LINE__));
            return;
        }

        $condition = sprintf(' `is_delete` = 0  AND `type_id` = %d AND `deal_status` = %d ', $deal_type_kongfu, DealModel::$DEAL_STATUS['full']);
        $dealList = DealModel::instance()->findAll($condition, true,'*');
        // 对标的列表执行放款操作
        $deal_service = new DealService();
        foreach ($dealList as $dealInfo) {
            if ($deal_service->autoMakeLoans($dealInfo)) {
                Logger::info(sprintf('success:deal_id:%s, func:%s [%s:%s]', $dealInfo['id'], __FUNCTION__, __FILE__, __LINE__));
            } else {
                Logger::error(sprintf('fail:deal_id:%s, func:%s [%s:%s]', $dealInfo['id'], __FUNCTION__, __FILE__, __LINE__));
            }
        }
    }
}
