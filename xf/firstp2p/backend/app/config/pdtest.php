<?php

$PROJECT_DIR = APP_MODULE_DIR;

$config = array(
    'cfp' => array(
        'adapter' => 'Mysql',
        'host' => 'm1-p2p.wxlc.org',
        'username' => 'cfp_user',
        'password' => 's1022xfjhowr',
        'dbname' => 'openapi_cfp',
        'port' => '3306',
        'type' => 'master'
    ),
    'firstp2p' => array(
        'adapter' => 'Mysql',
        'host' => 'm1-p2p.wxlc.org',
        'username' => 'wxlc_pro_test',
        'password' => 'AJyjAW2X2G',
        'dbname' => 'firstp2p',
        'port' => '3306',
        'type' => 'master'
    ),
    'firstp2p_r' => array(
        'adapter' => 'Mysql',
        'host' => 'r1-p2p.wxlc.org',
        'username' => 'wxlc_pro_test',
        'password' => 'AJyjAW2X2G',
        'dbname' => 'firstp2p',
        'port' => '3306',
        'type' => 'slave'
    ),
    'firstp2p_push' => array(
        'adapter' => 'Mysql',
        'host' => 'm1-mix.wxlc.org',
        'username' => 'wxlc_push_test',
        'password' => 'jNO9zAYBAXFv95kv',
        'dbname' => 'firstp2p_push',
        'port' => '3306',
        'type' => 'master'
    ),
    'firstp2p_push_r' => array(
        'adapter' => 'Mysql',
        'host' => 'm1-mix.wxlc.org',
        'username' => 'wxlc_push_test',
        'password' => 'jNO9zAYBAXFv95kv',
        'dbname' => 'firstp2p_push',
        'port' => '3306',
        'type' => 'master'
    ),
    'firstp2p_msg_box' => array(
        'adapter' => 'Mysql',
        'host' => 'm1-mix.wxlc.org',
        'username' => 'wxlc_msgbox_test',
        'password' => 'MSGP@ssW0rd',
        'dbname' => 'firstp2p_msg_box',
        'port' => '3306',
        'type' => 'master'
    ),
    'firstp2p_msg_box_r' => array(
        'adapter' => 'Mysql',
        'host' => 'm1-mix.wxlc.org',
        'username' => 'wxlc_msgbox_test',
        'password' => 'MSGP@ssW0rd',
        'dbname' => 'firstp2p_msg_box',
        'port' => '3306',
        'type' => 'slave'
    ),
    'redis' => array(
        'host' => 'se-redis1.wxlc.org',
        'port' => 6379
    ),

    'application' => array(
        "name"         => "backend",
        "namespace"    => "NCFGroup\Ptp\\",
        "mode"         => "Srv",

        'appId'       => 'backend',
        'metaDataDir' => $system . '/cache/metadata/',
        'modelsDir'   => $PROJECT_DIR . '/models/',
        'servicesDir' => $PROJECT_DIR . 'app/services/',
        'daosDir'     => $PROJECT_DIR . 'app/daos/',
        'enumsDir'    => $PROJECT_DIR . 'app/enums/',
        'publicDir'   => $PROJECT_DIR . 'public/',
        'baseUri'     => '/',
        'debug' => true,
        'logFilePath' => $system.'log/logger/firstp2p_backend.log',
        'ipRestriction' => false,
        'ipWhitelist' => array(
            "127.0.0.1",

            "172.21.12.82", // eth0
            "172.21.12.83",
            "172.21.12.84",

            "172.21.11.82", // eth1
            "172.21.11.83",
            "172.21.11.84",
        ),
        'clientRestriction' => true,
        'secretKey' => array(
            "firstp2p_web" => "a11c3aca89b97c5cbad31d7ce9348cb27ed90495",
            "firstp2p_api" => "be3s4rtestyu4rfwt4e2d7ce9348cb27ed904951"
        ),

        'cr4Key' => '714005f239ed1867', // cr4秘钥
    ),

    'mail' => array(
        'from' => 'firstp2p@ucfgroup.com',
        ),

    'logger' => array(
        'file' => array(
            'path' => $system.'log/logger/backend_'.date('Ymd').'.log',
        ),
        'remote' => array(
            'ip' => 'pmlog2.wxlc.org',
            'port' => '55002',
            'errorlog' => $system.'log/logger/backend_remotelog_error.log',
        ),
    ),
    'push' => array(
        //网信理财推送
        '1' => array(
            'ios' => array(
                'appId' => '5699684',
                'apiKey' => 'zMoc14qulm74SCeI4MXwgGkH',
                'secretKey' => 'ZnTe1GaWop9v9svfNwsSoyiLSkwL1QXU',
                'options' => array(
                    'msg_type' => 1,
                    'deploy_status' => 2, //1开发状态, 2生产状态
                ),
            ),
            'android' => array(
                'appId' => '5565781',
                'apiKey' => 'lr2tCd3T3RiAsvZaycTklGGS',
                'secretKey' => 'Cw7MOKxCMfen7AyU6aDocqMr93eoCGeV',
                'options' => array(
                    'msg_type' => 0,
                ),
            ),
        ),

    ),
);

return $config;
