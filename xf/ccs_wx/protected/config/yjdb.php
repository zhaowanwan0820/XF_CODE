<?php

return [
    'class'                 => 'ItzDbConnection', //支持事务嵌套
    'charset'               => 'utf8',
    'enableProfiling'       => false,
    'schemaCachingDuration' => 3600,
    'servers'               => [
        [
            'connectionString' => ConfUtil::get('MySQL-ag-yj.dsn'),
            'emulatePrepare' => true,
            'username' => ConfUtil::get('MySQL-ag-yj.user'),
            'password' => ConfUtil::get('MySQL-ag-yj.pwd'),
        ],
    ],
];
