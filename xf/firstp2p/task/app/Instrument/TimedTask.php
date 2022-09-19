<?php
namespace NCFGroup\Task\Instrument;

use NCFGroup\Task\Models\Task;
use NCFGroup\Task\Gearman\WxGearManWorker;
use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Task\Gearman\GearMan;

class TimedTask
{
    private static $eventType_retryQueueName = array(
        'core\event\ContractSignEvent' => 'domq_cpu',
    );

    private static $timedTaskConfigArr = array(
        array(
            'eventTypeArr' => array(
                'core\\\\event\\\\ContractSignEvent',
            ),
            'type' => 'in',
        ),
        array(
            'eventTypeArr' => array(
                'core\\\\event\\\\ContractSignEvent',
            ),
            'type' => 'not in',
        ),
    );
    private static $missTaskConfigArr = array(/*{{{*/
        array(
            'missSec' => -300,
            'eventTypeArr' => array(
                'core\\\\event\\\\ContractSignEvent',
            ),
            'type' => 'not in',
            'limit' => 1000,
        ),
        array(
            'missSec' => -186400,
            'eventTypeArr' => array(
                'core\\\\event\\\\ContractSignEvent',
            ),
            'type' => 'in',
            'limit' => 1000,
        ),
    );/*}}}*/

    public static function execute($toGearMan)
    {/*{{{*/
        $taskArr = self::getTimedTaskList();

        foreach($taskArr as $taskInfo)
        {
            self::dispatch($taskInfo);
        }

        foreach(self::$missTaskConfigArr as $missTaskConfig) {
            $missTaskArr = self::getMissTaskArr($missTaskConfig);
            foreach($missTaskArr as $taskInfo)
            {
                self::dispatch($taskInfo);
            }
        }
    }/*}}}*/

    private static function dispatch($taskInfo)
    {/*{{{*/
        if (isset(self::$eventType_retryQueueName[$taskInfo['event_type']])) {
            self::toGearMan(self::$eventType_retryQueueName[$taskInfo['event_type']], $taskInfo['id'], $taskInfo['app_name']);
        } else {
            self::toGearMan(WxGearManWorker::DOTASK_RETRY, $taskInfo['id'], $taskInfo['app_name']);
        }
    }/*}}}*/

    private static function getMissTaskArr($missTaskConfig)
    {/*{{{*/
        $eventTypeStr = "'".implode("','", $missTaskConfig['eventTypeArr'])."'";

        $sql = "SELECT\n".
            "   t.app_name,\n".
            "   t.id,\n".
            "   t.event_type\n".
            "FROM\n".
            "   task t\n".
            "WHERE\n".
            "   t.`status` = :now\n".
            "AND t.event_type {$missTaskConfig['type']} ($eventTypeStr)\n".
            "AND t.execute_time <= :time\n".
            "LIMIT {$missTaskConfig['limit']};";
        $binds = array(
            'now' => Task::STATUS_RUN_NOW,
            'time' => XDateTime::now()->addSecond($missTaskConfig['missSec'])->toString(),
        );

        return getDI()->get('taskDb')->query($sql, $binds)->fetchAll();
    }/*}}}*/

    private static function toGearMan($worker, $taskId, $appName)
    {/*{{{*/
        GearMan::getInstance()->doBackground(self::getPrefixedWoker($worker, $appName), $taskId, $taskId);
    }/*}}}*/

    private static function getPrefixedWoker($worker, $appName)
    {/*{{{*/
        return $appName.'_'.$worker;
    }/*}}}*/

    private static function getTimedTaskList()
    {/*{{{*/
        $taskArr = array();
        foreach (self::$timedTaskConfigArr as $timedTaskConfig) {
           $taskArr = array_merge($taskArr, self::getTimedNeedRunTaskArr($timedTaskConfig));
        }

        foreach ($taskArr as $taskInfo) {
            self::setTaskNow($taskInfo['id']);
        }

        return $taskArr;
    }/*}}}*/

    private static function setTaskNow($taskId)
    {/*{{{*/
        $sql = "UPDATE task\n".
            "SET STATUS = :now\n".
            "WHERE\n".
            "   id = {$taskId};";
        $binds = array(
            'now' => Task::STATUS_RUN_NOW,
        );

        return getDI()->get('taskDb')->query($sql, $binds);
    }/*}}}*/

    private static function getTimedNeedRunTaskArr($timedTaskConfig)
    {/*{{{*/
        $eventTypeStr = "'".implode("','", $timedTaskConfig['eventTypeArr'])."'";

        $sql = "SELECT\n".
            "   app_name,\n".
            "   id,\n".
            "   event_type\n".
            "FROM\n".
            "   task t\n".
            "WHERE\n".
            "   t.`status` = :timed\n".
            "AND execute_time <= :now\n".
            "AND t.event_type {$timedTaskConfig['type']} ($eventTypeStr)\n".
            " LIMIT 25000;";

        $binds = array();
        $binds['timed'] = Task::STATUS_RUN_TIMED;
        $binds['now'] = XDateTime::now()->toString();

        return getDI()->get('taskDb')->query($sql, $binds)->fetchAll();
    }/*}}}*/
}
