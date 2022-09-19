<?php
/**
 *
 *  根据白泽推送的数据获取给vip用户打tag
 *  白泽每月5号凌晨4点才能跑完
 *  1 3 6 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/user_tag && /apps/product/php/bin/php set_user_tag_vip.php
 *
 *
 */

require_once dirname(__FILE__).'/../../app/init.php';

use core\service\UserVipService;

ini_set('memory_limit', '400M');
set_time_limit(0);

$user_vip_service = new UserVipService();

$user_vip_service->handleVip();




