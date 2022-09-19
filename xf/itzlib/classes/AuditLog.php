<?php

/**
 * 审计日志基础类
 * @author Thomas Chan
 * @class params [ collection , db , server , replication ](ps: all may elect)
 * @method       [add, update, get, count, delete]
 * @since        Version 1.0.0
 **/
class AuditLog
{
    /**
     * @var MongoClient $connection
     */
    protected $connection;
    /**
     * @var object One collection of mongo db
     */
    protected $collection;

    /**
     * @var array Array of instances
     */
    protected static $instance = null;

    protected $config = [];

    public function __construct($_collection = '', $_db = '', $_server = '', $_replication = '')
    {
        if ($_collection) {
            $this->initConnect($_collection, $_db, $_server, $_replication);
        }
        return true;
    }

    public function loadConfig()
    {
        $configFile = dirname(dirname(__FILE__)) . '/config/auditLog.php';
        if (file_exists($configFile)) {
            return require($configFile);
        } else {
            Yii::log(' Error with load AuditLog config.php file', CLogger::LEVEL_ERROR, __CLASS__);
            return false;
        }
    }

    /**
     * 初始化连接
     * @param string $_collection
     * @param string $_db
     * @param string $_server
     * @param string $_replication
     * @return bool
     */
    public function initConnect($_collection = '', $_db = '', $_server = '', $_replication = '')
    {
        try {
            $config = $this->loadConfig();
            if (!$config) {
                return false;
            }
            if (empty($_server) || !isset($_server)) {
                $_server = $config['server'];
            }
            if ($_server['AuditLogOn'] === false) {
                return false;
            }
            if (empty($_collection) || !isset($_collection)) {
                $_collection = $_server['collection'];
            }
            if (empty($_db) || !isset($_db)) {
                $_db = $_server['db'];
            }
            if (empty($_replication) || !isset($_replication)) {
                $_replication = $_server['replication'];
            }

            $_user = !isset($_server['user']) ?: $_server['user'] . ':';
            $_pass = !isset($_server['pass']) ?: $_server['pass'];
            if (!isset($_server['servers'])) {
                return false;
            }

            $_servers = '';
            foreach ($_server['servers'] as $key => $value) {
                $_separator = $key === 0 ? '@' : ',';
                $_servers .= $_separator . $value;
            }

            $uri = "mongodb://" . $_user . $_pass . $_servers . '/' . $_db;

            $options = [
                'replicaSet' => $_replication,
                'connectTimeoutMS' => 4000,
                'socketTimeoutMS' => 4000,
            ];
            if (class_exists("MongoClient")) {
                $this->connection = new MongoClient($uri, $options);
            } elseif (class_exists("Mongo")) {
                $this->connection = new Mongo($uri, $options);
            } else {
                Yii::log('Both of Mongo or MongoClient are missing', CLogger::LEVEL_ERROR, __CLASS__);

                return false;
            }
            $this->collection = $this->connection->selectCollection($_db, $_collection);
        } catch (Exception $e) {
            Yii::log(print_r($e->getMessage(), true), CLogger::LEVEL_ERROR, __CLASS__);

            return false;
        }

        return true;
    }

    /**
     * Get different instance of class with different params
     *
     * @param  string $_collection The collection name
     * @param  string $_db The db name
     * @param  array|string $_server The servers config
     * @param  string $_replication
     *
     * @return object
     */
    public static function getInstance($_collection = '', $_db = '', $_server = '', $_replication = '')
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($_collection, $_db, $_server, $_replication);
        }

        return self::$instance;
    }

    protected function closeConnection()
    {
        if ($this->connection) {
            $this->connection->close(true);
        }
    }

    /**
     * Magic function to add try catch to all called method
     *
     * @param  string $name called method
     * @param  array $data params value
     * @return bool
     */
    public function __call($name, $data = [])
    {
        if ($name == 'add') {
            return $this->method($name, $data);
        } elseif (method_exists($this, $name)) {
            $this->initConnect();
            return $this->$name($data);
        } else {
            return false;
        }
    }

    /**
     * 添加审计日志， 历史原因 $name 只接收 add 参数
     * @param string $name 历史遗留，无用参数
     * @param array $data 审计日志信息
     * @return bool
     */
    public function method($name, $data = [])
    {
        if (!isset($data['event_id'])) {
            $data['event_id'] = $this->getGUID();
        }
        if (!isset($data['client_ip'])) {
            $data['client_ip'] = FunctionUtil::ip_address();
        }
        if (!isset($data['timestamp'])) {
            $data['timestamp'] = $this->getMillisecond();
        }
        if (!isset($data['timestamp_format'])) {
            $data['timestamp_format'] = date('Y-m-d H:i:s', time());
        }
        if (!isset($data['user_agent'])) {
            $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }

        $config = $this->loadConfig();
        /* 优先使用手动配置,判断是否直接写本地文件 */
        if ($config['server']['logToLocal']) {
            return $this->addToLocalFile($data);
        } else {
            return Yii::app()->async->dispatch(__CLASS__, 'addLogAsync', [$data]);
        }
    }

    public function __destruct()
    {
        $this->closeConnection();
    }

    public function addLogAsync($params)
    {
        $config = $this->loadConfig();
        if ($config['server']['logToLocal']) {
            /* 优先使用手动配置,判断是否直接写本地文件 */
            $this->addToLocalFile($params);
        } else {
            if ($this->initConnect()) {
                /* 连接成功写 mongo */
                try {
                    $res = $this->add($params);
                    if ($res['ok'] == 0 || !is_null($res['err'])) {
                        throw new InvalidArgumentException("AuditLog add fail: {$res['errmsg']}");
                    }
                } catch (Exception $e) {
                    Yii::log($e->getMessage(), CLogger::LEVEL_ERROR, __CLASS__);
                    $this->addToLocalFile($params);
                }
                $this->closeConnection();
            } else {
                /* 连接失败写本地文件 */
                $this->addToLocalFile($params);
            }
        }
    }

    /**
     * Add one log to collection
     *
     * @param $params
     * @return bool
     */
    protected function add($params)
    {
        if (empty($params)) {
            return false;
        } else {
            return $this->collection->insert($params);
        }
    }

    /**
     * 写本地
     * @param array $params
     * @return boolean
     */
    protected function addToLocalFile($params)
    {
        if (empty($params) || !isset($params)) {
            return false;
        }

        $basePath = Yii::app()->runtimePath . "/";
        $logPath = $basePath . __CLASS__;
        if (!file_exists($logPath)) {
            mkdir($logPath);
        }
        $filename = $logPath . "/" . date("Y-m-d") . ".log";
        $str = json_encode($params);
        $logInfo = date("Y-m-d H:i:s") . "\t{$str}\n";
        return file_put_contents($filename, $logInfo, FILE_APPEND);
    }

    /**
     * Wheres array.
     *
     * @var array
     * @access public
     */
    protected $_wheres = [];

    /**
     * Sorts array.
     *
     * @var array
     * @access protected
     */
    protected $_sorts = [];

    /**
     * Orders array.
     *
     * @var array
     * @access protected
     */
    protected $_orders = [];

    /**
     * Results limit.
     *
     * @var integer
     * @access protected
     */
    protected $_limit = 10;

    /**
     * Result skip(offset).
     *
     * @var integer
     * @access protected
     */
    protected $_skip = 0;

    protected function find($params)
    {
        $params = $params[0];
        $field = isset($params['field']) ? $params['field'] : [];

        if (!isset($params['format'])) $params['format'] = 'none';

        // die(var_dump($params));

        $r = $this->where([$params['where']])
            ->order([$params['order']])
            ->limit([$params['limit']])
            ->skip([$params['skip']])
            ->get($params['format'], $field);

        return $r;
    }

    private function _whereInit($field)
    {
        if (!isset($this->_wheres[$field])) {
            $this->_wheres[$field] = [];
        }
    }

    protected function where($params)
    {
        $wheres = isset($params[0]) ? $params[0] : [];
        $value = isset($params[1]) ? $params[1] : null;
        if (is_array($wheres)) {
            foreach ($wheres as $where => $value) {
                $this->_wheres[$where] = $value;
            }
        } else {
            $this->_wheres[$wheres] = $value;
        }

        return $this;
    }

    protected function whereIn($params)
    {
        $field = isset($params[0]) ? $params[0] : '';
        $inValues = isset($params[1]) ? $params[1] : null;
        $this->_whereInit($field);
        $this->_wheres[$field]['$in'] = $inValues;

        return $this;
    }

    protected function whereNotIn($params)
    {
        $field = isset($params[0]) ? $params[0] : '';
        $inValues = isset($params[1]) ? $params[1] : null;
        $this->_whereInit($field);
        $this->_wheres[$field]['$nin'] = $inValues;

        return $this;
    }

    protected function whereAnd($params)
    {
        $wheres = isset($params[0]) ? $params[0] : [];
        if (count($wheres) > 0) {
            if (!isset($this->_wheres['$and']) OR !is_array($this->_wheres['$and'])) {
                $this->_wheres['$and'] = [];
            }

            foreach ($wheres as $where => $value) {
                $this->_wheres['$and'][] = [$where => $value];
            }
        }

        return $this;
    }

    protected function whereOr($params)
    {
        $wheres = isset($params[0]) ? $params[0] : [];
        if (count($wheres) > 0) {
            if (!isset($this->_wheres['$or']) OR !is_array($this->_wheres['$or'])) {
                $this->_wheres['$or'] = [];
            }

            foreach ($wheres as $where => $value) {
                $this->_wheres['$or'][] = $value;
            }
        }

        return $this;
    }

    protected function whereGt($params)
    {
        $field = isset($params[0]) ? $params[0] : '';
        $value = isset($params[1]) ? $params[1] : null;
        // die(var_dump($field, $value));
        $this->_whereInit($field);
        $this->_wheres[$field]['$gt'] = $value;

        return $this;
    }

    protected function whereGte($params)
    {
        $field = isset($params[0]) ? $params[0] : '';
        $value = isset($params[1]) ? $params[1] : null;
        $this->_whereInit($field);
        $this->_wheres[$field]['$gte'] = $value;

        return $this;
    }

    protected function whereLt($params)
    {
        $field = isset($params[0]) ? $params[0] : '';
        $value = isset($params[1]) ? $params[1] : null;
        $this->_whereInit($field);
        $this->_wheres[$field]['$lt'] = $value;

        return $this;
    }

    protected function whereLte($params)
    {
        $field = isset($params[0]) ? $params[0] : '';
        $value = isset($params[1]) ? $params[1] : null;
        $this->_whereInit($field);
        $this->_wheres[$field]['$lte'] = $value;

        return $this;
    }

    protected function order($params)
    {
        $fields = isset($params[0]) ? $params[0] : [];
        foreach ($fields as $field => $order) {
            if ($order === -1 OR $order === false OR strtolower($order) === 'desc') {
                $this->_sorts[$field] = -1;
            } else {
                $this->_sorts[$field] = 1;
            }
        }

        return $this;
    }

    protected function limit($params)
    {
        $limit = isset($params[0]) ? $params[0] : 99999;
        if ($limit !== null AND is_numeric($limit) AND $limit >= 1) {
            $this->_limit = (int)$limit;
        }

        return $this;
    }

    protected function skip($params)
    {
        $skip = isset($params[0]) ? $params[0] : 0;
        if ($skip !== null AND is_numeric($skip) AND $skip >= 1) {
            $this->_skip = (int)$skip;
        }

        return $this;
    }

    protected function distinct($params)
    {
        $distinct = isset($params[0]) ? $params[0] : '';

        // die(var_dump($this->_wheres));
        $r = $this->collection
            ->distinct($distinct, $this->_wheres);

        return $r;
    }

    protected function get($params, $field)
    {
        $format = isset($params[0]) ? $params[0] : null;

        $r = $this->collection
            ->find($this->_wheres, $field)
            ->sort($this->_sorts)
            ->limit($this->_limit)
            ->skip($this->_skip);

        $this->closeConnection();

        $return = '';

        if (in_array($format, ['String', 'JSON'])) {
            $formatType = 'arrayTo' . $format;

            foreach ($r as $key => $value) {
                $return .= '<pre class="uk-width-1-1"><code>'
                    . $this->highlight($this->$formatType($value))
                    . '</code></pre>';
            }

            return $return;
        } else {
            return $r;
        }
    }

    protected function count()
    {
        // die(var_dump($this->_wheres));
        $r = $this->collection
            ->find($this->_wheres)
            ->skip($this->_skip)
            ->count();

        $this->closeConnection();

        return $r;
    }

    /**
     * Get GUID
     * @return string
     */
    protected function getGUID()
    {
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $hyphen = "-";
        $uuid = chr(123)// "{"
            . substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12)
            . chr(125);// "}"
        return $uuid;
    }

    /**
     * Get millisecond
     *
     * @return string
     */
    protected function getMillisecond()
    {
        list($s1, $s2) = explode(' ', microtime());

        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    protected function highlight($string)
    {
        $string = highlight_string("<?php " . $string, true);
        $find = ['<span style="color: #0000BB">&lt;?php&nbsp;</span>', '&lt;?php&nbsp;'];
        $string = str_replace($find, '', $string);

        return $string;
    }

    public function group($keys = [], $initial = [], $reduce = [], $condition = [])
    {
        $this->initConnect();
        $res = $this->collection->group($keys, $initial, $reduce, $condition);

        return $res;
    }

}
