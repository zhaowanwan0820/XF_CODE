<?php
namespace libs\queue;

use libs\base\Component;

/**
 * 基于redis 带优先级的队列
 * @author  caolong<caolong@ucfgroup.com>
 * @date    2013-12-6
 **/

class PriorQueue extends Component implements IQueue {
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
                if ($line == '-1') {
                    return null;
                }
                $length = $line + 2;
                $data   = '';
                while ($length > 0) {
                    if (($block = fread($this->_socket, $length)) === false) {
                        return false;
                    }
                    $data .= $block;
                    $length -= (function_exists('mb_strlen') ? mb_strlen($block, '8bit') : strlen($block));
                }
                return substr($data, 0, -2);
            case '*': // Multi-bulk replies
                $count = (int)$line;
                $data  = array();
                for ($i = 0; $i < $count; $i++) {
                    $data[] = $this->parseResponse();
                }
                return $data;
            default:
                return false;
        }
    }

    /**
     * 将信息加入到队列中
     * @param $message
     * @return bool
     */
    public function send($message) {
        return (bool)$this->executeCommand('ZADD', array($this->channel, 0, serialize($message)));
    }

    /**
     * 从队列中取出指定数量的值，读取时并不从队列顶部删除数据
     * @param int $count 默认为0，取出的数据数量为$count+1
     * @return bool|mixed|void
     */
    public function receive($count = 0) {
        $res = $this->executeCommand('ZRANGE', array($this->channel, 0, $count));
        if ($res) {
            if ($count == 0) {
                return unserialize($res[0]);
            } else {
                return $res;
            }
        } else {
            return false;
        }
    }

    /**
     * 从队列顶部删除一个数据
     * @return bool
     */
    public function delete() {
        return (bool)$this->executeCommand('ZREMRANGEBYRANK', array($this->channel, 0, 0));
    }

    /**
     * 读取当前队列中的数据总量，后台统计使用
     * @return int
     */
    public function len() {
        return intval($this->executeCommand('ZCOUNT', array($this->channel, "-inf", "+inf")));
    }

    /**
     * 清空队列内容，慎用！
     * @return bool
     */
    public function flush() {
        return (bool)$this->executeCommand('DEL', array($this->channel));
    }
}
