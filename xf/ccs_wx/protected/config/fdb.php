<?php

return [
    'class'                 => 'ItzDbConnection', //支持事务嵌套
    'charset'               => 'utf8',
    'enableProfiling'       => false,
    'schemaCachingDuration' => 3600,
    'servers'               => [
        [
            'connectionString' => ConfUtil::get('MySQL-firstp2p.dsn'),
            'emulatePrepare' => true,
            'username' => ConfUtil::get('MySQL-firstp2p.user'),
            'password' => ConfUtil::get('MySQL-firstp2p.pwd'),
        ],
    ],
];
