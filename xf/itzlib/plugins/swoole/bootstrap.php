<?php

/**
 * Async Server Bootstrap 启动器
 *
 * @author liuhaiyang (liuhaiyang@xxx.com)
 */

require(__DIR__ . "/AsyncServer.php");

function ASYNC_start()
{
    ini_set('display_errors', 0);
    error_reporting(E_ERROR);
    date_default_timezone_set("Asia/Shanghai");

    require dirname(dirname(__DIR__)) . "/util/ConfUtil.php";
    $config = ASYNC_loadConfig();
    /* 加载 Async Server */
    $server = new AsyncServer($config);
    $server->start();
}

function ASYNC_getLogPath()
{
    defined('ASYNC_DIR') or exit('UNDEFINED ASYNC_DIR!');
    if (strpos(ASYNC_DIR, 'dashboard/protected') !== false) {
        $yiiLogPath = getenv('runtimePath');
    } else {
        $yiiLogPath = getenv('runtimePath-itouzi');
    }

    if ($yiiLogPath === false) {
        $yiiLogPath = '/home/work/logs';
    }

    return $yiiLogPath . DIRECTORY_SEPARATOR . 'swoole';
}

function ASYNC_getLogicCoreNum()
{
    if (ASYNC_isMac()) {
        $worker_num = trim(`sysctl -n machdep.cpu.thread_count`);
    } else {
        $worker_num = trim(`cat /proc/cpuinfo 2>/dev/null | grep processor | wc -l`);
    }
    return $worker_num;
}

function ASYNC_isMac()
{
    return strpos(php_uname(), 'Darwin') !== false;
}

/**
 * 读取启动参数配置
 * 目前支持：
 *      -h  listen host
 *      -p  listen port
 *      -d  守护化
 * @return array
 */
function ASYNC_loadConfig()
{
    $config = [];
    $argv = $_SERVER['argv'];
    if ($key = array_search('-h', $argv)) {
        $config['host'] = $argv[$key + 1];
    }
    if ($key = array_search('-p', $argv)) {
        $config['port'] = $argv[$key + 1];
    }
    if ($key = array_search('-d', $argv)) {
        $config['daemonize'] = true;
    }
    if ($key = array_search('-mq', $argv)) {
        $config['message_queue_key'] = ftok(__FILE__, $argv[$key + 1]);
    }

    $config += require('config.php');

    /* 未配置的情况下, worker_num 为 logic cpu core num - 1 */
    if (!isset($config['worker_num'])) {
        $cores = ASYNC_getLogicCoreNum();
        $config['worker_num'] = $cores > 1 ? $cores - 1 : 1;
    }
    /* 未配置的情况下, task_worker_num 为 worker_num * 3 */
    if (!isset($config['task_worker_num'])) {
        $config['task_worker_num'] = $config['worker_num'] * 3;
    }
    /* 如果 worker_num == 1 , 则取消 cpu_affinity_ignore */
    if ($config['worker_num'] == 1) {
        $config['cpu_affinity_ignore'] = [];
    }
    /* 未配置的情况下, 日志文件为 dirname(yii.logPath)/swoole/server.log */
    if (!isset($config['log_file'])) {
        $config['log_file'] = ASYNC_getLogPath() . DIRECTORY_SEPARATOR . 'server.log';
    }

    return $config;
}

/**
 * 关闭 Server
 * @ref http://wiki.swoole.com/wiki/page/p-server/reload.html
 */
function ASYNC_shutdown()
{
    /* 柔性终止 Server */
    $pid = ASYNC_getMasterPId();
    if (empty($pid)) {
        echo "Server not found!" . PHP_EOL;
    } else {
        exec("kill -15 {$pid}");
        echo "Server has shutdown" . PHP_EOL;
    }
    sleep(1);
    if ($key = array_search('-f', $_SERVER['argv'])) {
        ASYNC_killLeftWorker();
    }
    return $pid;
}

/**
 * 杀死残留子进程
 */
function ASYNC_killLeftWorker()
{
    $worker = AsyncServer::PROCESS_PREFIX . 'worker';
    $taskWorker = AsyncServer::PROCESS_PREFIX . 'task';

    $pids = ASYNC_pidof($worker) . ' ' . ASYNC_pidof($taskWorker);
    if (trim($pids)) {
        exec("kill -15 {$pids} 2>/dev/null");
        echo "{$pids} worker process has been killed." . PHP_EOL;
    }
}

function ASYNC_pidof($process)
{
    $pids = exec("pidof {$process} 2>&1");
    if (strpos($pids, "no") !== false) {
        $pids = exec("ps axu | grep '{$process}' | grep -v 'grep' |awk '{print $2}' | tr \"\n\" \" \"");
    }
    return $pids;
}

function ASYNC_restart()
{
    $pid = ASYNC_shutdown();
    ASYNC_killLeftWorker();
    if (!$pid) {
        echo "ERROR: There has no server to be restarted!" . PHP_EOL;
        exit;
    } else {
        $_SERVER['argv'][] = '-d';
        ASYNC_start();
    }
}

/**
 * 通过端口号获取 PID
 * @return string
 */
function ASYNC_getMasterPId()
{
    $argv = $_SERVER['argv'];
    if ($key = array_search('-p', $argv)) {
        if (is_numeric($argv[$key + 1])) {
            $port = $argv[$key + 1];
        } else {
            echo "ERROR: 参数必须是一个整数类型的端口号。" . PHP_EOL;
            exit;
        }
    } else {
        $port = 10101;
    }
    return exec("lsof -i:{$port} | grep \"LISTEN\" |  awk '{print $2}'");
}

function ASYNC_reload()
{
    $pid = ASYNC_getMasterPId();
    if (empty($pid)) {
        echo "Server not found!" . PHP_EOL;
    } else {
        echo "Reloading..." . PHP_EOL;
        exec("kill -USR2 {$pid}", $out);
        echo "Reloaded" . PHP_EOL;
    }
}

function ASYNC_help()
{
    echo <<< 'HELP'

Async Server bootstrap

Usage: php run.php <command-name> [parameters...]

    以下是可用命令
    - start [-dhp] <params>         启动异步服务。 支持传递 host, port, daemon 等配置参数.
                                    1. 守护启动        ... start -d
                                    2. 指定主机端口     ... start -h 127.0.0.1 -p 10101

    - shutdown [-pf] <params>       关闭异步服务。
                                    -p 10101 关闭指定监听端口的 Server 。(可用于开发时的双启)
                                    -f 强制关闭所有进程 (用于异常时关闭残留子进程)

    - restart [-p] <params>         重启异步服务。
                                    -p 10101 重启指定监听端口的 Server 。(可用于开发时的双启)

    - reload                        热重启异步服务，每个 worker 在完成当前任务后会自动退出. 主要用于
                                    重新载入新的代码修改。


HELP;
}

if (!defined("ASYNC_DIR")) {
    echo "异步启动路径未配置!" . PHP_EOL;
    exit();
}
/* 获取执行命令 */
if (isset($_SERVER['argv'][1])) {
    $action = $_SERVER['argv'][1];
} else {
    $action = "help";
}

$function = "ASYNC_{$action}";
if (!is_callable($function)) {
    echo "unknown action: {$action} ！" . PHP_EOL;
} else {
    call_user_func_array($function, []);
}
