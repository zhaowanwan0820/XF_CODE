<?php
/**
 *-----------------------------------------------------------------------
 * 1、红包表数据迁移，将数据按照OWNER_UID分散到到32张表中
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
use core\event\Bonus\BonusHashSyncEvent;
//use libs\lock\LockFactory;
use core\dao\BonusUserModel;

//$lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
//$lock_key = 'bonus_hash_sync_script';
//if (!$lock->getLock($lock_key, 3600)) { //防止重复执行
//    return false;
//}

class BonusHashSync {

    /**
     * 整理天数
     */
    private $before_time = 30;

    /**
     * 分页
     */
    private $count = 10000;

    public $succ_count = 0;

    public function __construct($before_days = 30) {
        $this->before_time = strtotime(date('Y-m-d', strtotime("-{$before_days} days")));
    }

    public function run() {

        $time = time();
        $bonus_id = 0;
        $count_sql = 'SELECT COUNT(*) FROM firstp2p_bonus WHERE id > %s && owner_uid > 0 && `created_at` < %s && `expired_at` < %s';
        $total_count = \core\dao\BonusModel::instance()->countBySql(sprintf($count_sql, $bonus_id, $this->before_time, $time), array(), true);
        $pages = ceil($total_count / $this->count);
        $bonus_user = new BonusUserModel();

        for ($page = 0; $page < $pages; $page++) {
            $sql = 'SELECT id, owner_uid, sender_uid FROM firstp2p_bonus WHERE id > %s && owner_uid > 0 && `created_at` < %s && `expired_at` < %s ORDER BY id ASC LIMIT %s, %s';
            $sql = sprintf($sql, $bonus_id, $this->before_time, $time, ($page * $this->count), $this->count);
            $result = \core\dao\BonusModel::instance()->findAllBySql($sql, true, array(), true);
            foreach ($result as $row) {
                if ($row['owner_uid'] <= 0) { // 保证用户已经整理到bonus_user表中
                    continue;
                }
                $owner = $bonus_user->getUser($row['owner_uid']);
                if (empty($owner)) {
                    continue;
                }
                if ($row['sender_uid'] > 0) { // 保证用户已经整理到bonus_user表中
                    $sender = $bonus_user->getUser($row['sender_uid']);
                    if (empty($sender)) {
                        continue;
                    }
                }

                $event = new BonusHashSyncEvent($row['id']);
                $obj = new GTaskService();
                $event_res = $obj->doBackground($event, 1, TASK::PRIORITY_NORMAL, null, 'domq_bonus');//红包队列
                if ($event_res) {
                    $this->succ_count++;
                }
            }
        }
    }
}

$bonus_hash_sync = new BonusHashSync();
$bonus_hash_sync->run();
echo date('Y-m-d H:i:s'), "\tBonusHashSync成功放入队列共", $bonus_hash_sync->succ_count, "\n";

