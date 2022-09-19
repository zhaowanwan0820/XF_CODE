<?php

defined('APP_ROOT_PATH') or define('APP_ROOT_PATH', dirname(__FILE__).'/../');
defined('APP_HOST') or define('APP_HOST', isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
defined('APP') or define('APP', APP_ROOT_PATH.'web/');
define('LOG_PATH', APP_ROOT_PATH . '/log/');

require(APP_ROOT_PATH."libs/common/simple.php");
use_config();
defined('APP_SITE') or define('APP_SITE', $GLOBALS['sys_config']['APP_SITE']);
defined('APP_RUNTIME_PATH') or define('APP_RUNTIME_PATH', APP_ROOT_PATH."runtime/".APP_SITE."/");

FP::import('libs.init.autoload');
//定位fatal原因
register_shutdown_function(function(){
    $error = error_get_last();
    if(!empty($error) && $error['type'] === E_ERROR){
        \libs\utils\Logger::error('PHPFatalError. '.json_encode($error));
    }
});
FP::import('libs.common.functions');
FP::import('libs.common.app');

FP::setdir(APP_RUNTIME_PATH.'app/');
FP::setdir(APP_RUNTIME_PATH.'app/db_caches/');
define('DB_PREFIX', app_conf('DB_PREFIX'));
/**
 * 定义模板引擎
 */
FP::import('libs.template.template');
FP::setdir(APP_RUNTIME_PATH.'app/tpl_caches/');
FP::setdir(APP_RUNTIME_PATH.'app/tpl_compiled/');
$GLOBALS['tmpl'] = new AppTemplate;
$GLOBALS['tmpl']->cache_dir      = APP_RUNTIME_PATH.'app/tpl_caches';
$GLOBALS['tmpl']->compile_dir    = APP_RUNTIME_PATH.'app/tpl_compiled';
$GLOBALS['tmpl']->template_dir   = APP_ROOT_PATH. 'app/Tpl/'. app_conf("TEMPLATE");

//引用语言包
$GLOBALS['lang'] = require APP_ROOT_PATH.'/app/Lang/'.app_conf("SHOP_LANG").'/lang.php';

//项目名称 (目前GTM使用，未来其他项目也可以使用)
define('APP_NAME', 'p2p');

define('TASK_APP_NAME', 'p2p');
//加载task系统
require_once __DIR__.'/../Common/Phalcon/Bootstrap.php';
$bootstrap = new \NCFGroup\Common\Phalcon\Bootstrap(dirname(__DIR__).'/task/');
$bootstrap->execTaskforTest(array());
NCFGroup\Task\Gearman\WxGearManWorker::monitorShutDown();

// RPC 相关
if (isset($GLOBALS['components_config']['components']['rpc'])) {
    $rpcConfig = $GLOBALS['components_config']['components']['rpc'];
    $rpcRemoteServers = $rpcDependModules = array();
    if (is_array($rpcConfig)) {
        foreach ($rpcConfig as $appName => $_config) {
            if ($_config['mockRpc'] === true) {
                $_modules = array();
                // 多module依赖实现
                if (strpos($_config['rpcServerUri'], ',') !== false) {
                    $_modules = explode(',', $_config['rpcServerUri']);
                }
                else {
                    $_modules = array($_config['rpcServerUri']);
                }
                foreach ($_modules as $_dm) {
                    $rpcDependModules[$_dm] = $_dm;
                }
            }
            else if ($_config['mockRpc'] === false) {
                $rpcName = $appName.'Rpc';
                $rpcRemoteServers[$rpcName] = array();
                $rpcRemoteServers[$rpcName]['rpcServerUri'] = $_config['rpcServerUri'];
                $rpcRemoteServers[$rpcName]['rpcClientId'] = $_config['rpcClientId'];
                $rpcRemoteServers[$rpcName]['rpcSecretKey'] = $_config['rpcSecretKey'];
            }
        }
    }
}
// 注册远程rpc对象， 对象名为配置文件内的{$appName}Rpc 例如 o2oRpc, openapiRpc, pushRpc
if (!empty($rpcRemoteServers)) {
    $loader = new \Phalcon\Loader();
    $loader->registerNamespaces(array(
        'NCFGroup\Common' => APP_ROOT_PATH . '/Common/',
        'NCFGroup\Protos' => APP_ROOT_PATH . '/Protos/',
        'Phalcon' => APP_ROOT_PATH . '/Common/Phalcon/',
        'Assert' => APP_ROOT_PATH . '/Common/Vendor/assert/lib/Assert/',
    ))->register();

    foreach ($rpcRemoteServers as $rpcName => $connectInfo) {
        $GLOBALS[$rpcName] = new \NCFGroup\Common\Extensions\RPC\RpcClientAdapter($connectInfo['rpcServerUri'], $connectInfo['rpcClientId'], $connectInfo['rpcSecretKey']);
    }
}

// 注册本地RPC对象， 对象名为默认的 rpc
if (!empty($rpcDependModules)) {
    foreach ($rpcDependModules as $moduleName) {
        $bootstrap->dependModule($moduleName);
    }
    $GLOBALS['rpc'] = new \NCFGroup\Common\Extensions\RPC\LocalClientAdapter($bootstrap->getDI());
}
