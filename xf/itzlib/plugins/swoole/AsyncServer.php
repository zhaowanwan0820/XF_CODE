<?php

use Swoole\Server;

/**
 * 异步任务服务器
 *
 * @author liuhaiyang (liuhaiyang@xxx.com)
 */
class AsyncServer
{
    /**
     * @var null|Server
     */
    private $server = null;
    /**
     * @var string 日志格式
     * 格式: 时间  [错误级别]  [分类]    [内容]
     */
    private $logTemplate = "%s  [%s]  [%s]    %s";
    private $logPath = '/tmp';
    /**
     * @var array 插件配置
     */
    private $plugins = [];
    /**
     * @var null|ServiceDiscovery
     */
    public $serviceDiscovery = null;

    const LOG_LEVEL_INFO = 'info';
    const LOG_LEVEL_WARNING = 'warning';
    const LOG_LEVEL_ERROR = 'error';

    const PROCESS_PREFIX = 'async_server_';

    /**
     * 异步任务服务器构造函数
     *
     * 加载配置文件,注册异步 TCP Server 回调事件方法.
     * AsyncServer constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        $config = empty($config) ? require(__DIR__ . '/config.php') : $config;
        $this->plugins = $config['plugins'];
        unset($config['plugins']);
        $server = new Server($config['host'], $config['port']);
        $server->set($config);

        $events = [
            'start',
            'shutdown',
            'connect',
            'receive',
            'task',
            'finish',
            'workerStart',
            'workerStop',
            'managerStart'
        ];
        foreach ($events as $event) {
            $cb = 'on' . ucfirst($event);
            $server->on($event, [$this, $cb]);
        }

        $this->server = $server;
        $this->logPath = dirname($config['log_file']);
    }

    /**
     * 检查日志目录写入权限, 在 start 时执行
     */
    public function checkPermission()
    {
        try {
            $logFile = $this->server->setting['log_file'];
            $logDir = dirname($logFile);
            if (!is_writeable($logDir)) {
                throw new Exception("日志目录不存在或没有写权限, 目录路径: {$logDir}");
            }
        } catch (Exception $e) {
            echo "{$e->getMessage()}" . PHP_EOL;
            exit(1);
        }
    }

    public function onConnect(Server $server, $fd, $from_id)
    {
        $client = $server->getClientInfo($fd);
        $msg = "Client {$client['remote_ip']}:{$client['remote_port']} connected";
        $this->log(self::LOG_LEVEL_INFO, __FUNCTION__, $msg);
    }

    /**
     * 启动时回调方法
     * @param Server $server
     * @return bool
     */
    public function onStart(Server $server)
    {
        swoole_set_process_name(self::PROCESS_PREFIX . 'master');
        $this->log(
            self::LOG_LEVEL_INFO,
            __FUNCTION__,
            "Server has started. Listening on: {$server->host}:{$server->port} ..."
        );
        return true;
    }

    /**
     * Receive 时回调方法
     * @param Server $server
     * @param $fd
     * @param $from_id
     * @param $data
     */
    public function onReceive(Server $server, $fd, $from_id, $data)
    {
        $data = trim($data);
        $this->log(self::LOG_LEVEL_INFO, __FUNCTION__, "data: {$data}");
        if (strlen($data) == 2) {
            $msg = $this->signal($data, $server);
            if (!empty($msg)) {
                $server->send($fd, $msg);
            }
        } else {
            $server->send($fd, 1);
            $server->task($data);
        }
    }

    /**
     * 信号功能
     * @param $sign
     * @param Server $server
     * @return mixed|string
     */
    public function signal($sign, Server $server)
    {
        switch ($sign) {
            // 状态
            case 'st':
                $msg = print_r($server->stats(), true);
                break;
            // 重载
            case 'rl':
                $server->reload();
                $msg = 'reload ok';
                break;
            default:
                $msg = '';
                break;
        }

        return $msg;
    }

    /**
     * Manager 启动回调方法
     * @param Server $server
     */
    public function onManagerStart(Server $server)
    {
        swoole_set_process_name(self::PROCESS_PREFIX . 'manager');
    }

    /**
     * Worker 启动时回调方法
     * @param Server $server
     * @param $worker_id
     */
    public function onWorkerStart(Server $server, $worker_id)
    {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        /* 分别设置 worker 和 task worker */
        $worker_num = $server->setting['worker_num'];
        if ($worker_id < $worker_num) {
            swoole_set_process_name(self::PROCESS_PREFIX . 'worker');
        } else {
            swoole_set_process_name(self::PROCESS_PREFIX . 'task');
            require(__DIR__ . "/loadYiic.php");
            require(__DIR__ . "/ConsoleCommandLocator.php");
        }
    }

    /**
     * 服务发现功能
     */
    public function runServiceDiscovery()
    {
        require(__DIR__ . "/ServiceDiscovery.php");
        $serviceDiscovery = new ServiceDiscovery();
        $this->serviceDiscovery = $serviceDiscovery;
        $serviceDiscovery->run('/itzservices');
    }

    /**
     * 执行异步任务
     * @param Server $server
     * @param $task_id
     * @param $from_id
     * @param $data
     */
    public function onTask(Server $server, $task_id, $from_id, $data)
    {
        list($class, $method, $params) = json_decode($data, true);

        ob_start();
        try {
            if (stripos($class, "command")) {
                $commandPath = Yii::app()->getBasePath() . "/commands";
                $runner = new CConsoleCommandRunner();
                $runner->addCommands($commandPath);
                $argv = ConsoleCommandLocator::getRunArgv(substr($class, 0, -7), $method, $params);
                $runner->run($argv);
            } else {
                $obj = new $class();
                $callback = [$obj, $method];
                if (is_callable($callback)) {
                    call_user_func_array([$obj, $method], $params);
                } else {
                    $this->log(self::LOG_LEVEL_WARNING, __CLASS__, "方法不可调用" . print_r($callback, true));
                }
            }
        } catch (Exception $e) {
            $this->log(self::LOG_LEVEL_ERROR, __CLASS__, $e->getMessage());
        }
        $content = ob_get_clean();
        Yii::getLogger()->flush(true);
        error_log($content, 3, "{$this->logPath}/task.log");

        /* 短连接 MySQL gone away 解决方案 */
        foreach (Yii::app()->getComponents(true) as $key => $component) {
            if (get_class($component) == 'ItzDbConnection') {
                $component->master = null;
                $component->slave = null;
            }
        }
    }

    /**
     * 启动 Server
     */
    public function start()
    {
        $this->checkPermission();
        $this->server->start();
    }

    /**
     * 记录日志方法
     * @param $level
     * @param $category
     * @param $msg
     */
    public function log($level, $category, $msg)
    {
        $time = time();
        $datetime = date("Y-m-d H:i:s", $time) . " {$time}";
        printf($this->logTemplate, $datetime, $level, $category, $msg . PHP_EOL);
    }

    /**
     * 关闭前回调方法
     * @param Server $server
     */
    public function onShutdown(Server $server)
    {
        $this->log(self::LOG_LEVEL_INFO, __FUNCTION__,
            str_replace(["\r",
                         "\n",
                         "\r\n"], "", "Shutdown status: " . print_r($server->stats(), true)));
        $this->log(self::LOG_LEVEL_INFO, __FUNCTION__, "Server has shutdown.");
    }

    public function onFinish(Server $server, $task_id, $data) {}

    public function onWorkerStop(Server $server, $worker_id) {}

    /**
     * 关闭服务器
     */
    public function shutdown()
    {
        $this->server->shutdown();
    }
}
