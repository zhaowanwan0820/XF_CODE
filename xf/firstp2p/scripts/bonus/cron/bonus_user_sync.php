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
use core\event\Bonus\BonusUserSyncEvent;
use libs\lock\LockFactory;
use core\service\bonus\BonusUser;
use core\dao\BonusUserModel;

//$event = new BonusUserSyncEvent(201452, 1435939200);
//$event->execute();
//
//exit("done\n");

//$lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
//$lock_key = 'bonus_user_sync_script';
//if (!$lock->getLock($lock_key, 3600)) { //防止重复执行
//    return false;
//}
class BonusUserSync {

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
        if ($before_days <= 0) { //设置整理多久之前的数据
            exit("error Params!\n");
        }
        $this->before_time = strtotime(date('Y-m-d', strtotime("-{$before_days} days")));
    }

    public function run() {

        $time = time();
        $count_sql = 'SELECT count(distinct(owner_uid)) FROM firstp2p_bonus WHERE owner_uid > 0 && `created_at` < %s && `expired_at` < %s';
        $total_count = \core\dao\BonusModel::instance()->countBySql(sprintf($count_sql, $this->before_time, $time), array(), true);
        $pages = ceil($total_count / $this->count);

        $bonus_user = new BonusUser();
        $bonus_user_model = new BonusUserModel();
        for ($page = 0; $page < $pages; $page++) {
            $sql = 'SELECT distinct(owner_uid) FROM firstp2p_bonus WHERE owner_uid > 0 && `created_at` < %s && `expired_at` < %s ORDER BY owner_uid ASC LIMIT %s, %s';
            $sql = sprintf($sql, $this->before_time, $time, ($page * $this->count), $this->count);
            $result = \core\dao\BonusModel::instance()->findAllBySql($sql, true, array(), true);
            foreach ($result as $row) {
                /*$info = $bonus_user_model->getUser($row['owner_uid']);
                $get = $bonus_user->getTotalBonus($row['owner_uid'], intval($info['till_get_bonus_id']), $this->before_time);
                $send= $bonus_user->getTotalBonusSend($row['owner_uid'], intval($info['till_send_bonus_id']), $this->before_time);
                if (empty($get) && empty($send)) {
                    continue;
                }*/

                $event = new BonusUserSyncEvent($row['owner_uid'], $this->before_time);
                $obj = new GTaskService();
                $event_res = $obj->doBackground($event, 1, TASK::PRIORITY_NORMAL, null, 'domq_bonus');
                if ($event_res) {
                    $this->succ_count++;
                }
            }
        }
    }
}

$bonus_user_sync = new BonusUserSync();
$bonus_user_sync->run();
echo date('Y-m-d H:i:s'), "\tBonusUserSync成功放入队列共", $bonus_user_sync->succ_count, "\n";

