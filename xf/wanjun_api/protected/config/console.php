<?php
return [
    //protected根目录
    'basePath' => APP_DIR . '/protected/',
    //应用名
    'name' => 'wx',
    //运行时目录，主要用于模板的编译
    'runtimePath' => ConfUtil::get('runtimePath'),
    'import' => [
        'application.lib.models.*',  //orm model
        'application.lib.services.*',  //service layer
        'application.lib.classes.*',  //other lib class
        'application.lib.util.*',     //other lib class
        'itzlib.classes.*',     //itzlib class
        'itzlib.api.*',     //itzlib class
        'itzlib.itzService.*',    //itzlib itzService
        //Yii mongo extensions
        'itzlib.plugins.mongo.*',
        'itzlib.plugins.mongo.validators.*',
        'itzlib.plugins.mongo.behaviors.*',
        'itzlib.plugins.mongo.util.*',
        'itzlib.plugins.OSS.*',
        'itzlib.plugins.yop.lib.*',

    ],
    //模块配置，各部分的默认值为：module=default,controller=index,action=index
    'modules' => [
        'default',  //这是默认加载的模块
    ],
    //组件配置
    'components' => [
        'viewRenderer' => [
            //支持smarty引擎的插件
            'class' => 'ItzSmartyView',
            //模板后缀名
            'fileExtension' => '.tpl',
            //这里可用来配置全局变量，例如下面的配置，我们在模板种可以直接用<{CONST.cssRoot}>来读取
            'globalVal' => [
                'viewPath' => APP_DIR . '/views/',
                'jsPath' => '',
                'jsVersion' => time(),
                'cssVersion' => time(),
                'developMode' => false,
                //是否开发模式
            ],
            //这里为Smarty支持的属性
            'config' => [
                'left_delimiter' => "<{",
                'right_delimiter' => "}>",
                'template_dir' => APP_DIR . "/views/",
                'debugging' => false,
            ]
        ],
        //URLRewrite组件，根据需要进行配置
        'urlManager' => [
            'urlFormat' => 'path',
            'showScriptName' => false,
            'rules' => [
                //首页
                '' => 'itzdefault/index/index',
                '<module:\w+>/<controller:\w+>/<action:\w+>' => '<module>/<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => 'default/<controller>/<action>',
                '<action:\w+>' => 'default/index/<action>'
            ],
        ],
        //正常的log组件
        'log' => [
            'class' => 'CLogRouter',
            'routes' => [
                [
                    'class' => 'CFileLogRoute',
                    'levels' => 'info',
                    //支持日期格式,{}中的为日期格式
                    'logPath' => ConfUtil::get('logPath-itouzi'),
                    'logFile' => 'yii_wx_console_access.log',
                    'maxFileSize' => 2097152
                ],
                [
                    'class' => 'CFileLogRoute',
                    'levels' => 'error,warning',
                    //支持日期格式,{}中的为日期格式
                    'logPath' => ConfUtil::get('logPath-itouzi'),
                    'logFile' => 'yii_wx_console_error.log',
                    'maxFileSize' => 2097152
                ],
                [
                    'class' => 'CFileLogRoute',
                    'levels' => 'repayment.error',
                    //支持日期格式,{}中的为日期格式
                    'logPath' => ConfUtil::get('logPath-itouzi'),
                    'logFile' => 'advance_repayment_alarm_content.log',
                    'maxFileSize' => 2097152
                ],
            ]
        ],
        'c' => [
            'class' => 'ItzConfig',
            'baseUrl' => 'https://www.wx.com',
            'contractSavePath' => '/home/work/wx.com/contract',
            'realAuth_config' => require(WWW_DIR . '/itzlib/config/realAuth_config.php'),
            'naked_sql' => require(WWW_DIR . '/itzlib/config/sqls.php'),
            'realAuth_trigger' => 1,      //实名认证开关
            'weixin' => [
                'appId' => ConfUtil::get("Wechat-service-app.id"), //微信服务号appid
                'appSecret' => ConfUtil::get("Wechat-service-app.secret"),//微信服务号app secret
            ],
            // 错误返回值信息
            'errorcodeinfo' => require __DIR__ . '/error_code_info.php',
            //普惠禁止兑换的项目
            'disable_ph'    => require __DIR__ . '/PHDisableExchangeBorrow.php',
            //尊享禁止兑换的项目
            'disable_zx'    => require __DIR__ . '/ZXDisableExchangeBorrow.php',
            'youjie_base_url' => 'https://xfuser.zichanhuayuan.com',
            'contract' => require __DIR__ . '/contract.php',
            'wait_deal_id' => require __DIR__ . '/wait_deal_id.php',
            'itouzi' => require __DIR__ . '/itouzi.php',
            'xf_config' => require __DIR__.'/xf_config.php',
        ],
        //数据库组件
        'db' => require __DIR__ . "/db.php",
        'phdb' => require __DIR__ . '/phdb.php',
        'rcms' => require __DIR__ . '/rcms.php',
        'cdb' => require __DIR__ . '/cdb.php',
        'agdb' => require __DIR__ . '/agdb.php',
        'yiidb' => require __DIR__ . '/yiidb.php',
        'oss' => require __DIR__ . '/oss.php',
        'rcache'      => include __DIR__ . '/rcache.php',
        'offlinedb' => include __DIR__ . '/offlinedb.php',
    ],
    //预先加载log组建
    'preload' => [
        'log',
    ],
];
