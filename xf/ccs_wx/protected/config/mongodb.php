<?php
/**
 * mongodb
 */
return array(
	'class'  =>'EMongoClient',
	'db'     => ConfUtil::get('Mon-mongodb-dashboard.db'),              //default db, not null
	'server' => array(
		'user'        => ConfUtil::get('Mon-mongodb-dashboard.user'),          //mongo server user name
		'pass'        => ConfUtil::get('Mon-mongodb-dashboard.pwd'),        //server user password   //
        'servers'     => ConfUtil::get('Mon-cluster.server-arr', true),
    ),
    'options' => array(
		'replicaSet' =>'rs0',
	),
	'RP' => array('RP_PRIMARY_PREFERRED',array()),
);
                                       
