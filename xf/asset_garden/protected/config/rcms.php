<?php

return [
    'class'                 => 'ItzDbConnection', //支持事务嵌套
    'charset'               => 'utf8',
    'enableProfiling'       => false,
    'schemaCachingDuration' => 3600,
    'servers'               => [
        [
            'connectionString' => ConfUtil::get('MySQL-rcms.dsn'),
            'emulatePrepare' => true,
            'username' => ConfUtil::get('MySQL-rcms.user'),
            'password' => ConfUtil::get('MySQL-rcms.pwd'),
        ]
    ],
];
