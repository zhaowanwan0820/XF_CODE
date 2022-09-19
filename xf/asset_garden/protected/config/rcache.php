<?php
/**
 * Redis Cache
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
            'timeout' => 0.5,
        ],
    ],
];
