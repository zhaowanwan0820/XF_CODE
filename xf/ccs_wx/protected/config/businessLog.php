<?php
/**
 * mongodb
 */
return [
    'class' => 'EMongoClient',
    'db' => ConfUtil::get('Mon-businessLog.db'),              //default db, not null
    'server' => [
        'user' => ConfUtil::get('Mon-businessLog.user'),          //mongo server user name
        'pass' => ConfUtil::get('Mon-businessLog.pwd'),        //server user password   //
        'servers'     => ConfUtil::get('Mon-cluster.server-arr', true),
    ],
    'options' => [
        'replicaSet' => 'rs0',
    ],
    'RP' => ['RP_PRIMARY_PREFERRED', []],
];

