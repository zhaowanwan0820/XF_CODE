<?php
/**
 * Db class file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/
namespace libs\db;

use \SiteApp;
use \libs\utils\Logger;
use \libs\utils\FileCache;
use \libs\utils\DBDes;
use NCFGroup\Common\Library\TraceSdk;

/**
 * db access class
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/
class MysqlDb {
    var $link_id    = NULL;

    var $settings   = array();

    var $queryCount = 0;
    var $useMoved = false;
    var $useHistory = false;
    var $queryTime  = '';
    var $queryLog   = array();

    var $max_cache_time = 60; // 最大的缓存时间，以秒为单位

    var $desc_cache_time = 600; // 数据表的缓存时间为10分钟

    var $cache_data_dir = '';
    var $root_path      = '';

    var $error_message  = array();
    var $platform       = '';
    var $version        = '';
    var $dbhash         = '';
    var $starttime      = 0;

    var $transTimes;

    var $dbname = '';

    /**
     * master/slave等
     */
    private $type = '';

    private static $instance = array();
    // private $desFields = array();//加解密字段
    /**
     * 获取连接实例
     */
    public static function getInstance($configName, $type = 'master', $charset = 'utf8', $pConnect = 0, $quiet = 0)
    {
        if (empty(self::$instance[$configName][$type])) {
            $config = isset($GLOBALS['sys_config'][$configName.'_db'][$type]) ? $GLOBALS['sys_config'][$configName.'_db'][$type] : array();

            if (empty($config)) {
                throw new \Exception("MysqlDb config [{$configName}][{$type}] not found");
            }

            self::$instance[$configName][$type] = new MysqlDb($config['host'].':'.$config['port'], $config['user'], $config['password'], $config['name'], $charset, $pConnect, $quiet, $type);
        }

        return self::$instance[$configName][$type];
    }

    /**
     * 销毁连接实例
     */
    public static function destroyInstance($configName, $type = 'master')
    {
        if (isset(self::$instance[$configName][$type])) {
            self::$instance[$configName][$type]->close();
            unset(self::$instance[$configName][$type]);
        }
    }

    public function __construct($dbhost, $dbuser, $dbpw, $dbname = '', $charset = 'utf8', $pconnect = 0, $quiet = 0, $type = '')
    {
        $this->type = $type;

        $this->mysql_db($dbhost, $dbuser, $dbpw, $dbname, $charset, $pconnect, $quiet);
    }

    /**
     * 获取从库连接 (此处专指firstp2p从库)
     */
    public function get_slave($openConnection = 0)
    {
        if (defined('SPECIAL_USER_ACCESS')) {
            return MysqlDb::getInstance('firstp2p', 'vipslave');
        }

        if (defined('ADMIN_ROOT')) {
            return MysqlDb::getInstance('firstp2p', 'adminslave');
        }

        if ($this->useHistory) {
           return MysqlDb::getInstance('firstp2p_history', 'slave');
        }
        return MysqlDb::getInstance('firstp2p', 'slave');
    }

    function mysql_db($dbhost, $dbuser, $dbpw, $dbname = '', $charset = 'utf8', $pconnect = 0, $quiet = 0) {
        if (defined('APP_ROOT_PATH') && !$this->root_path) {
            $this->root_path = APP_ROOT_PATH;
            $this->cache_data_dir = APP_RUNTIME_PATH."app/db_caches/";
        }

        if ($quiet) {
            $this->connect($dbhost, $dbuser, $dbpw, $dbname, $charset, $pconnect, $quiet);
        }
        $this->settings = array(
            'dbhost'   => $dbhost,
            'dbuser'   => $dbuser,
            'dbpw'     => $dbpw,
            'dbname'   => $dbname,
            'charset'  => $charset,
            'pconnect' => $pconnect
        );
    }

    function setConfigName($configName){
        $this->settings['configName'] = $configName;
    }

    function connect($dbhost, $dbuser, $dbpw, $dbname = '', $charset = 'utf8', $pconnect = 0, $quiet = 0) {
        $this->dbname = $dbname;

        static $_connectTimes = 0;
        if ($pconnect) {
            if (!($this->link_id = @mysql_pconnect($dbhost, $dbuser, $dbpw))) {
                if (!$quiet) {
                    $this->ErrorMsg("Can't pConnect MySQL Server($dbhost)!");
                }
                return false;
            }
        } else {
            $this->link_id = mysql_connect($dbhost, $dbuser, $dbpw, true);

            if (!$this->link_id) {
                $_connectTimes ++;
                if ($_connectTimes < 3) {
                    usleep(500000);
                    $this->connect($dbhost, $dbuser, $dbpw, $dbname, $charset, $pconnect, $quiet);
                }
                else {
                    $this->ErrorMsg("Can't Connect MySQL Server($dbhost)!");
                }
                return false;
            }
        }

        $this->dbhash  = md5($this->root_path . $dbhost . $dbuser . $dbpw . $dbname);
        $this->version = '5.5.0';

        /* 如果mysql 版本是 4.1+ 以上，需要对字符集进行初始化 */
        mysql_query("SET character_set_connection=$charset, character_set_results=$charset, character_set_client=binary", $this->link_id);
        mysql_query("SET sql_mode=''", $this->link_id);

        $this->starttime = time();

        /* 选择数据库 */
        if ($dbname) {
            if (mysql_select_db($dbname, $this->link_id) === false ) {
                if (!$quiet) {
                    $this->ErrorMsg("Can't select MySQL database($dbname)!");
                }

                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    function select_database($dbname) {
        return mysql_select_db($dbname, $this->link_id);
    }

    function fetch_array($query, $result_type = MYSQL_ASSOC) {
        return mysql_fetch_array($query, $result_type);
        // if(!$res){
        //     return $res;
        // }
        // return DBDes::decryptFilter($res,$this->desFields);//MYSQL_NUM情况无法处理解密
    }

    function connectDb() {
        if ($this->link_id === NULL) {
            $this->connect(
                $this->settings['dbhost'],
                $this->settings['dbuser'],
                $this->settings['dbpw'],
                $this->settings['dbname'],
                $this->settings['charset'],
                $this->settings['pconnect']
            );

            $this->settings = array();
        }
    }

    function query($sql, $op = '') {
        $digPoint = TraceSdk::digLogStart(__FILE__, __LINE__, 'mysql');
        $this->connectDb();

        /* 当当前的时间大于类初始化时间的时候，自动执行 ping 这个自动重新连接操作 */
        if (time() > $this->starttime + 1) {
            mysql_ping($this->link_id);
        }

        // $sqlAndDesFields  = DBDes::encryptFilter($sql);
        // $sql = $sqlAndDesFields[0];
        // $this->desFields = $sqlAndDesFields[1];//查询结果解密使用
        $start = microtime(true);
        if (!($query = mysql_query($sql, $this->link_id)) && $op != 'SILENT') {
            $this->error_message[]['message'] = 'MySQL Query Error';
            $this->error_message[]['sql'] = $sql;

            $error = mysql_error($this->link_id);
            $errno = mysql_errno($this->link_id);
            $this->error_message[]['error'] = $error;
            $this->error_message[]['errno'] = $errno;
            $this->ErrorMsg($error, $sql);

            TraceSdk::record(TraceSdk::LOG_TYPE_EXCEPTION, __FILE__, __LINE__, 'mysql', array(
                'fun' => 'execute',
                'sql' => $sql,
                'error' => $error,
                'errno' => $errno,
            ));

            return false;
        }

        TraceSdk::digMysqlEnd($digPoint, $sql, '', $op, 'execute_sql');

        $trace = $this->getTrace();
        $cost = round(microtime(true) - $start, 4);
        Logger::remote("SqlLog. db:{$this->dbname}, type:{$this->type}, file:{$trace['file']}, line:{$trace['line']}, cost:{$cost}s, sql:{$sql}");

        //慢SQL告警
        if ($cost >= 3) {
            $alarmTitle = $this->type.'_'.(intval($cost / 3) * 3).'s';
            $alarmType = $this->dbname.'_'.$this->type;
            \libs\utils\Alarm::push($alarmType, $alarmTitle, "file:{$trace['file']}, line:{$trace['line']}, cost:{$cost}s, sql:{$sql}");
        }

        return $query;
    }

    private function getTrace() {
        $file = '';
        $line = 0;

        $trace = debug_backtrace();
        foreach ($trace as $item) {
            if (isset($item['file']) && strpos($item['file'], '/core/service')) {
                $file = basename($item['file']);
                $line = $item['line'];
                break;
            }
        }

        if ($file === '') {
            $file = isset($trace[2]['file']) ? basename($trace[2]['file']) : '';
            $line = isset($trace[2]['line']) ? $trace[2]['line'] : 0;
        }

        return array(
            'file' => $file,
            'line' => $line,
        );
    }

    function affected_rows() {
        return mysql_affected_rows($this->link_id);
    }

    function error() {
        return mysql_error($this->link_id);
    }

    function errno() {
        return mysql_errno($this->link_id);
    }

    function result($query, $row) {
        return @mysql_result($query, $row);
    }

    function num_rows($query) {
        return mysql_num_rows($query);
    }

    function num_fields($query) {
        return mysql_num_fields($query);
    }

    function free_result($query) {
        return mysql_free_result($query);
    }

    function insert_id() {
        return mysql_insert_id($this->link_id);
    }

    function fetchRow($query) {
        return mysql_fetch_assoc($query);
        // if(!$res){
        //     return $res;
        // }
        // return DBDes::decryptFilter($res,$this->desFields);
    }

    function fetch_fields($query) {
        return mysql_fetch_field($query);
    }

    function version() {
        return $this->version;
    }

    function ping() {
        return mysql_ping($this->link_id);
    }

    public static function escape_string($unescaped_string) {
        return addslashes($unescaped_string);
    }

    public function close()
    {
        if ($this->link_id !== null) {
            return mysql_close($this->link_id);
        }

        return true;
    }

    /**
     * 错误处理
     */
    public function ErrorMsg($message = '', $sql = '') {
        $logfilename = ROOT_PATH.'storage/log/mysql_error_'.date('Y_m_d').'.log';
        $_error_code = $this->link_id ? mysql_errno($this->link_id) : -1;
        $_error_msg = $this->link_id ? mysql_error($this->link_id) : $message;

        Logger::wLog("ErrorMsg. db:{$this->dbname}, type:{$this->type}, error:{$_error_msg}, errno:{$_error_code}, sql:{$sql}\n", Logger::ERR, Logger::FILE, $logfilename);
        Logger::remote("SqlLogError. db:{$this->dbname}, type:{$this->type}, error:{$_error_msg}, sql:{$sql}");

        $backtrace = debug_backtrace();
        foreach ($backtrace as $trace) {
            if (!isset($trace['file']))
            {
                $content = "Trace. function:{$trace['function']}\n";
                Logger::wLog($content, Logger::DEBUG, Logger::FILE, $logfilename);
                continue;
            }
            $content = "Trace. file:{$trace['file']}, line:{$trace['line']}, function:{$trace['function']}\n";
            Logger::wLog($content, Logger::DEBUG, Logger::FILE, $logfilename);
        }

        \libs\utils\Alarm::push('mysqlerror', 'ErrorMsg', "ErrorMsg. db:{$this->dbname}, type:{$this->type}, error:{$_error_msg}, errno:{$_error_code}, sql:{$sql}");

        throw new \Exception($_error_msg, $_error_code);
    }

    function getOne($sql, $limited = false) {
        if ($limited == true) {
            $sql = trim($sql . ' LIMIT 1');
        }

        $res = $this->query($sql, 'select');
        if ($res !== false) {
            //$row = mysql_fetch_row($res);
            $row = mysql_fetch_assoc($res);
            if ($row !== false) {
                return (array_values($row)[0]);
                // $row = DBDes::decryptFilter($row,$this->desFields);
                // return (array_values($row)[0]);
            } else {
                return '';
            }
        } else {
            return false;
        }
    }

    function getOneCached($sql, $cached = 'FILEFIRST') {
        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;

        if (!$cachefirst) {
            return $this->getOne($sql, true);
        } else {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true) {
                return $result['data'];
            }
        }

        $arr = $this->getOne($sql, true);

        if ($arr !== false && $cachefirst) {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    function getAll($sql) {
        $res = $this->query($sql, 'select');
        if ($res !== false) {
            $arr = array();
            while ($row = mysql_fetch_assoc($res)) {
               $arr[] = $row;
                // $arr[] = DBDes::decryptFilter($row,$this->desFields);
            }

            return $arr;
        } else {
            return false;
        }
    }

    function getAllCached($sql, $cached = 'FILEFIRST') {
        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst) {
            return $this->getAll($sql);
        } else {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true) {
                return $result['data'];
            }
        }

        $arr = $this->getAll($sql);

        if ($arr !== false && $cachefirst) {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    function getRow($sql, $limited = false) {
        if ($limited == true) {
            $sql = trim($sql . ' LIMIT 1');
        }

        $res = $this->query($sql, 'select');
        if ($res !== false) {
            return mysql_fetch_assoc($res);
            // return  DBDes::decryptFilter( mysql_fetch_assoc($res),$this->desFields);
        } else {
            return false;
        }
    }

    function getRowCached($sql, $cached = 'FILEFIRST') {


        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst) {
            return $this->getRow($sql, true);
        } else {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true) {
                return $result['data'];
            }
        }

        $arr = $this->getRow($sql, true);

        if ($arr !== false && $cachefirst) {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    function getCol($sql) {
        $res = $this->query($sql, 'select');
        if ($res !== false) {
            $arr = array();
            while ($row = mysql_fetch_row($res)) {
                $arr[] = $row[0];
            }

            return $arr;
        } else {
            return false;
        }
    }

    function getColCached($sql, $cached = 'FILEFIRST') {
        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst) {
            return $this->getCol($sql);
        } else {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true) {
                return $result['data'];
            }
        }

        $arr = $this->getCol($sql);

        if ($arr !== false && $cachefirst) {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    function update($table, $data, $where)
    {
        $sql = "UPDATE `{$table}` SET ";

        foreach ($data as $key => $value) {
            $value = is_null($value) ? 'null' : addslashes($value);
            $sql .= " `$key`='$value',";
        }

        $sql = substr($sql, 0, -1);

        if ($where !== '') {
            $sql .= " WHERE {$where}";
        }

        return $this->query($sql, 'update');
    }

    public function insert($table, $data)
    {
        // $data =  DBDes::encryptOneRow($table,$data);
        $fields = array_keys($data);
        foreach ($fields as $key => $value) {
            $data[$value] = is_null($data[$value]) ? 'null' : "'".addslashes($data[$value])."'";
            $fields[$key] = "`$value`";
        }

        $sql = "INSERT INTO `{$table}` (".implode(',', $fields).") VALUES (".implode(',', $data).")";
        if ($this->query($sql, 'insert')) {
            return $this->insert_id();
        }

        return 0;
    }

    /**
     * 批量插入
     */
    public function insertBatch($table, $data)
    {
        // $data =  DBDes::encryptMultiRow($table,$data);
        $fields = array_keys(current($data));

        $valueArray = array();
        foreach ($data as $item) {
            foreach ($fields as $value) {
                $item[$value] = is_null($item[$value]) ? 'null' : "'".addslashes($item[$value])."'";
            }
            $valueArray[] = '('.implode(',', $item).')';
        }

        foreach ($fields as $key => $value) {
            $fields[$key] = "`$value`";
        }

        $sql = "INSERT INTO `{$table}` (".implode(',', $fields).") VALUES ".implode(', ', $valueArray);

        return $this->query($sql, 'insert');
    }

    /**
     * DESC缓存
     */
    private function getDescCacheData($table_name)
    {
        $key = "desc_{$this->dbhash}_{$table_name}";

        $result = FileCache::getInstance()->get($key);
        if (empty($result)) {
            $result = $this->getCol("DESC {$table_name}");
            FileCache::getInstance()->set($key, $result, $this->desc_cache_time);
        }

        return $result;
    }

    function autoExecute($table, $field_values, $mode = 'INSERT', $where = '', $querymode = '') {
        $field_names = $this->getDescCacheData($table);

        $sql = '';
        if ($mode == 'INSERT') {
            // $field_values =  DBDes::encryptOneRow($table,$field_values);
            $fields = $values = array();
            foreach ($field_names AS $value) {
                if (@array_key_exists($value, $field_values) == true) {
                    $fields[] = $value;
                    $field_values[$value] = stripslashes($field_values[$value]);
                    $values[] = "'" . addslashes($field_values[$value]) . "'";
                }
            }

            if (!empty($fields)) {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
            }
        } else {
            $sets = array();
            foreach ($field_names AS $value) {
                if (array_key_exists($value, $field_values) == true) {
                    $field_values[$value] = stripslashes($field_values[$value]);
                    $sets[] = '`'.$value .'`' . " = '" . addslashes($field_values[$value]) . "'";
                }
            }

            if (!empty($sets)) {
                $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $where;
            }
        }

        if ($sql) {
            return $this->query($sql, $querymode);
        } else {
            return false;
        }
    }

    function autoReplace($table, $field_values, $update_values, $where = '', $querymode = '') {
        $field_descs = $this->getAll('DESC ' . $table);
        $primary_keys = array();
        foreach ($field_descs AS $value) {
            $field_names[] = $value['Field'];
            if ($value['Key'] == 'PRI') {
                $primary_keys[] = $value['Field'];
            }
        }

        $fields = $values = array();
        foreach ($field_names AS $value) {
            if (array_key_exists($value, $field_values) == true) {
                $fields[] = $value;
                $values[] = "'" . $field_values[$value] . "'";
            }
        }

        $sets = array();
        foreach ($update_values AS $key => $value) {
            if (array_key_exists($key, $field_values) == true) {
                if (is_int($value) || is_float($value)) {
                    $sets[] = $key . ' = ' . $key . ' + ' . $value;
                } else {
                    $sets[] = $key . " = '" . $value . "'";
                }
            }
        }

        $sql = '';
        if (empty($primary_keys)) {
            if (!empty($fields)) {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
            }
        } else {
            if ($this->version() >= '4.1') {
                if (!empty($fields)) {
                    $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
                    if (!empty($sets)) {
                        $sql .=  'ON DUPLICATE KEY UPDATE ' . implode(', ', $sets);
                    }
                }
            } else {
                if (empty($where)) {
                    $where = array();
                    foreach ($primary_keys AS $value) {
                        if (is_numeric($value)) {
                            $where[] = $value . ' = ' . $field_values[$value];
                        } else {
                            $where[] = $value . " = '" . $field_values[$value] . "'";
                        }
                    }
                    $where = implode(' AND ', $where);
                }

                if ($where && (!empty($sets) || !empty($fields))) {
                    if (intval($this->getOne("SELECT COUNT(*) FROM $table WHERE $where")) > 0) {
                        if (!empty($sets)) {
                            $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $where;
                        }
                    } else {
                        if (!empty($fields)) {
                            $sql = 'REPLACE INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
                        }
                    }
                }
            }
        }

        if ($sql) {
            return $this->query($sql, $querymode);
        } else {
            return false;
        }
    }

    function getSqlCacheData($sql, $cached = '') {
        $sql = trim($sql);

        $result = array();
        $result['filename'] = $this->cache_data_dir . 'sqlcache_' . abs(crc32($this->dbhash . $sql)) . '_' . md5($this->dbhash . $sql) . '.php';

        $result['data'] = $GLOBALS['cache']->get($result['filename']);
        if($result['data']===false) {
            $result['storecache'] = true;
        } else {
            $result['storecache'] = false;
        }
        return $result;
    }

    function setSqlCacheData($result, $data) {
        if ($result['storecache'] === true && $result['filename']) {
            $GLOBALS['cache']->set($result['filename'],$data,$this->max_cache_time);
        }
    }

    private $_transactionStartTime = 0;

    private $_rollbackFlag = false;

    /**
     * 启动事务
     */
    public function startTrans()
    {
        if ($this->transTimes == 0) {
            $this->_transactionStartTime = microtime(true);
            $this->query('START TRANSACTION', 'transaction');
        } else {
            Logger::remote('StartTrans called. transTimes:'.$this->transTimes);
        }

        $this->transTimes++;
        return ;
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        if ($this->transTimes == 1) {
            //如果中间有rollback调用
            if ($this->_rollbackFlag === true) {
                throw new \Exception('Rollback halfway');
            }

            $result = $this->query('COMMIT', 'transaction');

            Logger::remote("TransactionCommit. db:{$this->dbname}, cost:".round(microtime(true) - $this->_transactionStartTime, 4).'s');

            if ($result) {
                $this->transTimes = 0;
            } else {
                throw new \Exception($this->error());
            }
        } elseif ($this->transTimes > 1) {
            Logger::remote('Commit called. transTimes:'.$this->transTimes);
            $this->transTimes--;
        } else {
            throw new \Exception('Transaction not match');
        }
        return true;
    }

    /**
     * 事务回滚
     */
    public function rollback()
    {
        if ($this->transTimes == 1) {
            $result = $this->query('ROLLBACK', 'transaction');

            Logger::remote("TransactionRollback. db:{$this->dbname}, cost:".round(microtime(true) - $this->_transactionStartTime, 4).'s');

            //$this->transTimes = 0; // 多层事务嵌套，里层回滚，外层提交会有问题
            $this->transTimes--;
            $this->_rollbackFlag = false;

            if(!$result){
                throw new \Exception($this->error());
            }
        } elseif ($this->transTimes > 1) {
            Logger::remote('Rollback called. transTimes:'.$this->transTimes);
            $this->_rollbackFlag = true;
            $this->transTimes--;
        } else {
            throw new \Exception('Transaction not match');
        }
        return true;
    }

}
