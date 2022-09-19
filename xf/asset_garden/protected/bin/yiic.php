<?php

error_reporting(E_ERROR);
date_default_timezone_set('Asia/Shanghai');

define("APP_DIR", dirname(dirname(__DIR__)));
define("WWW_DIR", dirname(APP_DIR));
defined('YII_DEBUG') or define('YII_DEBUG', false);

require_once (WWW_DIR . '/thirdlib/yii/framework/yii.php');

//设置新命名空间
Yii::setPathOfAlias ( 'third', WWW_DIR . "/thirdlib" );
Yii::setPathOfAlias ( 'itzlib', WWW_DIR . "/itzlib" );

//导入命名空间
Yii::import ( "third.*" ); //import third
Yii::import ( "itzlib.yiiext.*" );
Yii::import ( "itzlib.util.*" );
Yii::import ( "itzlib.id5.SynPlat.*" );

$config = APP_DIR . '/protected/config/console.php';

//加载yii框架

require_once (WWW_DIR . '/thirdlib/yii/framework/yiic.php');
