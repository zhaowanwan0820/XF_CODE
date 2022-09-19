<?php
namespace core\service;

use NCFGroup\Task\Services\BaseTaskClient;
use NCFGroup\Task\Services\TaskService;
use NCFGroup\Task\Models\Task;
use NCFGroup\Task\Events\AsyncEvent;
use NCFGroup\Task\Gearman\WxGearManWorker;
use NCFGroup\Common\Library\Date\XDateTime;
use core\dao\TaskRecordModel as TaskRecord;

/**
 * PtpTaskClient 
 */
class PtpTaskClient extends BaseTaskClient {
    /**
     * @param $taskId
     * @return bool true | false
     * 创建任务在业务库本地的记录
     */
    public function createLocalTaskRecord($taskId) {
        $taskRecord = new TaskRecord();
        return $taskRecord->createRecord($taskId);
    }

    /**
     * @param $taskId
     * @return bool true | false
     * 查询任务在业务库执行的情况
     */
    public function queryLocalTaskRecord($taskId) {
        $taskRecord = new TaskRecord();
        return $taskRecord->queryRecord($taskId);
    }

    /**
     * @param $taskId
     * @return mixed
     * 删除任务在业务库的记录。
     */
    public function deleteLocalTaskRecord($taskId) {
        $taskRecord = new TaskRecord();
        $taskRecord->deleteRecord($taskId);
    }
}
