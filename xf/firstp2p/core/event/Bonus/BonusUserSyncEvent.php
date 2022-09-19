<?php
/**
 *-------------------------------------------------------
 * 将用户信息整理到红包用户表
 *-------------------------------------------------------
 * 2015-06-01 11:05:35
 *-------------------------------------------------------
 */

namespace core\event\Bonus;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use libs\lock\LockFactory;
use core\service\bonus\BonusUser;
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
class BonusUserSyncEvent extends BaseEvent
{

    private $user_id = 0;
    private $created_at = 0;
    public function __construct($user_id, $created_at) {
        $this->user_id = intval($user_id);
        $this->created_at = intval($created_at);
    }

    public function execute() {

        if (!$this->user_id) {
            throw new \Exception("用户ID为空");
        }
        //$lockKey = "bonus_user_sync_event_".$this->user_id;
        //$lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        //if (!$lock->getLock($lockKey, 120)) {
        //    throw new \Exception("user_id={$this->user_id}|记录被锁定");
        //}

        $log_file = APP_ROOT_PATH.'/log/logger/bonus_user_sync_'.date('y_m_d').'.log';

        $is_new = 1;
        $get_bonus_time = $send_bonus_time = 0;
        $result = \core\dao\BonusUserModel::instance()->findBy("user_id=".$this->user_id, '*', array(), true);
        if (isset($result['till_get_bonus_id']) && isset($result['till_send_bonus_id'])) {
            $is_new = 0;
            $get_bonus_time = $result['till_get_bonus_id'];
            $send_bonus_time = $result['till_send_bonus_id'];
        }

        $bonus_user = new BonusUser();
        $get_result = $bonus_user->getTotalBonus($this->user_id, $get_bonus_time, $this->created_at);
        $send_result = $bonus_user->getTotalBonusSend($this->user_id, $send_bonus_time, $this->created_at);
        if (empty($get_result) && empty($send_result)) {
            //$lock->releaseLock($lockKey);//解锁
            Logger::wLog("msg=该用户无红包user_id=$this->user_id|time=$this->created_at", Logger::INFO, Logger::FILE, $log_file);
            return true;
            //throw new \Exception("user_id={$this->user_id}|bonus_id=$get_bonus_time|$send_bonus_time|获取用户红包列表失败或者该用无红包。");
        }
        $time = time();
        $data = $bonus_user->getBonusData($get_result, $send_result, $result);
        $data['till_get_bonus_id'] = $data['till_send_bonus_id'] = $this->created_at;
        $data['update_time'] = $time;

        if ($is_new) {
            $data['create_time'] = $time;
            $result = $bonus_user->saveUserByUid($this->user_id, $data);
        } else {
            $result = $bonus_user->updateUserByUid($this->user_id, $data);
        }

        //$lock->releaseLock($lockKey);//解锁
        Logger::wLog("msg=任务执行成功\tresult=$result\tis_new=$is_new\tuser_id={$this->user_id}\tdata=".json_encode($data), Logger::INFO, Logger::FILE, $log_file);
        return true;
    }


    public function alertMails() {
        return array('wangshijie@ucfgroup.com');
    }
}
