<?php

namespace libs\utils;

/**
 * 分站相关
 */
class PhalconRpcInject
{
    static $instance = null;
    public static function init() {
        if(self::$instance) {
            return ;
        }
        // RPC 相关 By wangjiansong@
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
        if (!defined('BACKEND_SERVICE_ENABLE')) { //远程调用backend服务时,其他rpc服务没有注册上,因此p2pbackend已在自己入口加载,此处不能重复dependModule
            if (!empty($rpcDependModules)) {
                foreach ($rpcDependModules as $moduleName) {
                    $GLOBALS['phalcon_bootstrap']->dependModule($moduleName);
                }
                $GLOBALS['rpc'] = new \NCFGroup\Common\Extensions\RPC\LocalClientAdapter($GLOBALS['phalcon_bootstrap']->getDI());
            }
        }
        self::$instance = true;
    }

}
