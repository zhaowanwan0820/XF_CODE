<?php
/**
 * BonusAccountModel class file.
 *
 * @author luzhengshuai@ucfgroup.com
 */

namespace core\dao;

/**
 * 红包配置
 *
 * @author luzhengshuai@ucfgroup.com
 */
class BonusAccountModel extends BaseModel
{
    // 批量发送任务红包
    const TYPE_TASK = 1;

    // 返利规则红包
    const TYPE_RULE = 2;

    // 映射
    public static $taskTypeMap = array(
        self::TYPE_TASK => '批量红包任务',
        self::TYPE_RULE => '红包返利规则'
    );

    public function getAccountByTypeAndId($taskType, $taskId) {
        $condition = ' task_type = '.$taskType. ' AND task_id = ' .$taskId;
        $row = $this->findByViaSlave($condition);
        if (isset($row['account_id'])) {
            return $row['account_id'];
        }
        return false;
    }
}
