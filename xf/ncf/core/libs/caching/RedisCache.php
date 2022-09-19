<?php
/**
 * RedisCache class file
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace libs\caching;

use libs\caching\Cache;

/**
 * RedisCache implements a cache application component based on {@link http://redis.io/ redis}.
 *
 * RedisCache needs to be configured with {@link hostname}, {@link port} and {@link database} of the server
 * to connect to. By default RedisCache assumes there is a redis server running on localhost at
 * port 6379 and uses the database number 0.
 *
 * RedisCache also supports {@link http://redis.io/commands/auth the AUTH command} of redis.
 * When the server needs authentication, you can set the {@link password} property to
 * authenticate with the server after connect.
 *
 * See {@link CCache} manual for common cache operations that are supported by RedisCache.
 *
 * To use RedisCache as the cache application component, configure the application as follows,
 * <pre>
 * array(
 *     'components'=>array(
 *         'cache'=>array(
 *             'class'=>'RedisCache',
 *             'hostname'=>'localhost',
 *             'port'=>6379,
 *             'database'=>0,
 *         ),
 *     ),
 * )
 * </pre>
 *
 * The minimum required redis version is 2.0.0.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 */
class RedisCache extends Cache
{
    /**
     * @var string hostname to use for connecting to the redis server. Defaults to 'localhost'.
     */
    public $hostname='localhost';
    /**
     * @var int the port to use for connecting to the redis server. Default port is 6379.
     */
    public $port=6379;
    /**
     * @var string the password to use to authenticate with the redis server. If not set, no AUTH command will be sent.
     */
    public $password;
    /**
     * @var int the redis database to use. This is an integer value starting from 0. Defaults to 0.
     */
    public $database=0;
    /**
     * @var float timeout to use for connection to redis. If not set the timeout set in php.ini will be used: ini_get("default_socket_timeout")
     */
    public $timeout=null;
    /**
     * @var resource redis socket connection
     */
    private $_socket;

    /**
     * Establishes a connection to the redis server.
     * It does nothing if the connection has already been established.
     */
    protected function connect()
    {
        $this->_socket=@stream_socket_client(
            $this->hostname.':'.$this->port,
            $errorNumber,
            $errorDescription,
            $this->timeout ? $this->timeout : ini_get("default_socket_timeout")
        );
        if ($this->_socket)
        {
            if($this->password!==null)
                $this->executeCommand('AUTH',array($this->password));
            $this->executeCommand('SELECT',array($this->database));
        }
        else
            return false;
    }

    /**
     * Executes a redis command.
     * For a list of available commands and their parameters see {@link http://redis.io/commands}.
     *
     * @param string $name the name of the command
     * @param array $params list of parameters for the command
     * @return array|bool|null|string Dependend on the executed command this method
     * will return different data types:
     * <ul>
     *   <li><code>true</code> for commands that return "status reply".</li>
     *   <li><code>string</code> for commands that return "integer reply"
     *       as the value is in the range of a signed 64 bit integer.</li>
     *   <li><code>string</code> or <code>null</code> for commands that return "bulk reply".</li>
     *   <li><code>array</code> for commands that return "Multi-bulk replies".</li>
     * </ul>
     * See {@link http://redis.io/topics/protocol redis protocol description}
     * for details on the mentioned reply types.
     */
    public function executeCommand($name,$params=array())
    {
        if($this->_socket===null)
            $this->connect();

        array_unshift($params,$name);
        $command='*'.count($params)."\r\n";
        foreach($params as $arg)
            $command.='$'.strlen($arg)."\r\n".$arg."\r\n";
        if($this->_socket) {
            fwrite($this->_socket,$command);
        }
        return $this->parseResponse(implode(' ',$params));
    }

    /**
     * Reads the result from socket and parses it
     * @return array|bool|null|string
     */
    private function parseResponse()
    {
        if(!$this->_socket) {
            return false;
        }
        if(($line=fgets($this->_socket))===false)
            return false;
        $type=$line[0];
        $respStr = $line;
        $line=substr($line,1,-2);
        switch($type)
        {
            case '+': // Status reply
                return true;
            case '-': // Error reply
                return false;
            case ':': // Integer reply
                // no cast to int as it is in the range of a signed 64 bit integer
                return $line;
            case '$': // Bulk replies
                if($line=='-1')
                    return null;
                $length=$line+2;
                $data='';
                while($length>0)
                {
                    if(($block=fread($this->_socket,$length))===false)
                        return false;
                    $data.=$block;
                    $length-=(function_exists('mb_strlen') ? mb_strlen($block,'8bit') : strlen($block));
                }
                return substr($data,0,-2);
            case '*': // Multi-bulk replies
                $count=(int)$line;
                $data=array();
                for($i=0;$i<$count;$i++)
                    $data[]=$this->parseResponse();
                return $data;
            default:
                error_log('REDIS_PARSE_ERROR.'.$respStr);
                return false;
        }
    }

    /**
     * Retrieves a value from cache with a specified key.
     * This is the implementation of the method declared in the parent class.
     * @param string $key a unique key identifying the cached value
     * @return string|boolean the value stored in cache, false if the value is not in the cache or expired.
     */
    public function getValue($key)
    {
        return unserialize($this->executeCommand('GET',array($key)));
    }

    /**
     * Retrieves multiple values from cache with the specified keys.
     * @param array $keys a list of keys identifying the cached values
     * @return array a list of cached values indexed by the keys
     */
    protected function getValues($keys)
    {
        $response=$this->executeCommand('MGET',$keys);
        $result=array();
        $i=0;
        foreach($keys as $key)
            $result[$key]=$response[$i++];
        return $result;
    }

    /**
     * Stores a value identified by a key in cache.
     * This is the implementation of the method declared in the parent class.
     *
     * @param string $key the key identifying the value to be cached
     * @param string $value the value to be cached
     * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
     * @return boolean true if the value is successfully stored into cache, false otherwise
     */
    protected function setValue($key,$value,$expire)
    {
        $value = serialize($value);
        if ($expire==0)
            return (bool)$this->executeCommand('SET',array($key,$value));
        return (bool)$this->executeCommand('SETEX',array($key,$expire,$value));
    }

    /**
     * Stores a value identified by a key into cache if the cache does not contain this key.
     * This is the implementation of the method declared in the parent class.
     *
     * @param string $key the key identifying the value to be cached
     * @param string $value the value to be cached
     * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
     * @return boolean true if the value is successfully stored into cache, false otherwise
     */
    public function addValue($key,$value,$expire)
    {
        if ($expire == 0)
            return (bool)$this->executeCommand('SETNX',array($key,$value));

        if($this->executeCommand('SETNX',array($key,$value)))
        {
            $this->executeCommand('EXPIRE',array($key,$expire));
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Deletes a value with the specified key from cache
     * This is the implementation of the method declared in the parent class.
     * @param string $key the key of the value to be deleted
     * @return boolean if no error happens during deletion
     */
    public function deleteValue($key)
    {
        return (bool)$this->executeCommand('DEL',array($key));
    }

    public function incrValue($key) {
        $ret = $this->executeCommand('INCR', array($key));
        if($ret === false){
            return false;
        }
        if(intval($ret) == 1){
            // 如果加完之后是1，那这货就是个新的key,给一个默认值1分钟
            $this->setExpire($key,60);
        }
        return intval($ret);
    }

    public function decrValue($key) {
        $ret = $this->executeCommand('DECR', array($key));
        if($ret === false){
            return false;
        }
        return intval($ret);
    }

    public function setExpire($key,$expire){
        return $this->executeCommand('EXPIRE',array($key,$expire));
    }

    /**
     * Deletes all values from cache.
     * This is the implementation of the method declared in the parent class.
     * @return boolean whether the flush operation was successful.
     */
    protected function flushValues()
    {
        return $this->executeCommand('FLUSHDB');
    }

    public function lpop($key) {
        return $this->executeCommand('LPOP', array($key));
    }

    public function lpush($key, $data) {
        return $this->executeCommand('LPUSH', array($key, $data));
    }

    public function rpop($key) {
        return $this->executeCommand('RPOP', array($key));
    }

    public function rpush($key, $data) {
        return $this->executeCommand("RPUSH", array($key, $data));
    }

    public function llen($key) {
        return $this->executeCommand('LLEN', array($key));
    }

    public function lindex($key, $index) {
        return $this->executeCommand("LINDEX", array($key, $index));
    }

    public function hexists($key, $field) {
        return $this->executeCommand("HEXISTS", array($key, $field));
    }

    public function hset($key, $field, $value) {
        return $this->executeCommand("HSET", array($key, $field, $value));
    }

    public function hget($key, $field) {
        return $this->executeCommand("HGET", array($key, $field));
    }

    public function hdel($key, $field) {
        return $this->executeCommand("HDEL", array($key, $field));
    }

    public function call($moduleInstance, $function, $param, $expire = 10) {
        $site_id = app_conf('TEMPLATE_ID');
        if ($moduleInstance instanceof \libs\rpc\Rpc) {
            $cacheKey = sprintf("DATACACHE_%s_%s_%s_%s_%s", $site_id, get_class($moduleInstance), $function, $param[0], md5(serialize($param)));
        } else {
            $cacheKey = sprintf("DATACACHE_%s_%s_%s_%s", $site_id, get_class($moduleInstance), $function, md5(serialize($param)));
        }
        /**
         * 缓存开关打开
         */
        if (1 == $GLOBALS['sys_config']['REDIS_DATA_CACHE']) {
            $cacheData = $this->getValue($cacheKey);
            if ($cacheData !== FALSE) {
                if (1 == $GLOBALS['sys_config']['REDIS_DATA_CACHE_DEBUG']) {
                    $message = array(
                        "ip" => get_client_ip(),
                        "isUsedCache" => "isUsedCache",
                        "cacheKey" => $cacheKey,
                        "cacheData" => substr(serialize($cacheData), 0, 200),
                    );
                    \libs\utils\Logger::debug(implode(" | ", $message));
                }

                return $cacheData;
            }
        }
        $result = call_user_func_array(array($moduleInstance, $function), $param);

        if (false !== $result) {
            $this->setValue($cacheKey, $result, $expire);
        }
        return $result;
    }
}
