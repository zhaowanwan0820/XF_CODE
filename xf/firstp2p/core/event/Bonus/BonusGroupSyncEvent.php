<?php
/**
 *-------------------------------------------------------
 * 红包组冗余领取数以及消费数同步
 *-------------------------------------------------------
 * 2015-06-01 11:05:35
 *-------------------------------------------------------
 */

namespace core\event\Bonus;

use NCFGroup\Task\Events\AsyncEvent;
use libs\lock\LockFactory;
use libs\utils\Logger;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use NCFGroup\Task\Models\Task;
use core\event\BaseEvent;

/**
 * BonusUserEvent
 * 根据红包生成时间整理红包账户表
 *
 * @uses AsyncEvent
 * @package default
 */
//ini_set('display_errors', 1);
//error_reporting(E_ERROR);
class BonusGroupSyncEvent extends BaseEvent
{

    private $group_id = 0;
    public function __construct($group_id) {
        $this->group_id = intval($group_id);
    }

    public function execute() {

        if (!$this->group_id) {
            throw new \Exception("组id不能为空。");
        }
        //$lockKey = "bonus_group_sync_event_".$this->group_id;
        //$lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        //if (!$lock->getLock($lockKey, 120)) {
        //    throw new \Exception("group_id={$this->group_id}|记录被锁定");
        //}

        $log_file = APP_ROOT_PATH.'/log/logger/bonus_group_sync_'.date('y_m_d').'.log';

        $result = \core\dao\BonusModel::instance()->findAllViaSlave("group_id=".$this->group_id. " AND status > 0", true);
        if (empty($result)) {
            //$lock->releaseLock($lockKey);//解锁
            Logger::wLog("msg=红包没有被领取过\tgroup_id={$this->group_id}", Logger::INFO, Logger::FILE, $log_file);
            return true;
            //throw new \Exception("group_id={$this->group_id}|红包组没有被领取过");
        }

        $get = $used = 0; //领用个数与使用个数
        foreach($result as $row) {
            if ($row['status'] >= 1) {
                $get++;
            }

            if ($row['status'] == 2) {
                $used++;
            }
        }
        $sql = sprintf('UPDATE %s SET get_count=%s, used_count=%s WHERE id=%s LIMIT 1', 'firstp2p_bonus_group', $get, $used, $this->group_id);
        $result = \core\dao\BonusGroupModel::instance()->updateRows($sql);
        Logger::wLog("msg=任务执行成功\tresult=$result\tgroup_id={$this->group_id}\tsql=$sql", Logger::INFO, Logger::FILE, $log_file);
        //$lock->releaseLock($lockKey);//解锁
        if (!$result) {
            //$lock->releaseLock($lockKey);//解锁
            throw new \Exception("group_id={$this->group_id}|sql=$sql|同步红包组状态失败!");
        }
        return true;
    }

    public function alertMails() {
        return array('wangshijie@ucfgroup.com');
    }

}
