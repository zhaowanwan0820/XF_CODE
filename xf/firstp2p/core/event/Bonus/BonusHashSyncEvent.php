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
use core\service\bonus\BonusUser;

/**
 * BonusUserEvent
 * 根据红包生成时间整理红包账户表
 *
 * @uses AsyncEvent
 * @package default
 */
//ini_set('display_errors', 1);
//error_reporting(E_ERROR);
class BonusHashSyncEvent extends BaseEvent
{

    private $bonus_id = 0;
    public function __construct($bonus_id) {
        $this->bonus_id = intval($bonus_id);
    }

    public function execute() {

        if (!$this->bonus_id) {
            throw new \Exception("红包id不能为空。");
        }
        //$lockKey = "bonus_hash_sync_event_".$this->user_id;
        //$lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        //if (!$lock->getLock($lockKey, 120)) {
        //    throw new \Exception("user_id={$this->user_id}|记录被锁定");
        //}

        $log_file = APP_ROOT_PATH.'/log/logger/bonus_hash_sync_'.date('y_m_d').'.log';

        $result = \core\dao\BonusModel::instance()->find($this->bonus_id, '*', true);
        if (!is_object($result)) {
            return true;
        }
        $result = $result->getRow();
        if (empty($result['owner_uid'])) {
            throw new \Exception("获取红包失败id={$this->bonus_id}");
        }
        $data = array_filter($result, create_function('$v','return !empty($v);'));

        if ($result['status'] == 2) {
            $used = \core\dao\BonusUsedModel::instance()->findBy("bonus_id=".$this->bonus_id, 'deal_load_id, consume_id, used_at', array(), true);
            if (!is_object($used)) {
                return true;
            }
            $used = $used->getRow();
            if (empty($used)) {
                throw new \Exception("获取红包消费信息失败id={$this->bonus_id}");
            }
            $data['used_at'] = $used['used_at'];
            if ($used['deal_load_id'] > 0) {
                $data['consume_type'] = 1;
                $data['consume_id'] = $used['deal_load_id'];
            } else {
                $data['consume_type'] = 2;
                $data['consume_id'] = $used['consume_id'];
            }
        }

        if ($result['group_id'] > 0) { //保证红包组数据已经同步
            $group_info = \core\dao\BonusGroupModel::instance()->findBy("id=".$result['group_id'], 'get_count', array(), true);
            if ($group_info['get_count'] <= 0) {
                throw new \Exception("红包组的领取信息没有同步group_id=".$result['group_id']);
            }
        }

        $table = 'firstp2p_bonus_' . BonusUser::getTableId($result['owner_uid']);
        unset($data['id']);
        $data['bonus_id'] = $this->bonus_id;

        $insert_sql = sprintf('INSERT INTO %s (%s) VALUES(%s)', $table, implode(',', array_keys($data)), "'".implode("','", array_values($data))."'");
        $delete_sql = sprintf('DELETE FROM firstp2p_bonus WHERE id=%s LIMIT 1', $this->bonus_id);

        try {
            $GLOBALS['db']->startTrans();
            $res = $GLOBALS['db']->query($insert_sql);
            if (!$res) {
                throw new \Exception('插入数据失败!');
            }
            $res = $GLOBALS['db']->query($delete_sql);
            if (!$res) {
                throw new \Exception('删除数据失败!');
            }
            $GLOBALS['db']->commit();
        } catch(\Exception $e) {
            $res = 0;
            $GLOBALS['db']->rollBack();
        }

        Logger::wLog("result=$res\trow=".json_encode($result), Logger::INFO, Logger::FILE, $log_file);
        return true;
    }

    public function alertMails() {
        return array('wangshijie@ucfgroup.com');
    }

}
