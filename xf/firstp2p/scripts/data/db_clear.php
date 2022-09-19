<?php
/**
 * Created by PhpStorm.
 * User: liaoyebin@ucfgroup.com
 * Date: 2016/1/14
 * Time: 13:55
 */

require_once(dirname(__FILE__) . '/../../app/init.php');

use libs\db\Db;

// 先检查是否已经有处理进程存在，存在则本进程退出
$pid = posix_getpid();
$cmd = "ps aux | grep db_clear.php | grep -v grep | grep -v sudo | grep -v {$pid} | grep -v vi | grep -v /bin/sh";
$handle = popen($cmd, "r");
$str = fread($handle, 1024);
if ($str) {
    echo "进程已经启动\n";
    exit;
}
set_time_limit(0);
ini_set('memory_limit', '1024M');
\libs\utils\Script::start();

class DbClear
{
    private $_config = [];
    private $_db_master;
    private $_db_slave;

    const DEFAULT_LIMIT = 5000; //每次delete操作条数
    const SLEEPING_TIME = 10;   //一次sql后sleep秒数
    const DAYS_AGO = 7;    //删除n天前数据
    const DEFAULT_TIME_FIELD = 'create_time';

    public function __construct()
    {
        $this->_config = Db::getInstance('itil')->getAll('SELECT `table_name`,`where`,`remain_days`,`limit`,`backup_type`, `create_time_field`, `split_count` FROM db_clear_config WHERE status=1');
        $this->_db_master = Db::getInstance('firstp2p');
        $this->_db_slave = Db::getInstance('firstp2p', 'slave');
        if(empty($this->_db_slave)) {
            $this->_db_slave = $this->_db_master;
        }
    }

    public function run()
    {
        foreach($this->_config as $table){
            if(empty($table['table_name'])){
                continue;
            }

            //以字符'.'支持多库
            if(false !== $pos = strpos($table['table_name'], '.')) {
                $dbName = substr($table['table_name'], 0, $pos);
                $table['table_name'] = substr($table['table_name'], $pos+1);
                try {
                    $this->_db_master = Db::getInstance($dbName);
                } catch(Exception $e) {
                    //主库不存在
                    \libs\utils\Script::log($e->getMessage());
                    continue;
                }

                try {
                    $this->_db_slave = Db::getInstance($dbName, 'slave');
                } catch(Exception $e) {
                    \libs\utils\Script::log($e->getMessage());
                    //从库不存在
                    $this->_db_slave = $this->_db_master;
                }
            }

            \libs\utils\Script::log("----- clear table {$table['table_name']} begin ----");
            if(empty($this->_db_master)) {
                \libs\utils\Script::log("db init failed");
                continue;
            }

            //分表
            if(isset($table['split_count']) && (int)$table['split_count'] > 1){
                $cnt = (int)$table['split_count'];
                $name = $table['table_name'];
                for($i=0; $i<$cnt; $i++){
                    $table['table_name'] = $name . '_' . $i;
                    $this->clearByDaysAgo($table);
                }
            }else{
                $this->clearByDaysAgo($table);
            }
        }
    }

    /**
     * 删除｛$days｝天前的数据
     * 适合含有create_time字段，且于生成以后不会被更新，含有自增id的数据表
     * @param $table
     * @return bool
     */
    private function clearByDaysAgo($table){
        if(empty($table['table_name'])){
            \libs\utils\Script::log('##### table name is empty!');
            return false;
        }

        if($table['backup_type'] == 1){
            $backupDB = 'firstp2p_moved';
        }else{
            $backupDB = 'firstp2p_deleted';
        }

        //检测备份库是否有该表
        $dbName = $GLOBALS['sys_config'][$backupDB.'_db']['master']['name'];
        $ret = Db::getInstance($backupDB)->getOne("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='{$dbName}' AND TABLE_NAME='{$table['table_name']}'");
        if(empty($ret)){
            \libs\utils\Script::log('##### cannot find the backup table! ' . $table['table_name']);
            return false;
        }

        $limit = self::DEFAULT_LIMIT;
        if(!empty($table['limit'])){
            $limit = (int)$table['limit'];
        }

        $days = self::DAYS_AGO;
        if(!empty($table['remain_days'])){
            $days = max((int)$table['remain_days'], $days);
        }

        $createTime = self::DEFAULT_TIME_FIELD;
        if(!empty($table['create_time_field'])){
            $createTime = $table['create_time_field'];
        }

        $time = strtotime("{$days} days ago 00:00");

        $where = empty($table['where']) ? '' : ' WHERE '.$table['where'];
        $minId = $this->_db_slave->getOne('select min(id) FROM ' . $table['table_name'] . $where);
        $maxId = $this->_db_slave->getOne('select max(id) FROM ' . $table['table_name'] . $where);
        \libs\utils\Script::log("##### {$table['table_name']} minId is {$minId}, maxId is {$maxId} {$where}");

        $minRow = $this->_db_slave->getRow('SELECT id, ' . $createTime . ' FROM ' . $table['table_name'] . ' WHERE id = ' . $minId, true);
        $diff = $time - $minRow[$createTime];
        //最小id都在{$days}天内直接返回
        if($diff <= 0){
            \libs\utils\Script::log("##### The records of $days ago in table {$table['table_name']} has already been deleted");
            return true;
        }

        for ($i = $minId; $i < $maxId - 10 * $limit; $i += $limit) {
            if (!empty($table['where'])) {
                $condition = " WHERE id BETWEEN {$i} AND " . ($i + $limit - 1) . " AND ({$table['where']}) LIMIT {$limit}";
            } else {
                $condition = " WHERE id BETWEEN {$i} AND " . ($i + $limit - 1) . " LIMIT {$limit}";
            }
            $rows = $this->_db_slave->getAll('SELECT * FROM ' . $table['table_name'] . $condition);

            //无记录退出循环
            if (empty($rows)) {
                \libs\utils\Script::log("##### {$table['table_name']} records empty {$condition}");
                continue;
            }

            //时间是否符合要求
            foreach ($rows as $row) {
                if ($row[$createTime] >= $time) {
                    \libs\utils\Script::log("##### {$table['table_name']} a record's create_time is after {$time} id is {$row['id']}");
                    return false;
                }
            }

            //批量备份数据
            $insertOneByOne = false;
            try {
                \libs\utils\Script::log("##### batch backup {$table['table_name']} records {$condition}");
                Db::getInstance($backupDB)->insertBatch($table['table_name'], $rows);
            } catch (Exception $e) {
                \libs\utils\Monitor::add('DB_CLEAR_BATCH_INSERT_FAILED');
                \libs\utils\Script::log("##### batch insert {$table['table_name']} failed. msg:".$e->getMessage());
                $insertOneByOne =true;
            }

            //一条条备份
            if ($insertOneByOne) {
                foreach ($rows as $row) {
                    try {
                        Db::getInstance($backupDB)->insert($table['table_name'], $row);
                    } catch (Exception $e) {
                        \libs\utils\Script::log("##### insert {$table['table_name']} failed. msg:" . $e->getMessage());
                        if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                            \libs\utils\Monitor::add('DB_CLEAR_INSERT_FAILED');
                            return false;
                        }
                        \libs\utils\Monitor::add('DB_CLEAR_INSERT_DUPLICATE');
                    }
                }
            }

            //删除操作
            \libs\utils\Script::log("##### delete {$table['table_name']} records {$condition}, rows=" . count($rows) . " #####");
            $this->_db_master->query('DELETE FROM ' . $table['table_name'] . $condition);
            \libs\utils\Monitor::add('DB_CLEAR_DELETED_COUNT', count($rows));
        }
    }

    private function getRecordIdByDays($tableName, $minId, $maxId, $days, $first=true, $preMin=0)
    {
        $time = strtotime("{$days} days ago 00:00");
        $row = $this->_db_master->getRow('select id, create_time from ' . $tableName . ' where id >= ' . $minId, true);
        $minId = intval($row['id']);
        $date = date('Y-m-d H:s:i', $row['create_time']);
        \libs\utils\Script::log("##### record! id={$row['id']} date=$date min=$minId max=$maxId #####");

        $diff = $time - $row['create_time'];
        if($first && $diff <= 0){
            return intval($row['id']);
        }
        if(($diff == 0) || $maxId-$minId <=1 ){
            $date = date('Y-m-d H:s:i', $row['create_time']);
            \libs\utils\Script::log("##### Find the record! id={$row['id']} date=$date #####");
            return intval($row['id']);
        }elseif($diff > 0 && $maxId-$minId >1){
            $mid = ceil(($maxId + $minId) / 2);
            return $this->getRecordIdByDays($tableName, $mid, $maxId, $days, false, $minId);
        }elseif($diff < 0 && $maxId-$minId >1){
            return $this->getRecordIdByDays($tableName, $preMin, $minId, $days, false, $minId);
        }
    }

}

$dbClear = new DbClear();
$dbClear->run();

\libs\utils\Script::end();
