<?php
/**
 * rcache
 */
return [
    'class' => 'CRedisCache',
    'keyPrefix' => '',
    'hashKey' => false,
    'servers' => [
        [
            'host' => ConfUtil::get('Redis-3.host'),
            'port' => ConfUtil::get('Redis-3.port'),
            'timeout' => 1,
            'password' => ConfUtil::get('Redis-3.pwd'),
            'database' => ConfUtil::get('Redis-3.db'),
        ],
    ],
];

