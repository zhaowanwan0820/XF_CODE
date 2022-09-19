<?php
return array(
    "rongpay_email" => "guyun@xxx.com",
    'server' => array(
        'AuditLogOn'  => true,                  //日志开关
        'logToLocal'  => false,                 //切换到写本地
        'collection'  => 'audit_log',           //default collection, not null
        'db'          => 'audit',               //default db, not null
        'replication' => 'rs0',                 //
        'user'        => 'audit',               //mongo server user name
        'pass'        => '*_06&ITOUZI*_06&',    //server user password
        'servers'     => array(                 //servers
            '10.175.200.154:27018',
            '10.168.35.15:27018',
            '10.80.60.134:27018'
        )
    ),
);
