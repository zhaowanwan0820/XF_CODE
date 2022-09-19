<?php
//crontab: */1 * * * * cd /apps/product/nginx/htdocs/firstp2p/scripts && /apps/product/php/bin/php update_deal_status.php

/**
 * 定时检查并同步deal状态
 *
 * @return void
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';
require_once dirname(__FILE__).'/../libs/common/functions.php';

set_time_limit(0);

// 先检查是否已经有处理进程存在，存在则本进程退出
$pid = posix_getpid();
$cmd = "ps aux | grep update_deal_status.php | grep -v grep | grep -v {$pid} | grep -v /bin/sh";
$handle = popen($cmd, "r");
$str = fread($handle, 1024);
if ($str) {
    echo "进程已经启动\n";
    exit;
}

//检查并同步deal状态
syn_dealing();
