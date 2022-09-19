<?php

return [
    'class'                 => 'ItzDbConnection', //支持事务嵌套
    'charset'               => 'utf8',
    'enableProfiling'       => false,
    'schemaCachingDuration' => 3600,
    'servers'               => [
        [
            'connectionString' => ConfUtil::get('MySQL-ncfph.dsn'),
            'emulatePrepare' => true,
            'username' => ConfUtil::get('MySQL-ncfph.user'),
            'password' => ConfUtil::get('MySQL-ncfph.pwd'),
        ],
        [
            'connectionString' => ConfUtil::get('MySQL-ncfph-r.dsn'),
            'emulatePrepare' => true,
            'username' => ConfUtil::get('MySQL-ncfph-r.user'),
            'password' => ConfUtil::get('MySQL-ncfph-r.pwd'),
            'weight' => 80
        ],
    ],
];
