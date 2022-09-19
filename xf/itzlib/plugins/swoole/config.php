<?php
/*
 * Swoole Async Server 配置
 *
 ****** 此为 Swoole 服务端配置, 与客户端无关。 ********
 */

return [
    /* Server 监听配置 */
    'host' => '0.0.0.0',
    'port' => 10101,

    /* 在默认情况下， 将自动启动 CPU 逻辑核数个 worker_num 和 *3 个 task_worker_num */
    /*
    'worker_num' => 4,
    'task_worker_num' => 16,
    */
    'task_max_request' => 4096,

    'open_cpu_affinity' => true,
    'cpu_affinity_ignore' => [0],

    /* 守护 */
    'daemonize' => false,

    /* 粘包处理 */
    'open_eof_check' => 1,
    'package_eof' => "\r\n",
    'open_eof_split' => true,

    /* 连接检查，每 10 秒检测一次，自动关闭 60 秒内空闲的连接 */
    'heartbeat_idle_time' => 60,
    'heartbeat_check_interval' => 10,

    /* 默认情况下, 取 yii log 同级目录下, 建 swoole 新目录作为日志目录 */
    'log_file' => '/home/work/logs/swoole/server.log',
    'log_level' => 1,

    /* 队列类型 1.unix socket模式 2.内存方式 */
    'task_ipc_mode' => 2,

    'plugins' => [
        /* 服务发现 */
        'serviceDiscovery' => false,
    ],
];
