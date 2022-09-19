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
            '172.21.12.95:8180',
            '172.21.12.96:8180',
        )
    ),
    //gtm使用的Redis
    'redis_gtm' => array(
        'master' => 'mymaster',
        'sentinels' => array(
            array(
                'host' => 'st-redis1.wxlc.org',
                'port' => '26479',
            ),
            array(
                'host' => 'st-redis2.wxlc.org',
                'port' => '26479',
            ),
            array(
                'host' => 'st-redis3.wxlc.org',
                'port' => '26479',
            ),
        )
    ),
    //gtm使用的db
    'db_gtm' => array(
        'adapter' => 'Mysql',
        'host' => 'm1-mix.wxlc.org',
        'username' => 'p2pgtm_test',
        'password' => 'FC0E3677sABF4',
        'dbname' => 'firstp2p_gtm',
        'port' => '3306',
    ),

    'sms' => array(
        'url' => 'http://127.0.0.1:10000',
        'app_secret' => '2a1ecb37e4cf',
    ),
    //依图活体检测
    'yitu' => array(
        'host' => 'http://face.wxlc.org',
    ),
    //状态推送
    'status' => array(
        'frontend' => array(
            'https://status.ncfwx.com',
        ),
        'backend' => array(
            'http://172.21.12.237:11001',
            'http://172.21.12.238:11001',
        ),
    ),
    // 告警
    'alarm' => array(
        'host' => 'http://itiltest.firstp2p.com/api/alarm/push',
    ),
    // 资金系统统一配置
    'api' => array(
        'rpc' => array(
            'enableGateway' => false,
            'gateway' => 'http://gateway.firstp2p.com',
            'signatureKey' => 'fdaj3fda4DDE#afda#$#87',
            'ncfph' => array('url'=>'http://task.firstp2p.cn/api'),
            'ncfwx' => array('url'=>'http://p2pbackend.firstp2p.com/api'),
            'speedloan' => array('url'=>'http://creditloan.backend.wangxinlicai.com/api'),
            'user' => array('url'=>'http://service.user.firstp2p.com/api'),
            'o2o' => array('url'=>'http://service.o2o.firstp2p.com/api'),
            'openback'=> array('url'=>'http://service.open.firstp2p.com/api'),
            'duotou' => array('url'=>'http://dt.backend.firstp2p.com/api'),
            'finance' => array('url'=>'http://finance.backend.firstp2p.com/api'),
            'gold' => array('url'=>'http://gold.backend.firstp2p.com/api'),
            'medal' => array('url'=>'http://backend.medal.firstp2p.com/api'),
            'contract' => array('url'=>'http://contract.firstp2p.com/api'),
            'marketing' => array('url'=>'http://service.marketing.firstp2p.com/api'),
            'bonus' => array('url'=>'http://service.bonus.firstp2p.com/api'),
            'life' => array('url'=>'http://life.backend.firstp2p.com/api'),
        ),
    ),
    'msgbus' => array(
        'zookeepers' => array(
            'zookeeper1.wxlc.org:2181',
        ),
    ),
    'trace' => array(
        'enableTrace' => true,
        'logLevel' => 2, //1(debug), 2(trace), 3(notice), 4(info), 5(error), 6(emegency), 7(exception), 8(xhprof), 9(performance)
    ),
    //火眼反作弊系统
    'huoyan' => array(
        'url' => array(
            'http://127.0.0.1:13000',
        ),
    ),
    // 注册服务
    'registry' => array(
        'etcd' => array(
            'hosts' => array('172.31.33.6:2379', '172.31.33.18:2379', '172.31.33.26:2379'),
            'username' => 'root',
            'password' => 'O8fKx9MM9IYT',
        ),
    ),
);
