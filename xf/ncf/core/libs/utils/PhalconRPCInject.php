<?php

namespace libs\utils;

/**
 * 分站相关
 */
class PhalconRPCInject {
    static $instance = null;
    public static function init() {
        if (self::$instance) {
            return ;
        }

        // 启动phalcon-common，这里只引入Common的命名空间
        // 这里先直接把初始化代码写在这里，后期转移到phalcon-common
        // Initial, we read two configurations from php.ini
        $env = get_cfg_var("phalcon.env");
        if (!in_array($env, array('product', 'preproduct'))) {
            $debug = new \Phalcon\Debug();
            $debug->listen();
        }

        // Constants definition
        define("APP_ENV", $env);
        define("APP_ROOT_COMMON_DIR", ROOT_PATH . 'Common/');
        define("APP_ROOT_COMMON_CONF_DIR", APP_ROOT_COMMON_DIR . 'config/');
        define("APP_ROOT_COMMON_LOAD_DIR", APP_ROOT_COMMON_DIR . 'load/');

        $system = ROOT_PATH;
        // Global config file must exists
        $gConfPath = APP_ROOT_COMMON_CONF_DIR  . APP_ENV . '.php';
        if(!is_file($gConfPath)) {
            throw new \Phalcon\Config\Exception("Global config file not exist, file position: {$gConfPath}");
        }

        $gConfArr = require $gConfPath;
        $config = new \Phalcon\Config($gConfArr);
        // 配置中得日志注入
        $config['logger'] = array(
            'file' => array(
                'path' => ROOT_PATH.'storage/log/logger/p2p_rpc_'.date('Ymd').'.log',
            ),
        );

        $loader = new \Phalcon\Loader();
        $di = new \Phalcon\DI\FactoryDefault\CLI();
        $projectName = 'ncfph';
        $application = new \Phalcon\CLI\Console();
        $application->setDI($di);
        require APP_ROOT_COMMON_LOAD_DIR . 'default-cli.php';

        $loader->registerNamespaces(array(
            'NCFGroup\Common' => ROOT_PATH . 'Common/',
            'NCFGroup\Protos' => ROOT_PATH . '/Protos/',
            'Phalcon' => ROOT_PATH . 'Common/Phalcon/',
            'Assert' => ROOT_PATH . 'Common/Vendor/assert/lib/Assert/',
        ))->register();

        // 注册本地RPC对象， 对象名为默认的 rpc
        self::$instance = true;
    }

    public static function initComponents() {
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
            foreach ($rpcRemoteServers as $rpcName => $connectInfo) {
                $GLOBALS[$rpcName] = new \NCFGroup\Common\Extensions\RPC\RpcClientAdapter($connectInfo['rpcServerUri'], $connectInfo['rpcClientId'], $connectInfo['rpcSecretKey']);
            }
        }
    }
}
