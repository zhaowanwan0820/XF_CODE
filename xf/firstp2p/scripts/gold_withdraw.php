<?php
/**
 * 黄金变现脚本
 * User: weiwei12
 * Date: 2017/6/27
 * Time: 下午12:30
 */
require_once dirname(__FILE__).'/../app/init.php';
use core\service\GoldWithdrawService;
use libs\utils\Logger;
use libs\utils\Script;

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');


Script::start();

$goldWithdrawService = new GoldWithdrawService();
$goldWithdrawService->processOrderList();

Script::end();
