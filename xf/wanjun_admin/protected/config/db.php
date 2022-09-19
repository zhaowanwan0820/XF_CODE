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
            'connectionString' => ConfUtil::get('MySQL-wj-rightsdb-w.dsn'),
            'emulatePrepare' => true,
            'username' => ConfUtil::get('MySQL-wj-rightsdb-w.user'),
            'password' => ConfUtil::get('MySQL-wj-rightsdb-w.pwd'),
        ]
    ],
];
