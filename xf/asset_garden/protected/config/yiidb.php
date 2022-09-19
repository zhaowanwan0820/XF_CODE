<?php

return [
    'class'                 => 'ItzDbConnection', //支持事务嵌套
    'charset'               => 'utf8',
    'enableProfiling'       => false,
    'schemaCachingDuration' => 3600,
    'servers'               => [
        [
            'connectionString' => ConfUtil::get('MySQL-itouzi-db-w.dsn'),
            'emulatePrepare' => true,
            'username' => ConfUtil::get('MySQL-itouzi-db-w.user'),
            'password' => ConfUtil::get('MySQL-itouzi-db-w.pwd'),
        ],
        [
            'connectionString' => ConfUtil::get('MySQL-itouzi-db-r.dsn'),
            'emulatePrepare' => true,
            'username' => ConfUtil::get('MySQL-itouzi-db-r.user'),
            'password' => ConfUtil::get('MySQL-itouzi-db-r.pwd'),
            'weight' => ConfUtil::get('MySQL-itouzi-db-r.weight')
        ],
    ],
];
