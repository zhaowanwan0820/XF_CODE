<?php

//报错全部打开
ini_set('error_reporting', E_ALL);

//动态缓存最数量
define("MAX_DYNAMIC_CACHE_SIZE", 1000);

//前端显示异常的message的错误码
define('SHOW_EXCEPTION_MESSAGE_CODE', 100);

//是否命令行模式运行
defined('IS_CGI') or define('IS_CGI',substr(PHP_SAPI, 0,3)=='cgi' ? 1 : 0 );

defined('ROOT_PATH') or define('ROOT_PATH', realpath(dirname(__FILE__).'/../../').DIRECTORY_SEPARATOR);

// 项目名称 (目前GTM使用，未来其他项目也可以使用)
define('APP_NAME', 'ncfph');

//运行脚本的文件名
if (!defined('_PHP_FILE_')) {
    if(IS_CGI) {
        //CGI/FASTCGI模式下
        $_temp  = explode('.php',$_SERVER["PHP_SELF"]);
        define('_PHP_FILE_',  rtrim(str_replace($_SERVER["HTTP_HOST"],'',$_temp[0].'.php'),'/'));
    } else {
        define('_PHP_FILE_',  rtrim($_SERVER["SCRIPT_NAME"],'/'));
    }
}

//网站URL根目录
if (!defined('APP_ROOT')) {
    $_root = dirname(_PHP_FILE_);
    $_root = (($_root=='/' || $_root=='\\')?'':$_root);
    define('APP_ROOT', $_root);
}

//修复header (HTTP_X_FORWARDED_HOST)攻击
if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])){
    unset($_SERVER['HTTP_X_FORWARDED_HOST']);
}

//系统根目录路径
defined('APP_ROOT_PATH') or define('APP_ROOT_PATH', ROOT_PATH.'core/');

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

//生产环境检查
define('IDC_ENV', get_cfg_var('idc_environment') == 'BEIJINGZHONGJINIDC' ? 'BEIJING_ZHONGJIN' : 'TIANJIN');
define('ENV_IN_DISASTER', IDC_ENV == 'BEIJING_ZHONGJIN' ? true : false);

require ROOT_PATH.'core/libs/utils/PhalconRPCInject.php';
\libs\utils\PhalconRPCInject::init();

//引入数据库的系统配置及定义配置函数
require ROOT_PATH.'core/libs/common/simple.php';
use_config();

//运行时站点名，比如firstp2p
defined('APP_SITE') or define('APP_SITE', $GLOBALS['sys_config']['APP_SITE']);

//运行时路径宏变量定义及目录建立
defined('APP_RUNTIME_PATH') or define('APP_RUNTIME_PATH', ROOT_PATH."runtime/".APP_SITE."/");
FP::setdir(APP_RUNTIME_PATH);

//日志类的引用
require(ROOT_PATH."core/libs/common/slogs.php");
slogs::init(defined("ADMIN_ROOT")?'admin':APP_SITE, ROOT_PATH.'storage/log/');

//加载常用函数
FP::import('libs.common.functions');

//运行时的STATIC路径模板
defined('APP_STATIC_PATH_MODEL') or
define('APP_STATIC_PATH_MODEL', get_http().app_conf('STATIC_DOMAIN_NAME').app_conf('STATIC_DOMAIN_ROOT').app_conf('STATIC_WEB_PATH'));
defined('APP_STATIC_OPTION') or define('APP_STATIC_OPTION', (app_conf('ENV_FLAG') == "dev") ? app_conf('STATIC_OPTION') : 'PUBLISH');

if (defined("ADMIN_ROOT")){
    $GLOBALS['sys_config']['URL_MODEL'] = 0;
    defined('APP_STATIC_PATH') or define('APP_STATIC_PATH', APP_ROOT_PATH.'public/static/admin/');
    defined('APP_WEB_STATIC') or define('APP_WEB_STATIC', APP_ROOT.'/static/admin/');
} else {
    FP::import('libs.common.app');
}

//加载autoload函数，只针对libs下面的规范库使用
FP::import('libs.init.autoload');

// 系统维护
\libs\utils\Upgrade::system();

//方便定位fatal原因
register_shutdown_function(function() {
    $error = error_get_last();
    if (!empty($error) && in_array($error['type'], array(E_ERROR, E_PARSE))) {
        \libs\utils\Logger::error('PHPFatalError. '.json_encode($error).', request_uri: '.$_SERVER['REQUEST_URI']);
    }
});

//引入cookie和session处理类
FP::import('libs.utils.es_cookie');
FP::import('libs.utils.es_session');

//引入时区配置及定义时间函数
if (function_exists('date_default_timezone_set')){
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
// for phpunit
$GLOBALS['db'] = $db;

//定义传入参数，重写$_REQUEST
$_REQUEST = array_merge($_GET, $_POST);

filter_injection($_REQUEST);
FP::import("libs.common.site");

//引用语言包
$lang = require APP_ROOT_PATH.'/data/lang/'.app_conf("SHOP_LANG").'/lang.php';

//加载数据库的分站配置文件
use_config_db();

// 导入rpc配置
\libs\utils\PhalconRPCInject::initComponents();

//admin的初始化到此为止
if (defined("ADMIN_ROOT")){
    return;
}

// 灾备检查
\libs\utils\Upgrade::disasterCheck();
//检查是否页面进维护页
\libs\utils\Upgrade::partial();

$logId = \libs\utils\Logger::getLogId();
header("LogId: {$logId}");
