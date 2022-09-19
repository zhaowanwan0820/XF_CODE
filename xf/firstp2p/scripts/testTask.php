<?php
/**
 * generate_income.php
 *
 * @date 2014年11月3日
 * @author yangqing <yangqing@ucfgroup.com>
 * #定时任务脚本，计算各个分站的收益统计，存入缓存
 */

namespace scripts;

use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\TestEvent;

echo "\t*** App start ".date('Y-m-d H:i:s')." ***\n";

set_time_limit(0);
//error_reporting(0);
//ini_set('display_errors', 1);

require(dirname(__FILE__) . '/../app/init.php');
//require(dirname(__FILE__) . '/init.php');
system('/etc/init.d/gearman-manager-p2p restart &> /dev/null');

class Test {

    public function __construct() {
    }

    public function process() {
        $obj = new GTaskService();
        $i = 0;
        while ($i < 3) {
            $event = new TestEvent(81);
            $obj->doBackground($event);
            print("add task {$i} \n");

            $i ++;
        }

    }

    // 输出日志
    private function _log($msg) {
        echo "[".date('Y-m-d H:i:s')."]$msg\n";
    }

    // 输出错误日志
    private function _error($msg){
        echo "\n**** ERROR : $msg ****\n";
    }
}

(new Test)->process();

