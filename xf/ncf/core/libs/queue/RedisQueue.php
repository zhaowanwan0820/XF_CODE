<?php
namespace libs\queue;

use libs\base;
use libs\base\Component;
use libs\base\IComponent;
use libs\queue\IQueue;

/**
 * 基于redis的队列实现
 **/
class RedisQueue extends Component implements IQueue, IComponent {
    public $hostname = "localhost";
    public $port = 6379;
    public $password;
    public $database = 0;
    public $timeout = null;
    public $channel = '';

    private $_socket;

    public function init() {
        $this->connect();
    }

    /**
     * 与服务器建立连接
     * @return void
     **/
    protected function connect() {
        $this->_socket = @stream_socket_client(
            $this->hostname . ':' . $this->port,
            $errorNumber,
            $errorDescription,
            $this->timeout ? $this->timeout : ini_get("default_socket_timeout")
        );
        if ($this->_socket) {
            if ($this->password !== null) {
                $this->executeCommand('AUTH', array($this->password));
            }
            $this->executeCommand('SELECT', array($this->database));
            @stream_set_timeout($this->_socket, -1);
        } else {
            return false;
        }
    }

    /**
     * 执行redis命令
     * @return void
     **/
    public function executeCommand($name, $params = array()) {
        if ($this->_socket === null) {
            $this->connect();
        }
        array_unshift($params, $name);
        $command = '*' . count($params) . "\r\n";
        foreach ($params as $arg) {
            $command .= '$' . strlen($arg) . "\r\n" . $arg . "\r\n";
        }
        fwrite($this->_socket, $command);
        return $this->parseResponse(implode(' ', $params));
    }

    /**
     * 解析命令执行结果
     * @return void
     **/
    private function parseResponse() {
        if (($line = fgets($this->_socket)) === false) {
            return false;
        }
        $type = $line[0];
        $line = substr($line, 1, -2);
        switch ($type) {
            case '+': // Status reply
                return true;
            case '-': // Error reply
                return false;
            case ':': // Integer reply
                // no cast to int as it is in the range of a signed 64 bit integer
                return $line;
            case '$': // Bulk replies
                if ($line == '-1')
                    return null;
                $length = $line + 2;
                $data   = '';
                while ($length > 0) {
                    if (($block = fread($this->_socket, $length)) === false)
                        return false;
                    $data .= $block;
                    $length -= (function_exists('mb_strlen') ? mb_strlen($block, '8bit') : strlen($block));
                }
                return substr($data, 0, -2);
            case '*': // Multi-bulk replies
                $count = (int)$line;
                $data  = array();
                for ($i = 0; $i < $count; $i++)
                    $data[] = $this->parseResponse();
                return $data;
            default:
                return false;
        }
    }

    /**
     * 将信息push到队列中
     * @param $message
     * @return bool
     */
    public function send($message) {
        return (bool)$this->executeCommand('RPUSH', array($this->channel, serialize($message)));
    }

    /**
     * 将数据从队列顶部pop出来，取出数据时将数据删除
     * @return bool|mixed
     */
    public function receive() {
        $res = $this->executeCommand('BLPOP', array($this->channel, "0"));
        if ($res) {
            return unserialize($res[1]);
        } else {
            return false;
        }
    }

    /**
     * 删除方法被废弃
     */
    public function delete() {
        return false;
    }

    /**
     * 读取当前队列中的数据总量，后台统计使用
     * @return int
     */
    public function len() {
        return intval($this->executeCommand('LLEN', array($this->channel)));
    }

    /**
     * 清空队列内容，慎用！
     * @return bool
     */
    public function flush() {
        return (bool)$this->executeCommand('DEL', array($this->channel));
    }
}
