<?php
namespace NCFGroup\Ptp\Instrument\Listeners;

use libs\utils\Logger;

class DbListener
{
    protected $_logger;

    protected $_start;

    protected $_type;

    /**
     * Creates the profiler and starts the logging
     */
    public function __construct($type)
    {
        $this->_type = $type;
    }

    /**
     * This is executed if the event triggered is 'beforeQuery'
     */
    public function beforeQuery($event, $connection)
    {
        $this->_start = microtime(true);
    }

    /**
     * This is executed if the event triggered is 'afterQuery'
     */
    public function afterQuery($event, $connection)
    {
        $cost = round(microtime(true) - $this->_start, 4);
        $file = $line = '';
        $trace = debug_backtrace();
        foreach ($trace as $item) {
            if (isset($item['file'])) {
                $file = basename($item['file']);
                $line = $item['line'];
                break;
            }
        }
        $descriptor = $connection->getDescriptor();
        $dbname = $descriptor['dbname'];
        $msg = 'file:' .$file. ', line:'.$line.', cost:'. $cost . 's, sql:' . $connection->getSQLStatement()
            . ', variables:' . json_encode($connection->getSqlVariables()) . ', bindTypes:'. json_encode($connection->getSQLBindTypes());
        Logger::remote('BackendSqlLog. db:' . $dbname . ', type:' .$this->_type. ', ' .$msg);
        //慢SQL告警
        if ($cost >= 3) {
            $alarmTitle = 'BackendSqlLog. ' .$this->_type.'_'.(intval($cost / 3) * 3).'s';
            $alarmType = $dbname.'_'.$this->_type;
            \libs\utils\Alarm::push($alarmType, $alarmTitle, "BackendSqlLog. $msg");
        }
    }
}
