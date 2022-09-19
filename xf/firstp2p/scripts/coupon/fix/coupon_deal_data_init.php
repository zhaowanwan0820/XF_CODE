<?php
/**
 * coupon_deal_data_init.php
 *
 * 新建coupon_deal表后，初始化标结算配置数据，只上线后执行一次
 *
 * @date 2015-03-16
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

require_once(dirname(__FILE__) . '/../app/init.php');

set_time_limit(0);
$coupon_deal_service = new \core\service\CouponDealService();
$coupon_deal_service->initCouponDealData();
