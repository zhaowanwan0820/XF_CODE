<?php

return [
    'class'                 => 'ItzDbConnection', //支持事务嵌套
    'charset'               => 'utf8',
    'enableProfiling'       => false,
    'schemaCachingDuration' => 3600,
    'servers'               => [
        [
            'connectionString' => ConfUtil::get('MySQL-xf-firstp2p-2.dsn'),
            'emulatePrepare' => true,
            'username' => ConfUtil::get('MySQL-xf-firstp2p-2.user'),
            'password' => ConfUtil::get('MySQL-xf-firstp2p-2.pwd'),
        ]
    ],
];
