<?php

return array(
    'components' => array(
        //路由

        'route' => array(
            'class' => 'libs\route\Route',
            'rules' => array(
                //例如：http://www.firstp2p.com/a/1
                '/^\/a\/(?<atc_id>\d+)\/?$/i' => APP . '\controllers\article\Show',

                //特殊添加的 针对 rss
                //例如：http://www.firstp2p.com/rss/12  http://www.firstp2p.com/rss?cate=12 或者 cat=12 等于  特殊处理 rss
                //例如：http://www.firstp2p.com/rss/hl  http://www.firstp2p.com/rss?c=hl 等于  特殊处理 rss
                //'/^\/rss[\/](?<cate>\d+)\/?$/' => APP.'\controllers\Rss\Index',
                '/^\/rss[\/](?<c>(?!deal)\w{1,3})\/?$/' => APP . '\controllers\Rss\Index',
                //例如：http://www.firstp2p.com/guide/introduction
                '/^\/guide[\/](?<action>\w+)$/' => APP . '\controllers\guide\Index',
                // 考虑分站逻辑旧首页还是访问index
                '/^\/product\/?$/' => APP . '\controllers\index\Index',
                //例如：http://www.firstp2p.com/deal/id-1
                '/^\/(?<_c>\w+)[\/]id-(?<id>\d+)\/?$/' => APP . '\controllers\<_c>\Index',
                '/^\/(?<_c>\w+)[\/]id-(?<id>[0-9a-z]+)\/?$/' => APP . '\controllers\<_c>\Index',
                //例如：http://www.firstp2p.com/deal/1
                '/^\/(?<_c>\w+)[\/](?<id>\d+)\/?$/' => APP . '\controllers\<_c>\Index',
                //例如：http://www.firstp2p.com/deal/bid/1
                '/^\/(?<_c>\w+)[\/](?<_a>\w+)[\/](?<id>\w+)\/?$/' => APP . '\controllers\<_c>\<_a>',
                //例如：http://www.firstp2p.com/deal/index 或者 http://www.firstp2p.com/deal-index hacked by qunqiang @2014-04-16 11:04:45
                '/^\/(?<_c>\w+)[\/-](?<_a>\w+)\/?$/' => APP . '\controllers\<_c>\<_a>',
                //例如：http://www.firstp2p.com/deal
                '/^\/(?<_c>\w+)\/?$/' => APP . '\controllers\<_c>\Index',
                //例如：http://www.firstp2p.com
                '/^\/$/' => APP . '\controllers\index\Index',
            ),
            'rules_ec' => array(//标的Id加密添加路由规则
                '/^\/d[\/](?<id>\w+)\/?$/' => APP.'\controllers\deal\Index',
            ),
        ),
        'asset' => array(
            'class' => 'libs\helpers\Asset',
            'config_file' => APP_ROOT_PATH . 'package.json',
        ),
        //缓存
        'cache' => array(
            'class' => 'libs\caching\RedisCache',
            'hostname' => 'se-redis1.wxlc.org',
            'port' => '6379',
            'database' => 1,
        ),
        // 队列服务
        'thunder' => array(
            'class' => 'libs\queue\ThunderQueue',
            'queueType' => 'Redis',
            'server' => array(
                'hostname'  => 'se-redis1.wxlc.org',
                'port'      => '6379',
                'database'  => '15',
            ),
        ),

        //redis扩展缓存
        'dataCacheBak' => array(
            'class'    => 'libs\caching\RedisDataCache',
            'hostname' => '127.0.0.1',
            'port'     => '6379',
            'database' => 1,
        ),
         //redis（哨兵）缓存
        'dataCache' => array(
            'class'    => 'libs\caching\RedisDataCache',
            'database' => 1,
            'masterName' => 'mymaster',
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
            ),
        ),

        'tagRedisCluster' => array(
            'auth' => true,
            'password' => '4EAFC201B6283B',
            'nodes' => array(
                'tcp://p2ptag.redis.ncfrds.com:6381?timeout=1&persistent=1',
            )
        ),

        // aerospike 存储
        'aerospike' => array(
            'class' => 'libs\aerospike\AerospikeClient',
            'hosts' => array(
                array('addr'=>'as1.wxlc.org','port'=>3000),
                array('addr'=>'as2.wxlc.org','port'=>3000),
                array('addr'=>'as3.wxlc.org','port'=>3000),
            ),
            'namespace' => "contract",
            'set' => "data",
        ),

        //短信队列
        'sms_queue' => array(
            'class' => 'libs\queue\RedisQueue',
            'hostname' => 'se-redis1.wxlc.org',
            'port' => '6379',
            'database' => 0,
            'channel' => 'sms_queue',
        ),
        //短信调用接口，可不使用队列直接发送
        'sms' => array(
            'class' => 'app\Components\SmsProxy'
        ),
        // 邮件队列
        'prior_queue' => array(
            'class' => 'libs\queue\PriorQueue',
            'hostname' => 'se-redis1.wxlc.org',
            'port' => '6379',
            'database' => 0,
            'channel' => 'email_queue',
        ),

        // 多 app remote rpc support
        'rpc' => array(
            'fund' => array(
                'mockRpc' => false,
                'logFilePath' => '/tmp/rpc_api.log',
                'staticUri' => '/',
                'rpcServerUri' => 'http://oldservice.xfuser.com/',
                'rpcClientId' => 'firstp2p_api',
                'rpcSecretKey' => 'be3s4rtestyu4rfwt4e2d7ce9348cb27ed904951',
                'host' => 'http://oldservice.xfuser.com/',
            ),
            'openapi' => array(
                'mockRpc' => true,
                'logFilePath' => '/tmp/rpc_openapi.log',
                'staticUri' => '/',
                'rpcServerUri' => 'backend',
                'rpcClientId' => '',
                'rpcSecretKey' => '',
                'host' => 'http://openapi.firstp2p.com/',
            ),
            'o2o' => array(
                'mockRpc' => false,
                'logFilePath' => '/tmp/rpc_api.log',
                'staticUri' => '/',
                'rpcServerUri' => 'http://service.o2o.firstp2p.com/',
                'rpcClientId' => 'firstp2p_api',
                'rpcSecretKey' => 'be3s4rtestyu4rfwt4e2d7ce9348cb27ed904951',
                'host' => 'http://service.o2o.firstp2p.com/',
            ),
            'openback' => array(
                'mockRpc' => false,
                'logFilePath' => '/tmp/rpc_openback.log',
                'staticUri' => '/',
                'rpcServerUri' => 'http://service.open.firstp2p.com/',
                'rpcClientId' => 'open_backend',
                'rpcSecretKey' => 'be3s4rtestyu4rfwt4e2d7ce9348cb27ed904951',
                'host' => 'https://service.open.firstp2p.com/',
            ),

            'push' => array(
                'mockRpc' => true,
                'logFilePath' => '/tmp/rpc_push.log',
                'staticUri' => '/',
                'rpcServerUri' => 'backend',
                'rpcClientId' => '',
                'rpcSecretKey' => '',
                'host' => 'http://www.firstp2p.com/',
            ),
            'commonservice' => array(
                'mockRpc' => false,
                'logFilePath' => '/tmp/rpc_commonservice.log',
                'staticUri' => '/',
                'rpcServerUri' => 'http://172.31.33.16:8100/',
                'rpcClientId' => '',
                'rpcSecretKey' => '',
                'host' => '',
            ),
            'duotou' => array(
                'mockRpc' => false,
                'logFilePath' => '/tmp/rpc_duotou.log',
                'staticUri' => '/',
                'rpcServerUri' => 'http://dt.backend.firstp2p.com/',
                'rpcClientId' => '',
                'rpcSecretKey' => '',
                'host' => 'http://dt.backend.firstp2p.com',
            ),
            'finance' => array(
                'mockRpc' => false,
                'logFilePath' => '/tmp/rpc_finance.log',
                'staticUri' => '/',
                'rpcServerUri' => 'http://finance.backend.firstp2p.com/',
                'rpcClientId' => '',
                'rpcSecretKey' => '',
                'host' => 'http://finance.backend.firstp2p.com',
            ),
            'gold' => array(
                'mockRpc' => false,
                'logFilePath' => '/tmp/rpc_gold.log',
                'staticUri' => '/',
                'rpcServerUri' => 'http://gold.backend.firstp2p.com/',
                'rpcClientId' => '',
                'rpcSecretKey' => '',
                'host' => 'http://gold.backend.firstp2p.com',
            ),
            'creditloan' => array(
                'mockRpc' => false,
                'logFilePath' => '/tmp/rpc_creditloan.log',
                'staticUri' => '/',
                'rpcServerUri' => 'http://creditloan.backend.wangxinlicai.com/',
                'rpcClientId' => '',
                'rpcSecretKey' => '',
                'host' => 'http://creditloan.backend.wangxinlicai.com',
            ),
            'medal' => array(
                'mockRpc' => false,
                'logFilePath' => '/tmp/rpc_api.log',
                'staticUri' => '/',
                'rpcServerUri' => 'http://backend.medal.firstp2p.com/',
                'rpcClientId' => '',
                'rpcSecretKey' => '',
                'host' => 'https://backend.medal.firstp2p.com',
            ),
            'contract' => array(
                'mockRpc' => false,
                'logFilePath' => '/tmp/rpc_api.log',
                'staticUri' => '/',
                'rpcServerUri' => 'http://oldservice.xfuser.com/',
                'rpcClientId' => '',
                'rpcSecretKey' => '',
                'host' => 'http://oldservice.xfuser.com',
            ),
            'marketing' => array(
                'mockRpc' => false,
                'logFilePath' => '/tmp/rpc_api.log',
                'staticUri' => '/',
                'rpcServerUri' => 'http://service.marketing.firstp2p.com/',
                'rpcClientId' => 'firstp2p_api',
                'rpcSecretKey' => 'be3s4rtestyu4rfwt4e2d7ce9348cb27ed904951',
                'host' => 'http://service.marketing.firstp2p.com/',
            ),
            'bonus' => array(
                'mockRpc' => false,
                'logFilePath' => '/tmp/rpc_api.log',
                'staticUri' => '/',
                'rpcServerUri' => 'http://service.bonus.firstp2p.com/',
                'rpcClientId' => 'firstp2p_api',
                'rpcSecretKey' => 'be3s4rtestyu4rfwt4e2d7ce9348cb27ed904951',
                'host' => 'http://service.bonus.firstp2p.com/',
            ),
            'life' => array(
                'mockRpc' => false,
                'logFilePath' => '/tmp/rpc_life.log',
                'staticUri' => '/',
                'rpcServerUri' => 'http://life.backend.firstp2p.com/',
                'rpcClientId' => '',
                'rpcSecretKey' => '',
                'host' => 'http://life.backend.firstp2p.com',
            ),
        ),
    ),

    'jifubao' => array(
        'ftp' => array(
            'ftp_host'=>'ftp-out.wxlc.org',
            'ftp_username'=>'wjifubao',
            'ftp_password'=>'w%GeGVER34rF#Verf',
        ),
        'ftp_dir' => '/jifubao/',
    ),

    'mongo' => array(
        // 短信数据
        'sms' => array(
            'connection' => array(
                'hostnames' => 'mongo1.wxlc.org:27001,mongo2.wxlc.org:27001,mongo3.wxlc.org:27001',
                'database' => 'sms',
                'username' => 'firstp2p',
                'password' => 'H9hYkd=nZ5aZ',
                'options' => array(
                    'replicaSet' => 'repset',
                ),
            )
        ),

        // 邮件数据
        'email' => array(
            'connection' => array(
                'hostnames' => 'mongo1.wxlc.org:27001,mongo2.wxlc.org:27001,mongo3.wxlc.org:27001',
                'database' => 'email',
                'username' => 'firstp2p',
                'password' => 'H9hYkd=nZ5aZ',
                'options' => array(
                    'replicaSet' => 'repset',
                ),
            )
        ),

        // 标的信息
        'deal' => array(
            'connection' => array(
                'hostnames' => 'mongo1.wxlc.org:27001,mongo2.wxlc.org:27001,mongo3.wxlc.org:27001',
                'database' => 'deal',
                'username' => 'firstp2p',
                'password' => 'H9hYkd=nZ5aZ',
                'options' => array(
                    'replicaSet' => 'repset',
                ),
            ),
       ),
    ),
    'remotetag' => array(
        'host' => 'http://tags.wxlc.org/',
    ),
    'jrgcConfig' => array(
        //金融工场查询身份注册信息接口的配置
        'salt' => 'jrgc67g68',
        'aesKey' => 'UITN23LMUQC810RW',
        'url' => 'http://report.9888.cn/eten/rs.do?message',
    ),
);
