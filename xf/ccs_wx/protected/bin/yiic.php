<?php
date_default_timezone_set('Asia/Shanghai');
define ( "APP_DIR", dirname( dirname ( dirname ( __FILE__ ) ) ));
define ( "WWW_DIR", dirname ( APP_DIR ) );
defined ( 'YII_DEBUG' ) or define ( 'YII_DEBUG', false );

require_once (WWW_DIR . '/thirdlib/yii/framework/yii.php');
//证书路径
define ( "IOS_DIR", WWW_DIR.'/dashboard/public/ck_production.pem');

//设置新命名空间
Yii::setPathOfAlias ( 'third', WWW_DIR . "/thirdlib" );
Yii::setPathOfAlias ( 'itzlib', WWW_DIR . "/itzlib" );
Yii::setPathOfAlias('bootstrap', APP_DIR.'/protected/extensions/bootstrap');
Yii::setPathOfAlias('ecolumns', APP_DIR.'/protected/extensions/ecolumns');
Yii::setPathOfAlias('WDjQuerycity', APP_DIR.'/protected/extensions/WDjQuerycity');
Yii::setPathOfAlias('itzlog', APP_DIR.'/protected/extensions/itzlog');
//导入命名空间
Yii::import ( "third.*" );
Yii::import ( "itzlib.yiiext.*" );
Yii::import ( "itzlib.util.*" );
Yii::import ( "itzlib.classes.*" );
Yii::import ( "itzlib.id5.SynPlat.*" );
Yii::import ( "itzlog.*" );
//run app
$config = APP_DIR . '/protected/config/console.php';

//加载yii框架
require_once (WWW_DIR . '/thirdlib/yii/framework/yiic.php');
