<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-13 14:15:13
 * @encode UTF-8编码
 */
class P_Rpc_Local {

    public static function call($class_name, $params) {
        if (class_exists($class_name)) {
            $service = new $class_name;
            return $service->execute($params);
        }
        new P_Exception_Rpc(P_Conf_Globalerrno::$message[P_Conf_Globalerrno::INVALID_RPC_CALL], P_Conf_Globalerrno::INVALID_RPC_CALL);
        return false;
    }

}
