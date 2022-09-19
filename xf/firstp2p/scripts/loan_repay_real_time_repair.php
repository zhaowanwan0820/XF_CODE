<?php
/**
 * 将loan_repay 表中的real_time 都修正为当天零点的那一刻
 * 方便进行检索查询
 * User: jinhaidong
 * Date: 2016-03-29 11:23
 */
require_once dirname(__FILE__).'/../app/init.php';


class LoanRepayRealTimeRepair {
    public function run() {
        global $argv;

        $beginId = intval($argv[1]); // ID 起始位置
        if(!$beginId) {
            echo "Please input right begin id\n";
            exit;
        }

        $maxId = intval($argv[2]); // loan_repay 表的最大ID
        if($maxId <= 0 ) {
            echo "Please input right max id\n";
            exit;
        }

        $offset = intval($argv[3]); // 每次更新数量
        if($offset <= 0 ) {
            echo "Please input right offset\n";
            exit;
        }

        while(true) {
            $tmpMaxId = $beginId + $offset;
            $sql = "UPDATE `firstp2p_deal_loan_repay` SET real_time=UNIX_TIMESTAMP(FROM_UNIXTIME(real_time+28800,'%Y-%m-%d'))-28800 where id >=$beginId AND id <$tmpMaxId AND status=1 AND real_time !=0 ORDER BY id ASC";

            $res = $GLOBALS['db']->query($sql);
            if(!$res) {
                \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "loan_repay real_time repair fail maxId:".$tmpMaxId)));
                break;
            }
            if($tmpMaxId >= $maxId) {
                \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "loan_repay real_time repair finish maxId:".$tmpMaxId)));
                break;
            }
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "loan_repay real_time repair success maxId:".$tmpMaxId)));
            $beginId+=$offset;
        }
    }
}

//error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 0);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$obj = new LoanRepayRealTimeRepair();
$obj->run();