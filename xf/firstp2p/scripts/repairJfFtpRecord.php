<?php
/**
 * 即付汇款修复脚本
 * User: jinhaidong
 * Date: 2015/9/6 14:02
 */

ini_set('memory_limit', '2048M');
set_time_limit(0);
error_reporting(2047);
ini_set('display_errors',1);

require_once(dirname(__FILE__) . '/../app/init.php');

use core\service\jifu\JfLoanRepayService;

class RepairJfFtpRecord {

    public function run($method,$deal_id,$deal_repay_id) {
        $res = $this->$method($deal_id,$deal_repay_id);
        if($res) {
            echo "Repair success\n";
        }else{
            echo "Repair error\n";
        }
    }

    private function normalRepay($dealId,$repayId) {
        return JfLoanRepayService::instance()->syncNormalToJf($dealId,$repayId);
    }

    private function prepayRepay($dealId,$repayId) {
        return JfLoanRepayService::instance()->syncPrepayToJf($dealId,$repayId);
    }

    private function compoundRepay($dealId,$repayId) {
        return JfLoanRepayService::instance()->syncCompoundToJf($dealId,$repayId);
    }
}

global $argv;
$class = new RepairJfFtpRecord();

$methodList = array('normalRepay','prepayRepay','compoundRepay');
if(!isset($argv[1])) {
    exit("Please input the method name:\nMethods list:".implode("|",$methodList)."\n");
}

if(!in_array($argv[1],$methodList)) {
    exit("Please input the valid method:\n".implode("|",$methodList));
}

if(!isset($argv[2])) {
    exit("Please input the deal_id:\n");
}

if(!isset($argv[3])) {
    exit("Please input the deal_repay_id:\n");
}
$class->run($argv[1],$argv[2],$argv[3]);