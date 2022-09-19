<?php
/**
 * PushTaskModel class file.
 *
 * @author luzhengshuai@ucfgroup.com
 */

namespace core\dao;

/**
 * 推送任务常量定义
 *
 * @author luzhengshuai@ucfgroup.com
 */
class PushTaskModel extends BaseModel
{

    /**
     * 任务发送范围类型定义
     */
    const SCOPE_ALL = 1;  // 全量用户
    const SCOPE_USERIDS = 2; // 指定用户ID
    const SCOPE_USER_GROUP = 3; // 指定用户组ID
    const SCOPE_CSV = 4;  // 指定CSV文件

    /**
     * 任务类型定义
     */
    const TASK_MSG = 1; // 站内信
    const TASK_PUSH = 2; // 推送

    /**
     * 任务发送状态
     */
    const SEND_INIT = 1; // 初始状态
    const SEND_PROCESS = 2; // 发送中
    const SEND_COMPLETE = 3; // 发送完成

    /**
     * 任务删除状态
     */
    const IS_DELETE = 1; // 已删除
    const NO_DELETE = 0; // 未删除
}
