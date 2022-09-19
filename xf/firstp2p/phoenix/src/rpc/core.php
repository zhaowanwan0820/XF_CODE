<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-13 14:15:13
 * @encode UTF-8编码
 */
class P_Rpc_Core {

    public static function call($class_name, $params) {
        $rpc_class = implode(P_Conf_Autoload::CLASS_NAME_GLUE, array(P_Conf_Autoload::FRAMEWORK_PREFIX, P_Conf_Rpc::DEFAULT_INFFIX, P_Conf_Rpc::$rpc_method));
        if (class_exists($rpc_class)) {
            $rpc = new $rpc_class;
            return $rpc->call($class_name, $params);
        }
        new P_Exception_Rpc("undefined class={$rpc_class}", P_Conf_Globalerrno::INVALID_RPC_CALL);
        return false;
    }

}
