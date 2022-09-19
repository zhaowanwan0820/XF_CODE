<?php

set_time_limit(0);
/**
 * firstp2p_contract 合同数据迁移
 */
require_once dirname(__FILE__).'/../../app/init.php';
require_once dirname(__FILE__) . '/../../system/utils/logger.php';

use core\dao\ContractModel;
use core\dao\ContractContentModel;
use libs\aerospike\AerospikeSaveObj;
// 先检查是否已经有处理进程存在，存在则本进程退出
$pid = posix_getpid();
$cmd = "ps aux | grep contract_move.php | grep -v grep | grep -v {$pid} | grep -v /bin/sh";
$handle = popen($cmd, "r");
$str = fread($handle, 1024);
if ($str) {
    echo "进程已经启动\n";
    exit;
}

$maxId = intval($argv[1]);
if ( empty($maxId) )exit;
$content = ContractContentModel::instance()->getFromAerospike($maxId);
var_dump($content);

