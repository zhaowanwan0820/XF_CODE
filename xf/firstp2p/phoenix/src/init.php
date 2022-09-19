<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-12 11:22:36
 * @encode UTF-8编码
 */
defined('FRAMEWORK_PATH') or define('FRAMEWORK_PATH', rtrim(str_replace('\\', '/', dirname(__FILE__)), '/') . '/');
require_once FRAMEWORK_PATH . '/messer.php';

M::I('allow_call_time_pass_reference', "On");
M::D('IS_CLI', substr(PHP_SAPI, 0, 3) == 'cli' ? 1 : 0);
M::D('LOG_PATH', '/tmp/logs');
M::R(M::D('FRAMEWORK_PATH') . '/exception/handler.php');
M::R(M::D('FRAMEWORK_PATH') . '/autoload.php');

class P_Init {

    private static $_instance = false;
    private static $_suffix = 'Init';

    private function __construct() {
        
    }

    public static function init($app, $filter_function = 'trim') {
        if (M::D('IS_CLI')) {
            $_SERVER['REQUEST_URI'] = P_Http::argv(0);
            $_SERVER['QUERY_STRING'] = http_build_query(P_Http::argv());
        }
        P_Log_Slogs::init(M::D('APP', $app), M::D('LOG_PATH') . P_Conf_Autoload::PATH_GLUE . M::D('APP', $app));
        P_Http::reset($filter_function);
        if (false === self::$_instance) {
            $class = implode(P_Conf_Autoload::CLASS_NAME_GLUE, array(P_Conf_Autoload::CONTROLLER_PREFIX, ucfirst(strtolower(M::D('APP'))), self::$_suffix));
            if (!class_exists($class)) {
                P_Log_Slogs::at("{$class} does not exist!");
                P_Log_Slogs::write();
                return;
            }
            self::$_instance = new $class;
        }
        self::$_instance->run();
        if (M::D('DEBUG')) {
            error_reporting(E_ALL | E_STRICT);
        } else {
            error_reporting(0);
        }
        if (!M::D('IS_CLI')) {
            P_Dispatcher::route(P_Diversion::diversion());
        } else {
            M::I('memory_limit', -1);
            M::I('max_execution_time', 0);
        }
    }

}
