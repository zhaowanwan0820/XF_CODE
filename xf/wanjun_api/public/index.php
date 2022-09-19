<?php
 ini_set("display_errors", 'on');
error_reporting(0);
date_default_timezone_set('Asia/Shanghai');

define ( "APP_DIR", dirname ( dirname ( __FILE__ ) ) );
//定义www目录，如/var/gs/www
define ( "WWW_DIR", dirname ( APP_DIR ) );
defined ( 'ROOT_PATH' ) or define ( 'ROOT_PATH', '/home/work/xxx.com/xxx.com/' );
//debug开关
defined ( 'YII_DEBUG' ) or define ( 'YII_DEBUG', true );
//加载yii框架
require_once (WWW_DIR . '/thirdlib/yii/framework/yii.php');
//设置新命名空间
Yii::setPathOfAlias ( 'third', WWW_DIR . "/thirdlib" );

Yii::setPathOfAlias ( 'itzlib', WWW_DIR . "/itzlib" );

header("Content-type:text/html;charset=utf-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header('Access-Control-Allow-Credentials: true');
isset($_SERVER['HTTP_ORIGIN']) && header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
header("Access-Control-Allow-Headers: X-WJ-AUTHORIZATION,AUTHORIZATION,Content-Type,Accept,Origin,User-Agent,DNT,Cache-Control,X-Mx-ReqToken,Keep-Alive,X-Requested-With,If-Modified-Since");
if($_SERVER['REQUEST_METHOD']=='OPTIONS'){
     header("HTTP/1.0 200 OK");exit;
}
//导入命名空间
Yii::import ( "third.*" );
Yii::import ( "itzlib.yiiext.*" );
Yii::import ( "itzlib.util.*" );
Yii::import ( "itzlib.id5.SynPlat.*" );

//run app
try 
{
    Yii::createWebApplication ( APP_DIR . "/protected/config/main.php" )->run ();
}catch(Exception $e){
    Yii::log ( "Error:".$e->getMessage() , CLogger::LEVEL_WARNING, __METHOD__ );
    throw $e;
}
