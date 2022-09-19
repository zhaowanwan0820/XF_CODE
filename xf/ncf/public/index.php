<?php
ini_set("display_errors", 'on');
error_reporting(E_ALL);
define('APP', 'web');

require dirname(__FILE__).'/http2https.php';
//todo modify
//define('ROOT_PATH', realpath(dirname(__FILE__).'/../../../').DIRECTORY_SEPARATOR);
define('ROOT_PATH', realpath(dirname(__FILE__).'/../').DIRECTORY_SEPARATOR);

require(ROOT_PATH.'core/framework/init.php');

// 定义模板引擎
FP::import('libs.template.template');
FP::setdir(APP_RUNTIME_PATH.'app/tpl_caches/');
FP::setdir(APP_RUNTIME_PATH.'app/tpl_compiled/');
$GLOBALS['tmpl'] = \libs\web\Open::getTemplateEngine();

$GLOBALS['tmpl']->cache_dir      = APP_RUNTIME_PATH.'app/tpl_caches';
$GLOBALS['tmpl']->compile_dir    = APP_RUNTIME_PATH.'app/tpl_compiled';
$GLOBALS['tmpl']->template_dir   = APP_ROOT_PATH. 'app/Tpl/'. app_conf("TEMPLATE");

//输出根路径
$GLOBALS['tmpl']->assign("APP_ROOT", APP_ROOT);
defined('APP_STATIC_PATH') or define('APP_STATIC_PATH', APP_ROOT_PATH.'public/static/'.app_conf("TEMPLATE").'/');
if (APP_STATIC_OPTION == "PUBLISH") {
    $GLOBALS['tmpl']->assign("APP_ROOT_SATAIC", str_replace('__RAND__', 1, APP_STATIC_PATH_MODEL));
    defined('APP_WEB_STATIC') or define('APP_WEB_STATIC', str_replace('__RAND__', 1, APP_STATIC_PATH_MODEL.app_conf("TEMPLATE").'/'));
    defined('APP_SKIN_PATH') or define('APP_SKIN_PATH', str_replace('__RAND__', 1, APP_STATIC_PATH_MODEL.'skins/'.app_conf("TPL_SITE_DIR").'/'));
} else {
    $GLOBALS['tmpl']->assign("APP_ROOT_SATAIC", APP_ROOT.'/static/');
    defined('APP_WEB_STATIC') or define('APP_WEB_STATIC', APP_ROOT.'/static/'.app_conf("TEMPLATE").'/');
    defined('APP_SKIN_PATH') or define('APP_SKIN_PATH', APP_ROOT.'/static/skins/'.app_conf("TPL_SITE_DIR").'/');
}

// 引用语言包
$lang = require ROOT_PATH.'core/data/lang/'.app_conf("SHOP_LANG").'/lang.php';
// 定义当前语言包
$GLOBALS['tmpl']->assign("LANG", $lang);
// 定义模板路径
$tmpl_path = APP_ROOT."/static/".app_conf("TEMPLATE");
$tmpl_new_path = APP_ROOT."/static/".app_conf("STATIC_FILE_VERSION");
$GLOBALS['tmpl']->assign("TMPL",$tmpl_path);
$GLOBALS['tmpl']->assign("TMPL_HTTP",get_http());
$GLOBALS['tmpl']->assign("TMPL_NEW",$tmpl_new_path);
$GLOBALS['tmpl']->assign("TMPL_REAL",APP_ROOT_PATH."app/Tpl/".app_conf("TEMPLATE"));

//URL重写模块，逻辑太复杂
if (app_conf('URL_MODEL') == 1) {
    url_rewrite();
}

try {
    // XSS检测
    $parttern = array('"', "'", '%27', '%3E', '%3C', '>', '<');
    if (strlen(str_replace($parttern, '', $_SERVER['QUERY_STRING'])) !== strlen($_SERVER['QUERY_STRING'])) {
        \libs\utils\Logger::error("XSSDetected. host:{$_SERVER['HTTP_HOST']}, query:{$_SERVER['QUERY_STRING']}");
        app_redirect('/');
    }

    //引入cookie和session处理类
    FP::import('libs.utils.es_cookie');
    FP::import('libs.utils.es_session');

    /*从oapi跳转过来的用户在.com,.cn登录*/
    if(!empty($_COOKIE['OPENUNIONSID'])){
        session_id((string) $_COOKIE['OPENUNIONSID']);
    }
    es_session::start();

    SiteApp::init()->run();
} catch (\Exception $e) {
    require_once ROOT_PATH.'core/libs/utils/Logger.php';
    \libs\utils\Logger::error('InitException. message:'.$e->getMessage().', file:'.$e->getFile().', line:'.$e->getLine());
    $logId = \libs\utils\Logger::getLogId();
    // 展示错误页面
    require dirname(__FILE__).'/../views/exception.php';
}
