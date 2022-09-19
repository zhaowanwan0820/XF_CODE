<?php
/**
 * 分站异步刷新cache数据
 */

use libs\rpc\Rpc;
use libs\utils\Logger;
require_once dirname(__FILE__).'/../app/init.php';
set_time_limit(0);

$pid = posix_getpid();
$cmd = "ps aux | grep \"fenzhanDataRefresh.php\" | grep -v {$pid} | grep -v grep | grep -v /bin/sh";
$handle = popen($cmd, "r");
$str = fread($handle, 1024);
if ($str) {
    echo sprintf("fenzhan refresh  is running  ~! \n");
    exit(0);
}

$rpc = new Rpc();

$pcStart = microtime(true);

$supervisionSwitch = false;
if((int)app_conf('SUPERVISION_SWITCH') === 1){
    $supervisionSwitch = true;
}

//普惠首页 参考 web/controller/deals/Index.php
$cnStart = microtime(true);
$option = array(
    'deal_type' => '0,1',
    'isHitSupervision' => $supervisionSwitch,
);
$cnDeal = \SiteApp::init()->dataCache->call($rpc, 'local', array('DealService\getDealsList', array(null, 1,0,false,0,$option)), 300, true);
$dt = round(microtime(true) - $cnStart, 4);
if( $dt > 10 ){
    \libs\utils\Alarm::push('pc_index_refresh', '普惠首页刷新异常', sprintf("普惠首页刷新超时， 共耗时%s", $dt));
}
sleep(5);