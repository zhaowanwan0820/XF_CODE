<?php
require_once dirname(__FILE__).'/init.php';
require_once(APP_ROOT_PATH.'system/libs/msgcenter.php');

use core\dao\TaskModel;
use libs\utils\XDateTime;

/**
 * TaskMonitor 任务监控
 */
class TaskMonitor 
{
    const MAX_TASK_CNT = 100;


    public static function launch()
    {
        $nowCnt = TaskModel::instance()->count('1=1');
        if ($nowCnt > self::MAX_TASK_CNT) {
            $msgcenter = new \Msgcenter();
            $msgcenter->setMsg('jingxu@ucfgroup.com', 0, "任务积压 {$nowCnt}", false, '任务积压');
            $msgcenter->save();
        }

        $delayCnt = TaskModel::instance()->getExecutingTaskCntBeforeUpdateTime(XDateTime::now()->addMinute(-10));
        if ($delayCnt) {
            $msgcenter = new \Msgcenter();
            $msgcenter->setMsg('jingxu@ucfgroup.com', 0, "延迟任务 {$delayCnt}", false, '延迟任务');
            $msgcenter->save();
        }
    }
}

TaskMonitor::launch();
