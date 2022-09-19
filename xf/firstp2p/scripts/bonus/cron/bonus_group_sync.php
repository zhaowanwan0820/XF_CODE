<?php
/**
 *-----------------------------------------------------------------------
 * 1、红包账户整理
 *-----------------------------------------------------------------------
 * @version 1.0 Wang Shi Jie <wangshijie@ucfgroup.com>
 *-----------------------------------------------------------------------
 */

//ini_set('display_errors', 1);
//error_reporting(E_ERROR);

set_time_limit(0);
ini_set('memory_limit', '1024M');
require_once dirname(__FILE__).'/../../../app/init.php';

use NCFGroup\Task\Services\TaskService AS GTaskService;
use NCFGroup\Task\Models\Task;
use core\event\Bonus\BonusGroupSyncEvent;
use libs\lock\LockFactory;

//$lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
//$lock_key = 'bonus_group_sync_script';
//if (!$lock->getLock($lock_key, 3600)) { //防止重复执行
//    return false;
//}

class BonusGroupSync {

    /**
     * 整理天数
     */
    private $before_time = 30;

    /**
     * 分页
     */
    private $count = 10000;

    public $succ_count = 0;

    public function __construct($before_days = 20) {
        if ($before_days <= 0) { //设置整理多久之前的数据
            exit("error Params!\n");
        }
        $this->before_time = strtotime(date('Y-m-d', strtotime("-{$before_days} days")));
    }

    public function run() {

        $time = time();

        $count_sql = 'SELECT count(distinct(A.id)) FROM `firstp2p_bonus_group` A INNER JOIN firstp2p_bonus B ON A.id=B.group_id where A.created_at < %s && A.expired_at < %s && A.get_count = 0 && B.status > 0';
        $count_sql = sprintf($count_sql, $this->before_time, $time);
        $total_count = \core\dao\BonusModel::instance()->countBySql($count_sql, array(), true);
        $pages = intval(ceil($total_count / $this->count));

        for ($page = 0; $page < $pages; $page++) {
            $sql = 'SELECT distinct(A.id) FROM `firstp2p_bonus_group` A INNER JOIN firstp2p_bonus B ON A.id=B.group_id where A.created_at < %s && A.expired_at < %s && A.get_count = 0 && B.status > 0 ORDER BY A.id ASC LIMIT %s, %s';
            $sql = sprintf($sql, $this->before_time, $time, ($page * $this->count), $this->count);
            $result = \core\dao\BonusGroupModel::instance()->findAllBySql($sql, true, array(), true);
            foreach ($result as $row) {
                /*$bonus = \core\dao\BonusModel::instance()->findAllViaSlave("group_id=".$row['id'] . " AND status > 0", true);
                if (empty($bonus)) { //过滤没有领取的红包
                    continue;
                }*/

                $event = new BonusGroupSyncEvent($row['id']);
                $obj = new GTaskService();
                $event_res = $obj->doBackground($event, 1, TASK::PRIORITY_NORMAL, null, 'domq_bonus');
                if ($event_res) {
                    $this->succ_count++;
                    $new_group_id = $row['id'];
                }
            }
        }
    }
}

$bonus_group_sync = new BonusGroupSync();
$bonus_group_sync->run();
echo date('Y-m-d H:i:s'), "\tBonusUserSync成功放入队列共", $bonus_group_sync->succ_count, "\n";
