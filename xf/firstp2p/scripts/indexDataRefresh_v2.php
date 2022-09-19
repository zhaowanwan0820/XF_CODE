<?php
/**
 * 异步刷新cache数据
 */

use libs\rpc\Rpc;
use libs\utils\Logger;
require_once dirname(__FILE__).'/../app/init.php';
set_time_limit(0);

$pid = posix_getpid();
$cmd = "ps aux | grep \"indexDataRefresh.php\" | grep -v {$pid} | grep -v grep | grep -v /bin/sh";
$handle = popen($cmd, "r");
$str = fread($handle, 1024);
if ($str) {
    echo sprintf("index refrest  is running  ~! \n");
    exit(0);
}

$rpc = new Rpc();

$pcStart = microtime(true);

// 判断要刷的站点是否为主站, 不是的话重新加载配置, 标列表刷新对应站点
$siteId = isset($argv[1]) ? $argv[1] : 1;
// 加载数据库配置
if ($siteId != 1 && !empty($GLOBALS['sys_config_db'][$siteId])) {
    $GLOBALS['sys_config'] = array_merge($GLOBALS['sys_config'], $GLOBALS['sys_config_db'][$siteId]);
    if (!empty($GLOBALS['sys_config_db'][0])) {
        $GLOBALS['sys_config'] = array_merge($GLOBALS['sys_config'], $GLOBALS['sys_config_db'][0]); // 公用配置
        $GLOBALS['sys_config']['TEMPLATE_ID'] = $siteId;
    }
}

// 加载Open配置
$openAppConf = \libs\web\Open::getAppBySiteId($siteId);
if (!empty($openAppConf)) {
    \libs\web\Open::coverSiteInfo($openAppConf);
}

// 如果不是主站，但配置和主站一样，不刷新
if ($siteId != 1 && $GLOBALS['sys_config']['DEAL_SITE_ALLOW'] == $GLOBALS['sys_config_db'][1]['DEAL_SITE_ALLOW']) {
    Logger::info("site {$siteId} index page refresh v2 err, does't need refresh cache");
    exit(0);
}

$supervisionSwitch = false;
// TODO 分站不显示存管标的
if($siteId == 1 && (int)app_conf('SUPERVISION_SWITCH') === 1){
    $supervisionSwitch = true;
}

//参考web/controllers/index/Index.php
$webDeal = \SiteApp::init()->dataCache->call($rpc, 'local', array('DealService\getIndexList', array($supervisionSwitch)), 300, true);
if(empty($webDeal)){
    Logger::info("site {$siteId} web index page refresh v2 err");
}else{
    Logger::info("site {$siteId} web index page refresh v2 success");
}
$dt = round(microtime(true) - $pcStart, 4);
if( $dt > 10 ){
    \libs\utils\Alarm::push('pc_index_refresh', 'SITE:'.$siteId.', PC首页刷新异常', sprintf("PC首页刷新超时， 共耗时%s", $dt));
}


//参考api/controllers/deals/DealList.php
//index专项
$appStart = microtime(true);
$webDeal = \SiteApp::init()->dataCache->call($rpc, 'local', array('DealService\getList', array(0,null,null,1,20,false,1,true,'0,1','',false,$supervisionSwitch)), 300, true);
if(empty($webDeal)){
    Logger::info("site {$siteId} app index zx page refresh v2 err");
}else{
    Logger::info("site {$siteId} app index zx page refresh v2 success");
}
$dt = round(microtime(true) - $appStart, 4);
if( $dt > 10 ){
    \libs\utils\Alarm::push('app_index_refresh', 'SITE:'.$siteId.', APP首页专享刷新异常', sprintf("APP首页刷新超时， 共耗时%s", $dt));
}
if ($siteId == 1){
    //未登录主站显示交易所的
    $appStart = microtime(true);
    $webDeal = \SiteApp::init()->dataCache->call($rpc, 'local', array('DealService\getList', array(0,null,null,1,20,false,1,true,'2','',false,$supervisionSwitch)), 300, true);
    if(empty($webDeal)){
        Logger::info("site {$siteId} app index jys page refresh v2 err");
    }else{
        Logger::info("site {$siteId} app index jys page refresh v2 success");
    }
    $dt = round(microtime(true) - $appStart, 4);
    if( $dt > 10 ){
        \libs\utils\Alarm::push('app_index_refresh', 'SITE:'.$siteId.', APP首页交易所刷新异常', sprintf("APP首页刷新超时， 共耗时%s", $dt));
    }
}
//index存管&交易所
$appStart = microtime(true);
$webDeal = \SiteApp::init()->dataCache->call($rpc, 'local', array('DealService\getList', array(0,null,null,1,20,false,1,true,'3,2','',false,$supervisionSwitch)), 300, true);
if(empty($webDeal)){
    Logger::info("site {$siteId} app index p2p page refresh v2 err");
}else{
    Logger::info("site {$siteId} app index p2p page refresh v2 success");
}
$dt = round(microtime(true) - $appStart, 4);
if( $dt > 10 ){
    \libs\utils\Alarm::push('app_index_refresh', 'SITE:'.$siteId.', APP首页p2p刷新异常', sprintf("APP首页刷新超时， 共耗时%s", $dt));
}

sleep(1);
