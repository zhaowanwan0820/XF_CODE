<?php
$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
return [
    //模板目录
    'viewPath' => APP_DIR . "/views/",
    //protected根目录
    'basePath' => APP_DIR . '/protected/',
    //应用名
    'name' => 'ccs',

    'aliases' => [
        'iauth' => APP_DIR . '/protected/modules/iAuth',
    ],

    //运行时目录，主要用于模板的编译
    'runtimePath' => ConfUtil::get('runtimePath'),

    'import' => [
        'application.lib.models.*',  //orm model
        'application.lib.models.seoModels.*',
        'application.lib.models.windModels.*',
        'application.lib.models.bbs.*',
        'application.lib.models.data.*',
        'application.lib.services.*',  //service layer
        'application.lib.classes.*',  //other lib class
        'application.lib.util.*',
        'application.extensions.WDjQuerycity.*',

        'application.models.*',
        'application.components.*',
        'application.extensions.upload.*',

        'application.modules.rights.*',
        'application.modules.rights.components.*',
        'itzlib.plugins.mongo.*',
        'itzlib.plugins.mongo.validators.*',
        'itzlib.plugins.mongo.behaviors.*',
        'itzlib.plugins.mongo.util.*',
        'itzlib.plugins.rundeck.*',
        'itzlib.api.*',     //itzlib class
        'itzlib.itzService.*', //itzlib services
        'itzlib.plugins.OSS.*',
        'itzlib.plugins.yop.lib.*',
        'itzlib.plugins.yop.lib.Util.*',
    ],
    //模块配置，各部分的默认值为：module=default,controller=index,action=index
    'modules' => [
        'gii' => [
            'class'=>'system.gii.GiiModule',
            'password'=>'1',
            // If removed, Gii defaults to localhost only. Edit carefully to taste.
            'ipFilters'=>array('*'),
        ],
        'default',  //这是默认加载的模块
        'user',
        'offline',
        'shop',
        'debtMarket',
        'borrower',
        'iauth' => [
            'class' => 'iauth\IAuthModule',
            'components' => [
                'dualFactor' => [
                    'class' => 'iauth\components\DualFactor',
                ],
            ],
        ],
        'rights' => [
            'superuserName' => 'super_admin',//自己用户表里面的用户，这个作为超级用户
            'userClass' => 'ItzUser',//自己用户表对应的用户模型类
            'authenticatedName' => 'Authenticated',
            'userIdColumn' => 'id',//自己用户表对应的id栏
            'userNameColumn' => 'username',//自己用户表对应的栏
            'enableBizRule' => true,
            'enableBizRuleData' => false,
            'displayDescription' => true,
            'flashSuccessKey' => 'RightsSuccess',
            'flashErrorKey' => 'RightsError',
            'baseUrl' => '/rights',
            'layout' => 'header',
            'cssFile' => 'rights.css',
            'install' => true,//第一次安装需要为true，安装成功以后记得改成false
            'debug' => false,
        ],/**/
        'message',
    ],
    //组件配置
    'components' => [
        'request'=>[
            'class' => 'application.extensions.csrf.components.HttpRequest',
            'enableCsrfValidation'=>false,
            'enableCsrfReferrerValidation'=>false,
            'csrfTokenExpireTime'=>3000,
            'blackUrl' => include APP_DIR . '/protected/config/dashboard_config/black_url.php',
//            'enableCookieValidation'=>true,
        ],
        //模板渲染组件，这里统一采用smarty引擎
        'viewRenderer' => [
            'escape_html' => false,
            //支持smarty引擎的插件
            'class' => 'ItzSmartyView',
            //模板后缀名
            'fileExtension' => '.tpl',
            //这里可用来配置全局变量，例如下面的配置，我们在模板种可以直接用<{$CONST.cssRoot}>来读取
            'globalVal' => [
                'viewPath' => APP_DIR . '/views/',
                'cssPath' => '/assets/css',
                'cssLibPath' => '/assets/lib/layui/css',
                'jsPath' => '/assets/js',
                'jsLibPath' => '/assets/lib/layui/lay/modules',
                'layuiPath' => '/assets/lib/layui',
                'ypyPath' => "/itzstaticupyun.itzcdn.com", //必须用又拍云的地址 上传
                'jsVersion' => time(),
                'cssVersion' => time(),
                'developMode' => false,//是否开发模式
            ],
            //这里为Smarty支持的属性
            'config' => [
                'left_delimiter' => "<{",
                'right_delimiter' => "}>",
                'template_dir' => APP_DIR . "/views/",
                'debugging' => false,
            ]
        ],
        'user' => [
            'class' => 'iauth\components\IAuthWebUser',
            'identityCookie' => ['domain' => '.zichanhuayuan.com', 'path' => '/'],
            'loginUrl' => ['/default/index/index'],
            // 'stateKeyPrefix'=>explode('.', $_SERVER['SERVER_NAME'])[0],//后台session前缀，使用二级域名前缀
            'allowAutoLogin' => true,
            'authTimeout' => 1800, //session有效时间
            'loginRequiredAjaxResponse' => '{"code":10107, "info":"用户未登录！"}',
        ],
        /* iAuth 组件 iuser, iDbAuthManager */
        'iuser' => [
            'class' => 'iauth\components\IAuthWebUser',
            'identityCookie' => ['domain' => '.zichanhuayuan.com', 'path' => '/'],
            'loginUrl' => ['/default/index/index'],
            'allowAutoLogin' => true,
            'authTimeout' => 1800, //session有效时间
            'loginRequiredAjaxResponse' => '{"code":10107, "info":"用户未登录！"}'
        ],
        'iDbAuthManager' => [
            'class' => '\iauth\components\IDbAuthManager',
            'dualFactorKey' => 'iauth_dual_factor_auth',
            'admin' => 'super_admin'
        ],
        'authManager' => [
            'class' => 'RDbAuthManager',     // Provides support authorization item sorting.
            'assignmentTable' => 'ItzAuthAssignment',//认证项赋权关系
            'itemTable' => 'ItzAuthItem',//认证项表名称
            'itemChildTable' => 'ItzAuthItemChild',//认证项父子关系
            'rightsTable' => 'ItzRights',
            'defaultRoles' => ['Guest'],//默认角色
        ],/**/
        //URLRewrite组件，根据需要进行配置
        'urlManager' => [
            'urlFormat' => 'path',
            'showScriptName' => false,
            'rules' => [
                'gii'=>'gii/default/index',
                'gii/<controller:\w+>'=>'gii/<controller>',
                'gii/<controller:\w+>/<action:\w+>'=>'gii/<controller>/<action>',

                //首页
                '' => 'default/index/index',
                '<action:\w+>/index.html' => '/default/static/<action>',
                '<module:\w+>/<controller:\w+>/<action:\w+>' => '<module>/<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => 'default/<controller>/<action>',
                '<action:\w+>' => 'default/index/<action>'
            ],
            'baseUrl' => $http_type.$_SERVER['HTTP_HOST'],
            //'baseUrl' => 'http://115.29.189.125:8090'
        ],
        'bootstrap' => [
            'class' => 'bootstrap.components.Bootstrap',
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
                    'logFile' => 'yii_zichanhuayuan_ccs_access.log',
                    'maxFileSize' => 2097152
                ],
                [
                    'class' => 'CFileLogRoute',
                    'levels' => 'error,warning',
                    //支持日期格式,{}中的为日期格式
                    'logPath' => ConfUtil::get('logPath'),
                    'logFile' => 'yii_zichanhuayuan_ccs_error.log',
                    'maxFileSize' => 2097152
                ],
                [
                    'class' => 'CFileLogRoute',
                    'levels' => 'record',
                    //支持日期格式,{}中的为日期格式
                    'logPath' => ConfUtil::get('logPath'),
                    'logFile' => 'ccs_delete_userinfo.log',
                    'maxFileSize' => 2097152
                ],
//                [
//                    'class'=>'CWebLogRoute',
//                    'levels'=>'trace',
//                    'categories'=>'system.db.*',
//                ],
            ]
        ],
        'c' => [
            'class' => 'ItzConfig',
            'baseUrl' => "https://".$_SERVER['SERVER_NAME'],
            'params_borrow' => include APP_DIR . '/protected/config/dashboard_config/borrow_config.php',
            'params_sidebar' => APP_DIR . '/views/layouts/sidebar_admin_config.php',
            'yunUrl' => 'https://itzstaticupyun.itzcdn.com/',
            'coupon' => include WWW_DIR . "/itzlib/config/couponconfig.php",
            'params_msg' => require APP_DIR . '/protected/config/dashboard_config/msg_config.php',
            'params_market' => require APP_DIR. '/protected/config/dashboard_config/market_config.php',
            'linkconfig' => require WWW_DIR . '/itzlib/config/linkconfig.php',
            'gatewayList' => require APP_DIR . '/protected/config/dashboard_config/gatewayList.php',
            'esAuditConfig' => require APP_DIR . '/protected/config/dashboard_config/esAuditConfig.php',
            'queryAuditConfig' => require WWW_DIR . '/itzlib/config/queryAuditConfig.php',
            // 'codeConfig' => include WWW_DIR . '/itzlib/config/dashboard_config/apicodeconfig.php',
            //'xw_request_url' => \itzlib\sdk\ServiceUtil::getAddress('com.zichanhuayuan.trustee') . '/trustee/v1',
            'xw_request_url' => ConfUtil::get('xw-java-interface.address'),
            'idno_key' => ConfUtil::get('decrypt_secret_key'),//证件号测试环境解密秘钥
            'assignee' => require __DIR__ . "/assignee.php", // 受让方
            // 错误返回值信息
            'errorcodeinfo' => require __DIR__ . '/error_code_info.php',
            // 回购用户ID
            'buyback_user_id' => require __DIR__ . "/buyback_user_id.php",
            // 资产花园网信债转的确认交易成功接口地址
            'wx_confirm_debt_api' => ConfUtil::get('wx_confirm_debt_api'),
            //oss预览地址
            "oss_preview_address" => ConfUtil::get('oss_preview_address'),
            // 消费专区ID
            "channel_id" => include __DIR__ . '/channel_id.php',
            'xf_config' => include __DIR__ . '/xf_config.php',
            'payment_account_config' => include __DIR__ . '/payment_account_config.php',
        ],
        'async' => include __DIR__ . '/async.php',
        //权限库
        'db' => require __DIR__ . '/db.php',
        //普惠
        'phdb' => require __DIR__ . "/phdb.php",
        'rcache'      => include __DIR__ . '/rcache.php',
        'oss' => include __DIR__ . '/oss.php',
    ],

    //预先加载log组建
    'preload' => [
        'log',
        'user',
    ],
    'params' => include WWW_DIR . '/itzlib/config/linkconfig.php',

];
