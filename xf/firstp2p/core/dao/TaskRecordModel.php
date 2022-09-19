<?php
/**
 * TaskRecordModel class file.
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 **/

namespace core\dao;

/**
 * TaskRecordModel class
 *
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 **/
class TaskRecordModel extends BaseModel {

    /**
     * 创建任务
     * @param taskId 任务id
     * @return bool
     */
    public function createRecord($taskId) {
        $record = TaskRecordModel::instance();
        $record->setRow(array('task_id' => $taskId, 'create_time' => time()));
        return $record->save();
    }

    /**
     * 查询任务是否存在
     * @param taskId integer 任务id
     * @return bool
     */
    public function queryRecord($taskId) {
        $condition = sprintf(" task_id = '%d' ", $taskId);
        $record = $this->findBy($condition, 'id', null, true);
        return !empty($record) ? true : false;
    }

    /**
     * 删除任务记录
     * @param taskId integer 任务ID
     * @return bool
     */
    public function deleteRecord($taskId) {
        return $this->db->query("DELETE FROM firstp2p_task_record WHERE task_id = '{$taskId}'");
    }

}
