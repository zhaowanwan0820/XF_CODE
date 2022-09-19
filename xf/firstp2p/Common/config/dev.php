<?php

return array(
    "application" => array(
        "debug"      => false,
        "close"      => false,
    ),
    'namespace' => array(
        'NCFGroup\Common'  => $system.'/Common/',
        'NCFGroup\Protos'  => $system.'/Protos/',
        'Phalcon'          => $system.'/Common/Phalcon/',
        'Assert'           => $system.'/Common/Vendor/assert/lib/Assert/',
        'Money'            => $system.'/Common/Vendor/money/lib/Money/',
        'League\Fractal'   => $system.'/Common/Vendor/fractal/src/',
        'GeneratedHydrator'   => $system.'/Common/Vendor/generatedHydrator/src/GeneratedHydrator/',
    ),
    //idworker发号器配置
    'idworker' => array(
        'serverlist' => array(
            'test01.firstp2plocal.com:8180',
            'test02.firstp2plocal.com:8180',
            'test03.firstp2plocal.com:8180',
        )
    ),
    //gtm使用的Redis
    'redis_gtm' => array(
        'master' => 'def_master',
        'sentinels' => array(
            array('host' => '127.0.0.1', 'port' => '26479'),
            array('host' => '127.0.0.1', 'port' => '26579'),
            array('host' => '127.0.0.1', 'port' => '26679'),
        )
    ),
    //gtm使用的db
    'db_gtm' => array(
        'adapter' => 'Mysql',
        'host' => '127.0.0.1',
        'username' => 'firstp2p',
        'password' => '1234@abcd',
        'dbname' => 'firstp2p_gtm',
        'port' => '3306',
    ),
    //msgbus
    'msgbus' => array(
        'zookeepers' => array(
            'test01.firstp2plocal.com:2181',
        ),
    ),
    'sms' => array(
        'url' => 'http://10.20.69.57:10000',
        'app_secret' => 'firstp2p',
    ),
    // 火眼反作弊系统
    'huoyan' => array(
        'url' => array(
            'http://10.20.69.191:13000',
        ),
    ),
    //依图活体检测
    'yitu' => array(
        'host' => 'http://10.20.69.107:9500',
    ),
    //状态推送
    'status' => array(
        'frontend' => array(
            'http://test01.firstp2plocal.com:11000',
        ),
        'backend' => array(
            'http://test01.firstp2plocal.com:11001',
            'http://test02.firstp2plocal.com:11001',
        ),
    ),
    // 告警
    'alarm' => array(
        'host' => 'http://test03.itil.firstp2plocal.com/api/alarm/push',
    ),
    // 资金系统统一配置
    'api' => array(
        'rpc' => array(
            'enableGateway' => false,
            'gateway' => 'http://yanbingrong.gateway.firstp2plocal.com',
            'signatureKey' => 'fdaj32eqFE#afda#$#87',
            'ncfph' => array('url'=>'http://yanbingrong.task.ncfphlocal.com/api'),
            'ncfwx' => array('url'=>'http://yanbingrong.p2pbackend.firstp2plocal.com/api'),
            'speedloan' => array('url'=>'http://yanbingrong.creditloanbackend.firstp2plocal.com/api'),
            'user' => array('url'=>'http://yanbingrong.userbackend.firstp2plocal.com/api'),
            'o2o' => array('url'=>'http://yanbingrong.o2obackend.firstp2plocal.com/api'),
            'openback'=> array('url'=>'http://test07.openbackend.firstp2plocal.com/api'),
            'duotou' => array('url'=>'http://test29.duotoubackend.firstp2plocal.com/api'),
            'finance' => array('url'=>'http://test02.financebackend.firstp2plocal.com/api'),
            'gold' => array('url'=>'http://test03.goldbackend.firstp2plocal.com/api'),
            'medal' => array('url'=>'http://test03.backend.medal.firstp2plocal.com/api'),
            'contract' => array('url'=>'http://test03.contractbackend.firstp2plocal.com/api'),
            'marketing' => array('url'=>'http://test04.marketingbackend.firstp2plocal.com/api'),
            'bonus' => array('url'=>'http://test18.bonusbackend.firstp2plocal.com/api'),
            'life' => array('url'=>'http://test49.lifedev.ncfgroup.com/api'),
        ),
    ),
    'trace' => array(
        'enableTrace' => true,
        'logServer' => '',
        'logLevel' => 2, //1(debug), 2(trace), 3(notice), 4(info), 5(error), 6(emegency), 7(exception), 8(xhprof), 9(performance)
    ),
    // 注册服务
    'registry' => array(
        'etcd' => array(
            'hosts' => array('http://10.20.69.15:2379'),
            'username' => 'root',
            'password' => 'root',
        ),
    ),
);
