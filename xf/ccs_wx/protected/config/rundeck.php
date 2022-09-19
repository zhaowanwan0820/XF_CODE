<?php
/*
 * Rundeck 配置
 *
 */
return [
    'class' => 'Rundeck',
    'token' => ConfUtil::get('Rundeck-1.token'),
    'project' => ConfUtil::get('Rundeck-1.project'),
    'url' => ConfUtil::get('Rundeck-1.url'),
    'phpPath' => ConfUtil::get('Rundeck-1.phpPath'),
    // Rundeck 服务器代码目录，勿改。
    'yiicPath' => '/data/web_doc_root/2v/dashboard/dashboard/protected/bin/yiic',
];
