<?php

use Swoole\Client;

class AsyncClient extends \CApplicationComponent
{
    public $cluster = [];
    public $alertUser = [];
    public $error = "";

    public $connectedServer = "";

    /**
     * @var \Swoole\Client $client
     */
    private $client;

    public function init()
    {
        parent::init();
        $this->client = new Client(SWOOLE_SOCK_TCP);
    }

    public function __destruct()
    {
        $this->close();
    }

    private function connect()
    {
        $timeout = 0.2;
        foreach ($this->cluster as $server) {
            if ($this->client->connect($server[0], $server[1], $timeout)) {
                $this->connectedServer = "{$server[0]}:{$server[1]}";
                return true;
            } else {
                Yii::log("AsyncServer {$server[0]}:{$server[1]} connection failure", CLogger::LEVEL_ERROR, __CLASS__);
            }
            $timeout *= 2;
        }
        Yii::log("All AsyncServer connection failure.", CLogger::LEVEL_ERROR, __CLASS__);
        $this->sendAlertMsg("连接所有异步服务器失败！", true, true);
        return false;
    }

    public function sendAlertMsg($msg, $needEmail = true, $needSms = false)
    {
        $time = date("Y-m-d H:i:s");
        $host = gethostname() . " [{$_SERVER['SERVER_ADDR']}]";

        $remind = [];
        $remind['sent_user'] = 0;
        $remind['receive_user'] = 1;
        $remind['data']['alert_msg'] = "异步任务客户端出错: {$time} {$host} {$msg}" . PHP_EOL;
        $remind['data']['alert_email_title'] = "{$host} 异步任务客户端出错";
        $remind['mtype'] = "program_110";

        foreach ($this->alertUser as $user) {
            $remind['phone'] = $user[0];
            $remind['email'] = $user[1];
            $res = NewRemindService::getInstance()->SendToUser($remind, false, $needEmail, $needSms);
            if (!$res) {
                Yii::log("报警消息通知失败。" . print_r($remind, true), CLogger::LEVEL_ERROR, __CLASS__);
            }
        }
    }

    public function isConnected()
    {
        return $this->client->isConnected();
    }

    private function package($data)
    {
        return $data . "\r\n";
    }

    /**
     * 发送数据到服务端
     * connect -> send -> recv -> return
     *
     * @param string  $data         要发送的数据
     * @param int     $retryTimes   当前重试次数
     * @param bool    $forceConnect 是否强制重连 Server
     * @return bool
     */
    public function send($data, $retryTimes = 0, $forceConnect = false)
    {
        Yii::log("AsyncClient send data: {$data} , retry : {$retryTimes}", CLogger::LEVEL_INFO, __CLASS__);

        try {
            if (!$this->client->isConnected() || $forceConnect) {
                if (!$this->connect()) {
                    /* connect 方法已有失败报警信息，故此处 Exception 抛 errno 0，表示不再发报警 */
                    throw new InvalidArgumentException("客户端连接服务器失败。", 0);
                }
            }

            /* 发送数据 */
            if (!$this->client->send($this->package($data))) {
                /* 失败重试 1 次，并强制重连 Server  */
                if ($retryTimes < 1) {
                    return $this->send($data, $retryTimes + 1, true);
                } else {
                    throw new InvalidArgumentException("客户端信息发送失败。", 1);
                }
            }

            /* 接收响应 */
            if (!$this->client->recv()) {
                if (in_array($this->client->errCode, [0, 11, 32])) {
                    /* 0, 11 为已知在被正常处理下可能出现的响应失败返回码，故不需发送报警 errno 0 */
                    throw new InvalidArgumentException("服务端无响应。", 0);
                } else {
                    throw new InvalidArgumentException("服务端无响应。", 1);
                }
            }

        } catch (Exception $e) {
            $msg = $e->getMessage() . " errno: {$this->client->errCode}, server: {$this->connectedServer}";
            if ($e->getCode() == 1) {
                $this->sendAlertMsg($msg);
            }
            $this->error = $msg;
            Yii::log("{$msg} | retry: {$retryTimes} | data: {$data}", CLogger::LEVEL_ERROR, __CLASS__ . "_" . __FUNCTION__);
            return false;
        }

        return true;
    }

    public function close()
    {
        if ($this->client instanceof swoole_client) {
            $this->client->close();
        }
    }

    public function dispatch($class, $method, $params = [])
    {
        if (stripos($class, "command") !== false) {
            if (isset($params[0])) {
                $this->error = "脚本方法的调用， params 参数必须是一个 关联（ assoc ）数组。";
                return false;
            }
            $commandPath = Yii::app()->getBasePath() . "/commands";
            require_once("{$commandPath}/{$class}.php");
            if (!method_exists($class, "action{$method}")) {
                $this->error = "异步调用的类或方法不存在。";
                return false;
            }
        } else {
            if (!isset($params[0])) {
                $this->error = "一般方法的调用， params 参数必须是一个 索引（ index ）数组。";
                return false;
            }
            if (!method_exists($class, "{$method}")) {
                $this->error = "异步调用的类或方法不存在。";
                return false;
            }
        }

        return $this->send(json_encode([$class, $method, $params]));
    }

    public function getError()
    {
        return $this->error;
    }

}
