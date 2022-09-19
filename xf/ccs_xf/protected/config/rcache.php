<?php
/**
 * Redis Cache
 *
 * retry 重试次数
 */
return [
    'class' => 'CRedisCache',
    'keyPrefix' => '',
    'hashKey' => false,
    'servers' => [
        [
            'host' => ConfUtil::get("Redis-1.host"),
            'port' => ConfUtil::get("Redis-1.port"),
            'password' => ConfUtil::get('Redis-1.pwd'),
            'retry' => 2,
            'timeout' => 2,
        ]
    ],
];
