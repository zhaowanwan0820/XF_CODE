<?php
/**
 * 优惠码邀请用户投资数据
 * 0 1 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts && /apps/product/php/bin/php statistics_coupon_load_data.php
 *
 * @author wenyanlei@ucfgroup.com
 **/
require_once dirname(__FILE__).'/../app/init.php';

$coupon_stat_service = new \core\service\CouponStatService();
$result = $coupon_stat_service->makeCouponLoadData();
//echo implode("\r\n", $result['msg']);
