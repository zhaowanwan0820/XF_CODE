<?php
/**
 * archives新老员工id对照表
 * Created by PhpStorm.
 * User: jh
 * Date: 2016/12/6
 * Time: 21:18
 */
return [
    'class' => 'ItzDbConnection',
    'charset' => 'utf8',
    'enableProfiling' => false,
    'schemaCachingDuration' => 3600,
    'servers' => [
        [
            'connectionString' => ConfUtil::get('newuser-archives-w.dsn'),
            'emulatePrepare' => true,
            'username' => ConfUtil::get('newuser-archives-w.user'),
            'password' => ConfUtil::get('newuser-archives-w.pwd'),
        ]
    ],
];