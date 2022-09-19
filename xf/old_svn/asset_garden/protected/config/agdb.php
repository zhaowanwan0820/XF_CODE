<?php

return [
    'class'                 => 'ItzDbConnection', //支持事务嵌套
    'charset'               => 'utf8',
    'enableProfiling'       => false,
    'schemaCachingDuration' => 3600,
    'servers'               => [
        [
            'connectionString' => ConfUtil::get('MySQL-agdb.dsn'),
            'emulatePrepare' => true,
            'username' => ConfUtil::get('MySQL-agdb.user'),
            'password' => ConfUtil::get('MySQL-agdb.pwd'),
        ],
    ],
];
