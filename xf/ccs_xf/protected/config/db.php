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
            'connectionString' => ConfUtil::get('MySQL-xf-rightsdb-w.dsn'),
            'emulatePrepare' => true,
            'username' => ConfUtil::get('MySQL-xf-rightsdb-w.user'),
            'password' => ConfUtil::get('MySQL-xf-rightsdb-w.pwd'),
        ]
    ],
];
