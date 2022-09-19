<?php
/**
 * dcache缓存
 */
return [
    'class'=>'CMem2RedisCache',
    'keyPrefix'=>'frommemcache_',
	'clearOldExpireKey'=>false,//清理之前过期时间未设定的key
//    'servers' => [
//        [
//            'host' => ConfUtil::get('Mem-1.host'),
//            'port' => ConfUtil::get('Mem-1.port'),
//            'persistent' => true,
//            'timeout' => 1,
//        ],
//        [
//            'host' => ConfUtil::get('Mem-2.host'),
//            'port' => ConfUtil::get('Mem-2.port'),
//            'persistent' => true,
//            'timeout' => 1,
//        ],
//    ],
];