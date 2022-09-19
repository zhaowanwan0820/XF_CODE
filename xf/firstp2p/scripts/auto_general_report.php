<?php
/**
 * 自动生成对账单程序
 * 每天晚上0点开始执行，生成前一天的对账数据
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 **/

require_once(dirname(__FILE__) . '/../app/init.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('__DEBUG', false);

use core\service\ReportService;

$reportService = new ReportService();

$term = date('Ymd', strtotime('-1 day'));

$reportService->generate($term);
