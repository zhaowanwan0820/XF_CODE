<?php
/**
 * coupon_update_rebate_days.php
 *
 * 通知贷优惠码每日更新叠加返利天数程序
 * 0 1 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php coupon_update_rebate_days.php
 * 
 * @date 2015-02-04
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

require_once(dirname(__FILE__) . '/../../app/init.php');

set_time_limit(0);
$coupon_log_service = new \core\service\CouponJobsService();
$coupon_log_service->addTaskUpdateRebateDaysAll();
