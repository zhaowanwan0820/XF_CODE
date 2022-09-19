<?php
return [
    //protected根目录
    'basePath' => APP_DIR . '/protected/',
    //应用名
    'name' => 'itouzi',
    'aliases' => [
        'iauth' => APP_DIR . '/protected/modules/iAuth',
    ],
    //运行时目录，主要用于模板的编译
    'runtimePath' => ConfUtil::get('runtimePath'),
    'import' => [
        'application.lib.models.*',  //orm model
        'application.lib.models.bbs.*',  //bbs model
        'application.lib.models.seoModels.*',
        'application.lib.services.*',  //service layer
        'application.lib.classes.*',  //other lib class
        'application.lib.util.*',
        'itzlib.plugins.mongo.*',
        'itzlib.plugins.mongo.validators.*',
        'itzlib.plugins.mongo.behaviors.*',
        'itzlib.plugins.mongo.util.*',
        'itzlib.plugins.rundeck.*',
        'itzlib.plugins.OSS.*',
        'itzlib.itzService.*',    //itzlib itzService
    ],
    //模块配置，各部分的默认值为：module=default,controller=index,action=index
    'modules' => [
    ],
    //组件配置
    'components' => [
        //URLRewrite组件，根据需要进行配置
        'urlManager' => [
            'urlFormat' => 'path',
            'showScriptName' => false,
            'rules' => [
                //首页
                '' => 'default/index/index',
                '<module:\w+>/<controller:\w+>/<action:\w+>' => '<module>/<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => 'default/<controller>/<action>',
                '<action:\w+>' => 'default/index/<action>'
            ],
            'baseUrl' => 'https://www.itouzi.com'
        ],
        //正常的log组件
        'log' => [
            'class' => 'CLogRouter',
            'routes' => [
                [
                    'class' => 'CFileLogRoute',
                    'levels' => 'info,trace,debug',
                    //支持日期格式,{}中的为日期格式
                    'logPath' => ConfUtil::get('logPath'),
                    'logFile' => 'yii_cron_ccs_access.log',
                    'maxFileSize' => 2097152
                ],
                [
                    'class' => 'CFileLogRoute',
                    'levels' => 'error,warning',
                    //支持日期格式,{}中的为日期格式
                    'logPath' => ConfUtil::get('logPath'),
                    'logFile' => 'yii_cron_ccs_error.log',
                    'maxFileSize' => 2097152
                ],
            ]
        ],
        'c' => [
            'class' => 'ItzConfig',
            'opmpUrl' => \itzlib\sdk\ServiceUtil::getAddress('com.itouzi.opmp').'/opmp/v1/web',
            'ccsUrl' => \itzlib\sdk\ServiceUtil::getAddress('com.itouzi.opmp').'/opmp/v1/ccs',
            'baseUrl' => 'https://www.itouzi.com'
        ],
        //权限库
        'db' => require __DIR__ . '/db.php',
        //普惠
        'phdb' => require __DIR__ . '/phdb.php',
        //firstp2p库
        'fdb' => require __DIR__ . '/fdb.php',
        //合同库contract_dev
        'contractdb' => require __DIR__ . "/contractdb.php",
        //普惠
        'phdb' => require __DIR__ . "/phdb.php",
        //金融工场
        'yjdb' => require __DIR__ . "/yjdb.php",
        //风控库
        'cdb' => require __DIR__ . "/cdb.php",
        //dw_库
        'dwdb' => require __DIR__ . "/dwdb.php",
        //dw_库
        'cps' => require __DIR__ . "/cps.php",
        //bbs_库
        'bbs' => require __DIR__ . "/bbs.php",
        //bbs_库
        'rbbs' => require __DIR__ . "/rbbs.php",
        //crm库
        'crmdb'=> include __DIR__ . "/crmdb.php",
        //ccs库
        'ccsdb'=> include __DIR__ . "/ccsdb.php",
        //seo库
        'seodb'=> include __DIR__ . "/seo.php",
        //data库
        'datadb' => require __DIR__ . "/datadb.php",
        'dcache' => require __DIR__ . '/dcache.php',
        'rcache' => include __DIR__ . '/rcache.php',
        // Redis 11DB
        'rcache3' => include __DIR__ . '/rcache3.php',

        'businessLog' => include __DIR__ . '/businessLog.php',
        //市场库
        'market' => include __DIR__ . '/market.php',
        // Rundeck
        'rundeck' => include __DIR__ . '/rundeck.php',

        'oss' => include __DIR__ . '/oss.php',
        //mongo_库
        'mongodb' => include __DIR__ . '/mongodb.php',
        'async' => require __DIR__ . '/async.php',

    ],
    //预先加载log组建
    'preload' => [
        'log',
    ]
];
