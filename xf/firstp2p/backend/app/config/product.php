<?php

$PROJECT_DIR = APP_MODULE_DIR;

$config = array(
    'cfp' => array(
        'adapter' => 'Mysql',
        'host' => 'm1-p2p.wxlc.org', // 主库172.31.3.100 从库172.31.3.200
        'username' => 'cfp_user',
        'password' => 's1022xfjhowr',
        'dbname' => 'openapi_cfp',
        'port' => '3306',
        'type' => 'master'
    ),
    'firstp2p' => array(
        'adapter' => 'Mysql',
        'host' => 'w-p2p.wxlc.org',
        'username' => 'wxlc_pro',
        'password' => 'lzBbkfLiwEQZU8WXWww7',
        'dbname' => 'firstp2p',
        'port' => '3322',
        'type' => 'master'
    ),
    'firstp2p_r' => array(
        'adapter' => 'Mysql',
        'host' => 'r1-p2p.wxlc.org',
        'username' => 'wxlc_pro_r',
        'password' => 'Y7pFCXuM3xF1pxnVLzXk',
        'dbname' => 'firstp2p',
        'port' => '3333',
        'type' => 'slave'
    ),
    'firstp2p_push' => array(
        'adapter' => 'Mysql',
        'host' => 'w-p2ppush.dbs.wxlc.org',
        'username' => 'firstp2p_push_u',
        'password' => 'CQ9xecagUuGi2p7CFkKH',
        'dbname' => 'firstp2p_push',
        'port' => '3306',
        'type' => 'master'
    ),
    'firstp2p_push_r' => array(
        'adapter' => 'Mysql',
        'host' => 'w-p2ppush.dbs.wxlc.org',
        'username' => 'firstp2p_push_u',
        'password' => 'CQ9xecagUuGi2p7CFkKH',
        'dbname' => 'firstp2p_push',
        'port' => '3306',
        'type' => 'master'
    ),
    'firstp2p_msg_box' => array(
        'adapter' => 'Mysql',
        'host' => 'w-msgbox.dbs.wxlc.org',
        'username' => 'p2p_msgbox_pro',
        'password' => '8VEXqmZ4aEPkg3uo',
        'dbname' => 'firstp2p_msg_box',
        'port' => '3306',
        'type' => 'master'
    ),
    'firstp2p_msg_box_r' => array(
        'adapter' => 'Mysql',
        'host' => 'r-msgbox.dbs.wxlc.org',
        'username' => 'p2p_msgbox_r',
        'password' => 'J#MVfx|BioS?dt#t',
        'dbname' => 'firstp2p_msg_box',
        'port' => '3306',
        'type' => 'slave'
    ),
    'redis' => array(
        'host' => 'se-redis1.wxlc.org',
        'port' => 6379
    ),
    'application' => array(
        "name" => "backend",
        "namespace" => "NCFGroup\Ptp\\",
        "mode" => "Srv",
        'appId' => 'backend',
        'metaDataDir' => $system . '/cache/metadata/',
        'modelsDir' => $PROJECT_DIR . '/models/',
        'servicesDir' => $PROJECT_DIR . 'app/services/',
        'daosDir' => $PROJECT_DIR . 'app/daos/',
        'enumsDir' => $PROJECT_DIR . 'app/enums/',
        'publicDir' => $PROJECT_DIR . 'public/',
        'baseUri' => '/',
        'debug' => true,
        'logFilePath' => $system.'log/logger/firstp2p_backend.log',
        'ipRestriction' => false,
        'ipWhitelist' => array(
            "127.0.0.1",
            "172.31.33.37",
            "172.31.33.32",

            "172.21.12.82", // eth0
            "172.21.12.83",
            "172.21.12.84",

            "172.21.12.89",//基金
            "172.21.12.15",
            "172.21.12.16",
            "172.21.12.17",

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
            'path' => $system . 'log/logger/backend_' . date('Ymd') . '.log',
        ),
        'remote' => array(
            'ip' => 'pmlog2.wxlc.org',
            'port' => '55002',
            'errorlog' => $system . 'log/logger/backend_remotelog_error.log',
        ),
    ),
    'push' => array(
        //网信理财推送
        '1' => array(
            'ios' => array(
                'appId' => '2351076',
                'apiKey' => 'fCvfzULFyij7a4BYni9PPGkd',
                'secretKey' => 'CdAYwqQr1Z3MbvmoZmRuSsQrqBkqIA1d',
                'options' => array(
                    'msg_type' => 1,
                    'deploy_status' => 2, //1开发状态, 2生产状态
                ),
            ),
            'android' => array(
                'appId' => '2351076',
                'apiKey' => 'fCvfzULFyij7a4BYni9PPGkd',
                'secretKey' => 'CdAYwqQr1Z3MbvmoZmRuSsQrqBkqIA1d',
                'options' => array(
                    'msg_type' => 0,
                ),
            ),
        ),
        //理财师推送
        '2' => array(
            'ios' => array(
                'appId' => '6823603',
                'apiKey' => 'GZLuthuwCcoyGVXUcmsKi9ma',
                'secretKey' => 'GfUBo7QmeELXkv5N9qi2cODrCkuO3QWH',
                'options' => array(
                    'msg_type' => 1,
                    'deploy_status' => 2, //1开发状态, 2生产状态
                ),
            ),
            'android' => array(
                'appId' => '6823606',
                'apiKey' => 'yp17B2VaP5g1hfOff6LiGAZb',
                'secretKey' => 'nppHWLxnexjqW4Zt4eheiR3ujsBXb8ig',
                'options' => array(
                    'msg_type' => 0,
                ),
            ),
        ),
    ),

);

return $config;
