<?php

define("ADMIN_ROOT",1);
define('SQL_DEBUG', false);

if (isset($_POST["PHPSESSID"])) session_id($_POST["PHPSESSID"]);

if(!defined("ADMIN_ROOT")) {
    die("Invalid access");
}

define('BASE_PATH','./');
define('THINK_PATH', '../ThinkPHP');
//定义项目名称和路径
define('ADMIN_APP_NAME', 'admin');
define('APP_PATH', '../');
define('APP_TIME', time());

define('ROOT_PATH', realpath(dirname(__FILE__).'/../../../').DIRECTORY_SEPARATOR);
require(ROOT_PATH.'core/framework/init.php');

defined('RUNTIME_PATH') OR define('RUNTIME_PATH', ROOT_PATH."runtime/admin/");
FP::setdir(ROOT_PATH."runtime/admin/");
//加载框架入口文件
require(THINK_PATH."/ThinkPHP.php");

if (!\libs\utils\AntiSqlInjection::checkAllInput()) {
    \libs\utils\Alarm::push('Admin_Sql_Injection', 'Admin后台注入', array($_GET, $_POST, $_COOKIE));
    echo '输入参数包含非法关键字';
    exit;
}

//实例化一个网站应用实例
$AppWeb = new App();
//应用程序初始化
$AppWeb->run();

slogs::set("host",APP_HOST);
$adm_session = es_session::get(md5(app_conf("AUTH_KEY")));
slogs::set("user",isset($adm_session['adm_name']) ? $adm_session['adm_name'] : '');
slogs::write();
