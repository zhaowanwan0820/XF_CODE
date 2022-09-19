<?php

return [
    'class'                 => 'ItzDbConnection', //支持事务嵌套
    'charset'               => 'utf8',
    'enableProfiling'       => false,
    'schemaCachingDuration' => 3600,
    'servers'               => [
        [
            'connectionString' => ConfUtil::get('MySQL-xf-firstp2p.dsn'),
            'emulatePrepare' => true,
            'username' => ConfUtil::get('MySQL-xf-firstp2p.user'),
            'password' => ConfUtil::get('MySQL-xf-firstp2p.pwd'),
        ],
        [
            'connectionString' => ConfUtil::get('MySQL-xf-firstp2p-r.dsn'),
            'emulatePrepare' => true,
            'username' => ConfUtil::get('MySQL-xf-firstp2p-r.user'),
            'password' => ConfUtil::get('MySQL-xf-firstp2p-r.pwd'),
            'weight' => ConfUtil::get('MySQL-xianfeng-db-r.weight')
        ]
    ],
];
