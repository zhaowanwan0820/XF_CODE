<?php
 
// ini_set("display_errors", 'on'); 
error_reporting(0);
date_default_timezone_set('Asia/Shanghai');

define ( "APP_DIR", dirname ( dirname ( __FILE__ ) ) );
//定义www目录，如/var/gs/www
define ( "WWW_DIR", dirname ( APP_DIR ) );
//证书路径
define ( "IOS_DIR", APP_DIR.'/public/ck_production.pem');
//debug开关
// defined ( 'YII_DEBUG' ) or define ( 'YII_DEBUG', true );
//加载yii框架
require_once (WWW_DIR . '/thirdlib/yii/framework/yii.php');
//设置新命名空间
Yii::setPathOfAlias ( 'third', WWW_DIR . "/thirdlib" );

Yii::setPathOfAlias ( 'itzlib', WWW_DIR . "/itzlib" );

Yii::setPathOfAlias('bootstrap', APP_DIR.'/protected/extensions/bootstrap');
Yii::setPathOfAlias('wdueditor', APP_DIR.'/protected/extensions/wdueditor');
Yii::setPathOfAlias('ecolumns', APP_DIR.'/protected/extensions/ecolumns');
Yii::setPathOfAlias('itzlog', APP_DIR.'/protected/extensions/itzlog');
Yii::setPathOfAlias('WDjQuerycity', APP_DIR.'/protected/extensions/WDjQuerycity');
//导入命名空间
Yii::import ( "third.*" );
Yii::import ( "itzlib.yiiext.*" );
Yii::import ( "itzlib.util.*" );
Yii::import ( "itzlib.classes.*" );
Yii::import ( "itzlib.id5.SynPlat.*" );
Yii::import ( "itzlog.*" );
Yii::import("itzlib.plugins.payment.*");
//run app

Yii::createWebApplication ( APP_DIR . "/protected/config/main.php" )->run ();
