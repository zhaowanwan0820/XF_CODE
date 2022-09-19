<?php
/**
 *-------------------------------------------------------
 * 按照用户组信息以及标签信息批量给用户发送红包
 *-------------------------------------------------------
 * 2014-12-29 17:05:35
 *-------------------------------------------------------
 */

namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use core\dao\MsgBoxModel;
use core\dao\BonusJobsModel;
use core\dao\UserModel;
use core\service\BonusJobsService;
use core\service\BonusService;
use libs\utils\Logger;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use NCFGroup\Task\Models\Task;
use core\event\BonusSingleEvent;
use core\event\BaseEvent;

/**
 * BonusBatchEvent
 * 批量发红包
 *
 * @uses AsyncEvent
 * @package default
 */
class BonusBatchEvent extends BaseEvent
{

    public function __construct($id) {
        $this->id = $id;
    }

    public function execute() {

        $id = intval($this->id);
        $job = BonusJobsModel::instance()->find($id);
        if (!$job) {
            return false;
        }

        $log_file = APP_ROOT_PATH.'/'.BonusJobsService::JOB_LOG_PREFIX.$id.'_'.date('y_m_d').'.log';
        if($job['is_effect'] == 0) {
            Logger::wLog('任务为无效状态', Logger::INFO, Logger::FILE, $log_file);
            return false;
        }

        if($job['end_time'] < get_gmtime()) {
            Logger::wLog('任务已过期', Logger::INFO, Logger::FILE, $log_file);
            return false;
        }
        $list = UserModel::instance()->getUserListByJob($job['user_group'], $job['user_tag']);
        $list_chunk = array_chunk($list, 500);
        $bonus_single_event = new BonusSingleEvent($job->group_money, $job->bonus_count, $job->group_validity, $job->group_count, $id);
        $gtask_service = new GTaskService();
        $task_count = 0;
        foreach($list_chunk as $list) {
            foreach ($list as $user) {
                $bonus_single_event->setSendUserIds($user['id']);
                $result = $gtask_service->doBackground($bonus_single_event, 1, TASK::PRIORITY_NORMAL);
                if($result){
                    $task_count++;
                }
            }
        }
        Logger::wLog(sprintf("task_count | success:%s", $task_count), Logger::INFO, Logger::FILE, $log_file);
        return true;
    }
    public function alertMails() {
        return array('wangshijie@ucfgroup.com');
    }
}
