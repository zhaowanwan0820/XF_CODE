<?php

return [
    'class'                 => 'ItzDbConnection', //支持事务嵌套
    'charset'               => 'utf8',
    'enableProfiling'       => false,
    'schemaCachingDuration' => 3600,
    'servers'               => [
        [
            'connectionString' => ConfUtil::get('MySQL-wj-contract.dsn'),
            'emulatePrepare' => true,
            'username' => ConfUtil::get('MySQL-wj-contract.user'),
            'password' => ConfUtil::get('MySQL-wj-contract.pwd'),
        ],
    ],
];

