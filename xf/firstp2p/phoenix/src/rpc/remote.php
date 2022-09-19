<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-13 14:15:13
 * @encode UTF-8编码
 */
class P_Rpc_Remote {

    public static function call($class_name, $params, $version = 1) {
        $rpc_vars = P_Conf_Rpc::$rpc_remote;
        list($cn_prev, $cn_webroot) = explode(P_Conf_Autoload::CLASS_NAME_GLUE, $class_name);
        if (strtolower($cn_webroot) == "web") {
            $url = $rpc_vars['service_root'] . "&a=web&class=" . $class_name;
        } else {
            $url = $rpc_vars['service_root'] . "&a=api&v=" . $version . "&class=" . $class_name;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_TIMEOUT, $rpc_vars['timeout']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $op = curl_exec($ch);
        $error = curl_errno($ch);
        if ($error > 0) {
            
        }
        curl_close($ch);
        $ret = json_decode($op, true);
        return $ret;
    }

}
