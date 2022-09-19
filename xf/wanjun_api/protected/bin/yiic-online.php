<?php

error_reporting(E_ERROR); 
date_default_timezone_set('Asia/Shanghai');

define ( "APP_DIR", dirname( dirname ( dirname ( __FILE__ ) ) ));
define ( "WWW_DIR", dirname ( APP_DIR ) );
defined ( 'YII_DEBUG' ) or define ( 'YII_DEBUG', false );

require_once (WWW_DIR . '/thirdlib/yii/framework/yii.php');

//设置新命名空间
Yii::setPathOfAlias ( 'third', WWW_DIR . "/thirdlib" );
Yii::setPathOfAlias ( 'itzlib', WWW_DIR . "/itzlib" );

//导入命名空间
Yii::import ( "third.*" ); //import third
Yii::import ( "itzlib.yiiext.*" );
Yii::import ( "itzlib.util.*" );



$config= dirname(dirname(__FILE__)).'/config/console.php';


define('UC_CONNECT', '');
define('UC_DBHOST', 'rm-bp1oy3s9l9qum4h2z.mysql.rds.aliyuncs.com');
define('UC_DBUSER', 'itzadmin');
define('UC_DBPW', '14768D2wqvt348');
define('UC_DBNAME', 'db_itouzi_bbs_new');
define('UC_DBCHARSET', 'utf8');
define('UC_DBTABLEPRE', '`db_itouzi_bbs_new`.itz_ucenter_');
define('UC_DBCONNECT', '0');
define('UC_KEY', '!qaz2wsX');
define('UC_API', 'https://bbs.xxx.com/uc_server');
define('UC_CHARSET', 'utf-8');
define('UC_IP', '');
define('UC_APPID', '5');
define('UC_PPP', '20');



//加载yii框架
require_once (WWW_DIR . '/thirdlib/yii/framework/yiic.php');
