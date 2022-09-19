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
        'password' => '28EFpFE8FCs0E367',
        'sentinels' => array(
            array(
                'host' => 'redis-p2pgtm.ncfgroup.org',
                'port' => '36380',
            ),
        )
    ),
    //gtm使用的db
    'db_gtm' => array(
        'adapter' => 'Mysql',
        'host' => 'w-p2pgtm.dbs.wxlc.org',
        'username' => 'p2pgtm_pro',
        'password' => '8D9F8Ft8AAD0s40EkF',
        'dbname' => 'firstp2p_gtm',
        'port' => '3308',
    ),
    'sms' => array(
        'url' => 'http://wx-sms.corp.ncfgroup.com',
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
        'host' => 'http://itil.firstp2p.com/api/alarm/push',
    ),
    // 资金系统统一配置
    'api' => array(
        'rpc' => array(
            'enableGateway' => false,
            'gateway' => 'http://gateway.firstp2p.com',
            'signatureKey' => 'fdaj34676FE#afda#$#87',
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
            'contract' => array('url'=>'http://contract.backend.firstp2p.com/api'),
            'marketing' => array('url'=>'http://service.marketing.firstp2p.com/api'),
            'bonus' => array('url'=>'http://service.bonus.firstp2p.com/api'),
            'life' => array('url'=>'http://life.backend.firstp2p.com/api'),
        ),
    ),
    'msgbus' => array(
        'zookeepers' => array(
            'zookeeper1.wxlc.org:2181',
            'zookeeper2.wxlc.org:2181',
            'zookeeper3.wxlc.org:2181',
        ),
    ),
    'trace' => array(
        'enableTrace' => false,
        'logLevel' => 4, //1(debug), 2(trace), 3(notice), 4(info), 5(error), 6(emegency), 7(exception), 8(xhprof), 9(performance)
    ),
    //火眼反作弊系统
    'huoyan' => array(
        'url' => array(
            'http://172.21.12.241:13000',
            'http://172.21.12.242:13000',
        ),
    ),
    // 注册服务
    'registry' => array(
        'etcd' => array(
            'hosts' => array('172.21.12.184:2379', '172.21.12.185:2379', '172.21.12.186:2379'),
            'username' => 'root',
            'password' => 'vcnio4r902v',
        ),
    ),
);
