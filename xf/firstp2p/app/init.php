<?php

//报错全部打开
ini_set('error_reporting', E_ALL);

//动态缓存最数量
define("MAX_DYNAMIC_CACHE_SIZE", 1000);

//前端显示异常的message的错误码
define('SHOW_EXCEPTION_MESSAGE_CODE', 100);

//是否命令行模式运行
defined('IS_CGI') or define('IS_CGI', substr(PHP_SAPI, 0, 3)=='cgi' ? 1 : 0);

//项目名称 (目前GTM使用，未来其他项目也可以使用)
define('APP_NAME', 'p2p');

//运行脚本的文件名
if (!defined('_PHP_FILE_')) {
    if (IS_CGI) {
        //CGI/FASTCGI模式下
        $_temp  = explode('.php', $_SERVER["PHP_SELF"]);
        define('_PHP_FILE_', rtrim(str_replace($_SERVER["HTTP_HOST"], '', $_temp[0].'.php'), '/'));
    } else {
        define('_PHP_FILE_', rtrim($_SERVER["SCRIPT_NAME"], '/'));
    }
}

//网站URL根目录
if (!defined('APP_ROOT')) {
    $_root = dirname(_PHP_FILE_);
    $_root = (($_root=='/' || $_root=='\\')?'':$_root);
    define('APP_ROOT', $_root);
}

//修复header (HTTP_X_FORWARDED_HOST)攻击
if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    unset($_SERVER['HTTP_X_FORWARDED_HOST']);
}

//系统根目录路径
defined('APP_ROOT_PATH') or define('APP_ROOT_PATH', preg_replace('/app$/', '', rtrim(str_replace('\\', '/', dirname(__FILE__)), '/')));

//系统配置路径
defined('APP_CONF_PATH') or define('APP_CONF_PATH', APP_ROOT_PATH."conf/");

//网站根目录路径
defined('APP_WEBROOT_PATH') or define('APP_WEBROOT_PATH', APP_ROOT_PATH."public/");

//网站名
defined('APP_INDEX') or define('APP_INDEX', 'p2p');

//网站域名
defined('APP_HOST') or define('APP_HOST', isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');

//网站来源标识,解决未定义时的Notice错误
defined('APP') or define('APP', 'web');

//定义$_SERVER['REQUEST_URI']兼容性
if (!isset($_SERVER['REQUEST_URI'])) {
    if (isset($_SERVER['argv'])) {
        $uri = $_SERVER['PHP_SELF'] .'?'. $_SERVER['argv'][0];
    } else {
        $uri = $_SERVER['PHP_SELF'] .'?'. $_SERVER['QUERY_STRING'];
    }
    $_SERVER['REQUEST_URI'] = $uri;
}

//引入数据库的系统配置及定义配置函数
require APP_ROOT_PATH.'libs/common/simple.php';
use_config();

//运行时站点名，比如firstp2p
defined('APP_SITE') or define('APP_SITE', $GLOBALS['sys_config']['APP_SITE']);

//运行时路径宏变量定义及目录建立
defined('APP_RUNTIME_PATH') or define('APP_RUNTIME_PATH', APP_ROOT_PATH."runtime/".APP_SITE."/");
FP::setdir(APP_RUNTIME_PATH);

//日志类的引用
require(APP_ROOT_PATH."libs/common/slogs.php");
slogs::init(defined("ADMIN_ROOT")?'admin':APP_SITE, APP_ROOT_PATH."log/");

//加载常用函数
FP::import('libs.common.functions');

//运行时的STATIC路径模板
defined('APP_STATIC_PATH_MODEL') or
define('APP_STATIC_PATH_MODEL', get_http().app_conf('STATIC_DOMAIN_NAME').app_conf('STATIC_DOMAIN_ROOT').app_conf('STATIC_WEB_PATH'));
defined('APP_STATIC_OPTION') or define('APP_STATIC_OPTION', (app_conf('ENV_FLAG') == "dev") ? app_conf('STATIC_OPTION') : 'PUBLISH');

if (defined("ADMIN_ROOT")) {
    $GLOBALS['sys_config']['URL_MODEL'] = 0;
    defined('APP_STATIC_PATH') or define('APP_STATIC_PATH', APP_ROOT_PATH.'public/static/admin/');
    if (APP_STATIC_OPTION == "PUBLISH") {
        //前后台分开处理 去掉缓存
        defined('APP_WEB_STATIC') or define('APP_WEB_STATIC', APP_ROOT.'/static/admin/');
    } else {
        defined('APP_WEB_STATIC') or define('APP_WEB_STATIC', APP_ROOT.'/static/admin/');
    }
} else {
    FP::import('libs.common.app');
}

//加载autoload函数，只针对libs下面的规范库使用
FP::import('libs.init.autoload');

// 系统维护
\libs\utils\Upgrade::system();

//方便定位fatal原因
register_shutdown_function(function () {
    $error = error_get_last();
    if (!empty($error) && in_array($error['type'], array(E_ERROR, E_PARSE))) {
        \libs\utils\Logger::error('PHPFatalError. '.json_encode($error).', request_uri: '.$_SERVER['REQUEST_URI']);
    }
});

//引入cookie和session处理类
FP::import('libs.utils.es_cookie');
FP::import('libs.utils.es_session');

//如果脚本，不start session
if (PHP_SAPI !== 'cli' && APP !== 'api') {
    /*从oapi跳转过来的用户在.com,.cn登录*/
    if (!empty($_COOKIE['OPENUNIONSID'])) {
        session_id((string) $_COOKIE['OPENUNIONSID']);
    }
    es_session::start();
}

//URL重写模块，逻辑太复杂
if (app_conf('URL_MODEL') == 1) {
    url_rewrite();
}

//引入时区配置及定义时间函数
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set(app_conf('DEFAULT_TIMEZONE'));
}

//定义缓存
FP::import('libs.cache.Cache');
$cache = CacheService::getInstance();
$fcache = CacheService::getInstance();
$fcache->set_dir(APP_RUNTIME_PATH."data/");

//定义DB
define('DB_PREFIX', app_conf('DB_PREFIX'));
FP::setdir(APP_RUNTIME_PATH.'app/');
FP::setdir(APP_RUNTIME_PATH.'app/db_caches/');

//数据库实例
$db = \libs\db\Db::getInstance('firstp2p');
// 给单元测试系统集成使用
$GLOBALS['db'] = $db;

//加载phoenix框架, 因框架需要APP_ROOT_PATH以及DB_PREFIX两个常量，因而这部分代码必须放置在autoload加载以及db定义之后
define('LOG_PATH', APP_ROOT_PATH . '/log/');
require_once APP_ROOT_PATH . 'phoenix/src/messer.php';
require_once APP_ROOT_PATH . 'phoenix/src/autoload.php';

//定义模板引擎
FP::import('libs.template.template');
FP::setdir(APP_RUNTIME_PATH.'app/tpl_caches/');
FP::setdir(APP_RUNTIME_PATH.'app/tpl_compiled/');
$GLOBALS['tmpl'] = libs\web\Open::getTemplateEngine();

//定义传入参数，重写$_REQUEST
$_REQUEST = array_merge($_GET, $_POST);

//引用语言包
$lang = require APP_ROOT_PATH.'/app/Lang/'.app_conf("SHOP_LANG").'/lang.php';

//自动生成runtime.admin文件
FP::setdir(APP_ROOT_PATH."runtime/admin/");

//加载数据库的分站配置文件
use_config_db();

//加载task系统
if ((!defined('BACKEND_SERVICE_ENABLE') || constant('BACKEND_SERVICE_ENABLE') != 1) && app_conf("ENV_FLAG") != 'lc') {
    define('TASK_APP_NAME', 'p2p');
    require_once __DIR__.'/../Common/Phalcon/Bootstrap.php';
    $GLOBALS['phalcon_bootstrap'] = new \NCFGroup\Common\Phalcon\Bootstrap(dirname(__DIR__).'/task/', 'ncfwx');
    $GLOBALS['phalcon_bootstrap']->execTaskforTest(array());
}

//admin的初始化到此为止
if (defined("ADMIN_ROOT")) {
    return;
}

define("CTL", 'ctl');
define("ACT", 'act');

//用于商城部分的初始化
$IMG_APP_ROOT = APP_ROOT;
$GLOBALS['tmpl']->cache_dir      = APP_RUNTIME_PATH.'app/tpl_caches';
$GLOBALS['tmpl']->compile_dir    = APP_RUNTIME_PATH.'app/tpl_compiled';
$GLOBALS['tmpl']->template_dir   = APP_ROOT_PATH. 'app/Tpl/'. app_conf("TEMPLATE");
//定义当前语言包
$GLOBALS['tmpl']->assign("LANG", $lang);
//定义模板路径
$tmpl_path = APP_ROOT."/static/".app_conf("TEMPLATE");
$tmpl_new_path = APP_ROOT."/static/".app_conf("STATIC_FILE_VERSION");
$GLOBALS['tmpl']->assign("TMPL", $tmpl_path);
$GLOBALS['tmpl']->assign("TMPL_HTTP", get_http());
$GLOBALS['tmpl']->assign("TMPL_NEW", $tmpl_new_path);
$GLOBALS['tmpl']->assign("TMPL_REAL", APP_ROOT_PATH."app/Tpl/".app_conf("TEMPLATE"));

//检查是否页面进维护页
\libs\utils\Upgrade::partial();

filter_injection($_REQUEST);

$GLOBALS['tmpl']->assign("site_info", get_site_info());

if (app_conf("SHOP_OPEN")==0) {
    $GLOBALS['tmpl']->assign("page_title", $GLOBALS['lang']['SHOP_CLOSE']);
    $GLOBALS['tmpl']->assign("html", app_conf("SHOP_CLOSE_HTML"));
    $GLOBALS['tmpl']->display("shop_close.html");
    exit;
}

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

$GLOBALS['tmpl']->assign("APP_STATIC_PATH", APP_STATIC_PATH);
$GLOBALS['tmpl']->assign("APP_WEB_STATIC", APP_WEB_STATIC);
$GLOBALS['tmpl']->assign("APP_SKIN_PATH", APP_SKIN_PATH);
//输出语言包的js
// if(!file_exists(APP_STATIC_PATH.'lang.js'))
// {
//         $str = "var LANG = {";
//         foreach($lang as $k=>$lang_row)
//         {
//             $str .= "\"".$k."\":\"".str_replace("nbr","\\n",addslashes($lang_row))."\",";
//         }
//         $str = substr($str,0,-1);
//         $str .="};";
//         @file_put_contents(APP_STATIC_PATH.'lang.js',$str);
// }

//会员自动登录及输出
$cookie_uname = es_cookie::is_set("user_name")?es_cookie::get("user_name"):'';
$cookie_upwd = es_cookie::is_set("user_name")?es_cookie::get("user_pwd"):'';
if ($cookie_uname!=''&&$cookie_upwd!=''&&!es_session::is_set("user_info")) {
    FP::import('libs.libs.user');
    auto_do_login_user($cookie_uname, $cookie_upwd);
}

$user_info = es_session::get('user_info');
if (isset($user_info['id'])) {
    $user_info = $GLOBALS['db']->get_slave()->getRow("select * from ".DB_PREFIX."user where is_delete = 0 and is_effect = 1 and id = ".intval($user_info['id']));
}
es_session::set('user_info', $user_info);

if ($user_info) {
    // 注入当前登录用户信息
    \core\service\UserService::setLoginUser($user_info);

    $GLOBALS['tmpl']->assign("user_info", $user_info);
    setcookie('fpid', $user_info['id'], 0, '/');
} else {
    $GLOBALS['tmpl']->assign("user_info", array());
}

//保存返利的cookie
if (isset($_REQUEST['r'])) {
    $rid = intval(base64_decode($_REQUEST['r']));
    $ref_uid = intval($GLOBALS['db']->get_slave()->getOne("select id from ".DB_PREFIX."user where id = ".intval($rid)));
    es_cookie::set("REFERRAL_USER", intval($ref_uid));
} else {
    //获取存在的推荐人ID
    if (intval(es_cookie::get("REFERRAL_USER"))>0) {
        $ref_uid = intval($GLOBALS['db']->get_slave()->getOne("select id from ".DB_PREFIX."user where id = ".intval(es_cookie::get("REFERRAL_USER"))));
    }
}

//保存来路
if (!es_cookie::get("referer_url") && isset($_SERVER["HTTP_REFERER"])) {
    if (!preg_match("/".urlencode(get_domain().APP_ROOT)."/", urlencode($_SERVER["HTTP_REFERER"]))) {
        es_cookie::set("referer_url", $_SERVER["HTTP_REFERER"]);
    }
}
$referer = es_cookie::get("referer_url");

//判断是否有绑定未确认的担保
$guarantor = es_session::get("guarantor");
if ($guarantor) {
    $redirect = url("index", "deal", array("id" => $guarantor['deal_id']));
    es_session::delete("guarantor");
    return app_redirect($redirect);
}

define("DEAL_ONLINE", 1); //进行中
define("DEAL_HISTORY", 2); //过期
define("DEAL_NOTICE", 3); //未上线

FP::import("libs.common.site");

$logId = \libs\utils\Logger::getLogId();
header("LogId: {$logId}");
