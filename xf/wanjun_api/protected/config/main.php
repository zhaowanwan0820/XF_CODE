<?php

return [
    //模板目录
    'viewPath' => APP_DIR.'/views/',
    //protected根目录
    'basePath' => APP_DIR.'/protected/',
    //应用名
    'name' => 'debt',
    //运行时目录，主要用于模板的编译
    'runtimePath' => ConfUtil::get('runtimePath'),

    'import' => [
        'application.lib.models.*',
        'application.modules.openApi.classes.*',
        'application.modules.openApiV2.classes.*',
        'application.lib.models.seoModels.*',
        'application.lib.services.*',
        'application.lib.services.seoServices.*',
        'application.lib.classes.*',
        'application.lib.util.*',
        'itzlib.classes.*',
        'itzlib.sdk.*',
        'itzlib.api.*',
        'itzlib.itzService.*',
        'application.lib.extensions.RESTFullYii.components.*',
        'itzlib.plugins.mongo.*',
        'itzlib.plugins.mongo.validators.*',
        'itzlib.plugins.mongo.behaviors.*',
        'itzlib.plugins.mongo.util.*',
        'itzlib.plugins.blockcc.*',
        'itzlib.plugins.OSS.*',
        'itzlib.plugins.tcpdf.*',
        'itzlib.plugins.yop.lib.*',
        'itzlib.plugins.yop.lib.Util.*',
    ],

    //模块配置，各部分的默认值为：module=default,controller=index,action=index
    'modules' => [
        'default',
        'user',
        'common',
        'openApi',
        'openApiV2',
        'Debt',
        'Launch',
        'assetGarden',
        'apiService',
        'debtConfirm',
    ],

    //组件配置
    'components' => [
        'request' => [
            'class' => 'application.lib.extensions.csrf.components.HttpRequest',
            'enableCsrfValidation' => true, //总开关，是否开启csrf验证
            'enableCsrfReferrerValidation' => true, //是否开启referer验证
            'csrfTokenExpireTime' => 3000, //csrftoken有效期 默认50分钟
        ],
        'errorHandler' => [
            'errorAction' => 'common/Index/Error',
        ],

        //模板渲染组件，这里统一采用smarty引擎
        'viewRenderer' => [
            'escape_html' => true,
            //支持smarty引擎的插件
            'class' => 'ItzSmartyView',
            //模板后缀名
            'fileExtension' => '.tpl',
            //这里可用来配置全局变量，例如下面的配置，我们在模板种可以直接用<{$CONST.cssRoot}>来读取
            'globalVal' => [
                'viewPath' => APP_DIR.'/views/',
                'developMode' => false, //是否开发模式
            ],
            //这里为Smarty支持的属性
            'config' => [
                'left_delimiter' => '<{',
                'right_delimiter' => '}>',
                'template_dir' => APP_DIR.'/views/',
                // 'debugging' => true,
            ],
        ],
        'user' => [
            'class' => 'WebUser',
            'identityCookie' => ['domain' => '.xxx.com', 'path' => '/'],
            //'loginUrl'=>array('/guar/default/login'),
            'allowAutoLogin' => true,
            'authTimeout' => 1800, //session有效时间
            'loginRequiredAjaxResponse' => '{"status":1, "msg":"User not login!"}',
        ],
        //URLRewrite组件，根据需要进行配置
        'urlManager' => [
            'urlFormat' => 'path',
            'showScriptName' => false, //隐藏index.php
            'rules' => [
                'user' => 'user/user/Index',
                //首页
                '' => 'user/user/Index',
            ],
        ],

        'bootstrap' => [
            'class' => 'bootstrap.components.Bootstrap',
        ],

        'log' => [
            'class' => 'CLogRouter',
            'routes' => [
                [
                    'class' => 'CFileLogRoute',
                    'levels' => 'info',
                    //支持日期格式,{}中的为日期格式
                    'logPath' => ConfUtil::get('logPath'),
                    'logFile' => 'yii_wx_access.log',
                    'maxFileSize' => 2097152,
                ],
                [
                    'class' => 'CFileLogRoute',
                    'levels' => 'info',
                    'categories' => 'app_behaviour',
                    //支持日期格式,{}中的为日期格式
                    'logPath' => ConfUtil::get('logPath'),
                    'logFile' => 'app_behaviour.log',
                    'maxFileSize' => 2097152,
                ],
                [
                    'class' => 'CFileLogRoute',
                    'levels' => 'error,warning',
                    //支持日期格式,{}中的为日期格式
                    'logPath' => ConfUtil::get('logPath'),
                    'logFile' => 'yii_wx_error.log',
                    'maxFileSize' => 2097152,
                ],
            ],
        ],

        'c' => [
            'class' => 'ItzConfig',
            'viewPathNew' => APP_DIR.'/templates/', // PC改版新的模板目录
            // 错误返回值信息
            'errorcodeinfo' => require __DIR__.'/error_code_info.php',
            'XF_error_code_info' => require __DIR__.'/XF_error_code_info.php',
            'idno_key' => ConfUtil::get('decrypt_secret_key'), //证件号测试环境解密秘钥

            //普惠禁止兑换的项目
            'disable_ph' => require __DIR__.'/PHDisableExchangeBorrow.php',
            //尊享禁止兑换的项目
            'disable_zx' => require __DIR__.'/ZXDisableExchangeBorrow.php',
            'youjie_base_url' => 'https://xfuser.zichanhuayuan.com',
            'contract' => require __DIR__.'/contract.php',
            'itouzi' => require __DIR__.'/itouzi.php',
            'xf_config' => require __DIR__.'/xf_config.php',
            'error_code_info' => require __DIR__.'/error_code_info.php',

            //oss预览地址
            'oss_preview_address' => ConfUtil::get('oss_preview_address'),
        ],
        //权限库
        'db' => require __DIR__.'/db.php',
        'phdb' => require __DIR__.'/phdb.php',
        'cdb' => require __DIR__.'/cdb.php',
        'agdb' => require __DIR__.'/agdb.php',
        'yiidb' => require __DIR__.'/yiidb.php',
        'oss' => require __DIR__.'/oss.php',
        //redis
        'rcache' => include __DIR__.'/rcache.php',
        'dcache' => include __DIR__.'/dcache.php',
        'offlinedb' => include __DIR__.'/offlinedb.php',
        'rcms' => require __DIR__ . '/rcms.php',
    ],

    //预先加载log组建
    'preload' => [
        'log',
        'user',
    ],
];
