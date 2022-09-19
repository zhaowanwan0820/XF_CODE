<?php
/**
 * 清洗资金记录
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */
ini_set('memory_limit', '1024M');
set_time_limit(0);
require_once dirname(__FILE__).'/../app/init.php';

use core\service\WashUserLogService;
use libs\db\MysqlDb;
use NCFGroup\Common\Library\Idworker;

error_reporting(E_ALL);
ini_set('display_errors', 1);

class WashUserLog
{
    public function __construct($partition, $db = 'backup', $start = 0)
    {
        $this->partition = $partition;
        $this->db = $db;
        $this->start = $start;
    }

    /**
     * 数据遍历逻辑
     */
    public function run()
    {
        $service = new WashUserLogService($this->partition, $this->db, $this->start, 'cli');
        //执行主进程
        $i = 0;
        $records = [];
        $logInfo = array_keys($service->allowUserLogInfo);
        $sqlTemplate = "SELECT * FROM firstp2p_user_log_%d WHERE log_info IN ('".implode("','", $logInfo)."') AND log_time BETWEEN %d AND %d AND deal_type  = 4";
        $startTime = strtotime('2019-04-13 00:00:00') - 28800;
        $endTime = strtotime('2019-04-17 11:00:00') - 28800;
        do {
            $sql = sprintf($sqlTemplate, ($i), $startTime, $endTime);
            $records = $service->getRecordsBySql('ncfwx', $sql);
            foreach ($records as $record)
            {
                $data = $service->parseUserLog($record);
                if ($data === true || $data === false)
                {
                    //echo '资金记录无效'.json_encode($record);
                    continue;
                }
                $service->addLog($data);
            }
            unset($records);
            $i ++;

        } while ($i < 64);
    }
}

$washer = new WashUserLog(0, 'backup', 0);
$washer->run();
