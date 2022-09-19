<?php
/**
 * 数据库
 */
return [
    'class' => 'ItzDbConnection',
    'charset' => 'utf8',
    'enableProfiling' => false,
    'schemaCachingDuration' => 3600,
    'servers' => [
        [
            'connectionString' => ConfUtil::get('MySQL-xf-rcms.dsn'),
            'emulatePrepare' => true,
            'username' => ConfUtil::get('MySQL-xf-rcms.user'),
            'password' => ConfUtil::get('MySQL-xf-rcms.pwd'),
        ]
    ],
];
