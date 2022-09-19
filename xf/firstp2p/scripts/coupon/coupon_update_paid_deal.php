<?php
/**
 * coupon_update_paid_deal.php.
 *
 * 定时更新已经结清的标的状态
 * 10 8-23 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php coupon_update_paid_deal.php
 *
 * @date 2015-03-12
 *
 * @author liangqiang <liangqiang@ucfgroup.com>
 */
require_once dirname(__FILE__).'/../../app/init.php';

set_time_limit(0);
ini_set('memory_limit', '1024M');

$couponJobsService = new \core\service\CouponJobsService();
$couponJobsService->updatePaidDeals();
$couponJobsService->updatePaidDealsNcfph();
$couponJobsService->updatePaidDealsThird();
