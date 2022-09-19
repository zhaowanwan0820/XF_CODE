<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-11 15:33:27
 * @encode UTF-8编码
 */
class P_Dispatcher {

    private static $_default_action = '';
    private static $_default_controller = 'index';
    private static $_default_controller_first = false;
    private static $_default_route_rules = array(
        array(
            'type' => P_Conf_Route::ROUTE_MAP,
            'controller_first' => true,
        )
    );
    private static $_app_div = false;
    private static $_rewrite_var = ':';
    private static $_rewrite_wildcard = '*';

    private static function _route_map($rules) {
        $request_uri = parse_url(P_Http::server('REQUEST_URI'));
        if (!isset($request_uri['path'])) {
            new P_Exception_Dispatcher('invalid request uri', P_Conf_Globalerrno::DISPATCHER_ERROR);
            return false;
        }
        $path = explode(P_Conf_Autoload::PATH_GLUE, $request_uri['path']);
        $controller = strlen($path[1]) ? ucfirst(strtolower($path[1])) : ucfirst(strtolower(self::$_default_controller));
        $action = isset($path[2]) ? ucfirst(strtolower($path[2])) : ucfirst(strtolower(self::$_default_action));
        $controller_first = isset($rules['controller_first']) ? $rules['controller_first'] : self::$_default_controller_first;
        if (!$controller_first) {
            $class = implode(P_Conf_Autoload::CLASS_NAME_GLUE, array(P_Conf_Autoload::CONTROLLER_PREFIX, ucfirst(strtolower(self::$_app_div)), $controller, $action));
        } else {
            $class = implode(P_Conf_Autoload::CLASS_NAME_GLUE, array(P_Conf_Autoload::CONTROLLER_PREFIX, ucfirst(strtolower(self::$_app_div)), $controller));
        }
        if (!class_exists($class)) {
            new P_Exception_Dispatcher("invalid class={$class}", P_Conf_Globalerrno::DISPATCHER_ERROR);
            return false;
        }
        return $class;
    }

    private static function _route_regex($rules) {
        if (!isset($rules['regex'])) {
            new P_Exception_Dispatcher('invalid regex rules', P_Conf_Globalerrno::DISPATCHER_ERROR);
            return false;
        }
        $request_uri = parse_url(P_Http::server('REQUEST_URI'));
        if (!isset($request_uri['path'])) {
            new P_Exception_Dispatcher('invalid request uri', P_Conf_Globalerrno::DISPATCHER_ERROR);
            return false;
        }
        if (!@preg_match($rules['regex'], $request_uri['path'], $matches)) {
            new P_Exception_Dispatcher('unmatch regex rules', P_Conf_Globalerrno::DISPATCHER_ERROR);
            return false;
        }
        $param = array();
        for ($i = 1; $i < count($matches); $i++) {
            if (isset($rules['index']) && is_array($rules['index']) && isset($rules['index'][$i]) && strlen(trim($rules['index'][$i]))) {
                $param[trim($rules['index'][$i])] = $matches[$i];
            } else {
                $param[$i] = $matches[$i];
            }
        }
        $controller = isset($rules['controller']) ? ucfirst(strtolower($rules['controller'])) : ucfirst(strtolower(self::$_default_controller));
        $action = isset($rules['action']) ? ucfirst(strtolower($rules['action'])) : ucfirst(strtolower(self::$_default_action));
        $class = implode(P_Conf_Autoload::CLASS_NAME_GLUE, array(P_Conf_Autoload::CONTROLLER_PREFIX, ucfirst(strtolower(self::$_app_div)), $controller, $action));
        if (!class_exists($class)) {
            new P_Exception_Dispatcher("invalid class={$class}", P_Conf_Globalerrno::DISPATCHER_ERROR);
            return false;
        }
        foreach ($param as $k => $v) {
            P_Http::set_get($k, $v);
        }
        return $class;
    }

    private static function _route_rewrite($rules) {
        if (!isset($rules['rewrite'])) {
            new P_Exception_Dispatcher('invalid rewrite rules', P_Conf_Globalerrno::DISPATCHER_ERROR);
            return false;
        }
        $request_uri = parse_url(P_Http::server('REQUEST_URI'));
        if (!isset($request_uri['path'])) {
            new P_Exception_Dispatcher('invalid request uri', P_Conf_Globalerrno::DISPATCHER_ERROR);
            return false;
        }
        $path = explode(P_Conf_Autoload::PATH_GLUE, $request_uri['path']);
        $rewrite = $rules['rewrite'];
        if (0 !== strpos($rewrite, P_Conf_Autoload::PATH_GLUE)) {
            $rewrite = P_Conf_Autoload::PATH_GLUE . $rewrite;
        }
        $rewrite = explode(P_Conf_Autoload::PATH_GLUE, $rewrite);
        $rewrite_index = 1;
        $request_index = 1;
        $param = array();
        while ($rewrite_index < count($rewrite) && $request_index < count($path)) {
            if ($rewrite[$rewrite_index] === $path[$request_index]) {
                $request_index++;
            } else if (0 === strpos(trim($rewrite[$rewrite_index]), self::$_rewrite_var) && strlen(trim($rewrite[$rewrite_index])) > 1) {
                $param[substr(trim($rewrite[$rewrite_index]), 1)] = $path[$request_index];
                $request_index++;
            } else if ($rewrite[$rewrite_index] === self::$_rewrite_wildcard) {
                if ((count($path) - $request_index) % 2) {
                    new P_Exception_Dispatcher('invalid params', P_Conf_Globalerrno::DISPATCHER_ERROR);
                    return false;
                }
                for ($request_index; $request_index < count($path); $request_index = $request_index + 2) {
                    if (!strlen(trim($path[$request_index]))) {
                        new P_Exception_Dispatcher('invalid param name', P_Conf_Globalerrno::DISPATCHER_ERROR);
                        return false;
                    }
                    $param[$path[$request_index]] = $path[$request_index + 1];
                }
            } else {
                new P_Exception_Dispatcher('invalid rewrite prefix', P_Conf_Globalerrno::DISPATCHER_ERROR);
                return false;
            }
            $rewrite_index++;
        }
        $controller = isset($rules['controller']) ? ucfirst(strtolower($rules['controller'])) : ucfirst(strtolower(self::$_default_controller));
        $action = isset($rules['action']) ? ucfirst(strtolower($rules['action'])) : ucfirst(strtolower(self::$_default_action));
        $class = implode(P_Conf_Autoload::CLASS_NAME_GLUE, array(P_Conf_Autoload::CONTROLLER_PREFIX, ucfirst(strtolower(self::$_app_div)), $controller, $action));
        if (!class_exists($class)) {
            new P_Exception_Dispatcher("invalid class={$class}", P_Conf_Globalerrno::DISPATCHER_ERROR);
            return false;
        }
        foreach ($param as $k => $v) {
            P_Http::set_get($k, $v);
        }
        return $class;
    }

    private static function _route_simple($rules) {
        if (!isset($rules['controller']) || !isset($rules['action'])) {
            new P_Exception_Dispatcher("undefined controller or action", P_Conf_Globalerrno::DISPATCHER_ERROR);
            return false;
        }
        $controller = P_Http::get($rules['controller']);
        $action = P_Http::get($rules['action']);
        if (false === $controller || false === $action) {
            new P_Exception_Dispatcher("invalid controller or action", P_Conf_Globalerrno::DISPATCHER_ERROR);
            return false;
        }
        $controller = ucfirst(strtolower($controller));
        $action = ucfirst(strtolower($action));
        $class = implode(P_Conf_Autoload::CLASS_NAME_GLUE, array(P_Conf_Autoload::CONTROLLER_PREFIX, ucfirst(strtolower(self::$_app_div)), $controller, $action));
        if (!class_exists($class)) {
            new P_Exception_Dispatcher("invalid class={$class}", P_Conf_Globalerrno::DISPATCHER_ERROR);
            return false;
        }
        return $class;
    }

    private static function _route_static($rules) {
        $request_uri = parse_url(P_Http::server('REQUEST_URI'));
        if (!isset($request_uri['path'])) {
            new P_Exception_Dispatcher('invalid request uri', P_Conf_Globalerrno::DISPATCHER_ERROR);
            return false;
        }
        $path = explode(P_Conf_Autoload::PATH_GLUE, $request_uri['path']);
        if (!strlen($path[1]) || !isset($path[2]) || !strlen($path[2]) || !isset($path[3]) || !strlen(trim($path[3])) || !isset($path[4])) {
            new P_Exception_Dispatcher('invalid params', P_Conf_Globalerrno::DISPATCHER_ERROR);
            return false;
        }
        $controller = ucfirst(strtolower($path[1]));
        $action = ucfirst(strtolower($path[2]));
        $class = implode(P_Conf_Autoload::CLASS_NAME_GLUE, array(P_Conf_Autoload::CONTROLLER_PREFIX, ucfirst(strtolower(self::$_app_div)), $controller, $action));
        if (!class_exists($class)) {
            new P_Exception_Dispatcher("invalid class={$class}", P_Conf_Globalerrno::DISPATCHER_ERROR);
            return false;
        }
        P_Http::set_get(trim($path[3]), $path[4]);
        return $class;
    }

    private static function _route_supervar($rules) {
        if (!isset($rules['supervar'])) {
            new P_Exception_Dispatcher("undefined supervar", P_Conf_Globalerrno::DISPATCHER_ERROR);
            return false;
        }
        $supervar = P_Http::get($rules['supervar']);
        if (0 !== strpos($supervar, P_Conf_Autoload::PATH_GLUE)) {
            $supervar = P_Conf_Autoload::PATH_GLUE . P_Http::get($rules['supervar']);
        }
        $request_uri = parse_url($supervar);
        if (!isset($request_uri['path'])) {
            new P_Exception_Dispatcher('invalid request uri', P_Conf_Globalerrno::DISPATCHER_ERROR);
            return false;
        }
        $path = explode(P_Conf_Autoload::PATH_GLUE, $request_uri['path']);
        if (!strlen($path[1]) || !isset($path[2]) || !strlen($path[2])) {
            new P_Exception_Dispatcher('invalid params', P_Conf_Globalerrno::DISPATCHER_ERROR);
            return false;
        }
        $controller = ucfirst(strtolower($path[1]));
        $action = ucfirst(strtolower($path[2]));
        $class = implode(P_Conf_Autoload::CLASS_NAME_GLUE, array(P_Conf_Autoload::CONTROLLER_PREFIX, ucfirst(strtolower(self::$_app_div)), $controller, $action));
        if (!class_exists($class)) {
            new P_Exception_Dispatcher("invalid class={$class}", P_Conf_Globalerrno::DISPATCHER_ERROR);
            return false;
        }
        return $class;
    }

    public static function route($diversion = '') {
        //初始化
        $app_route_rules = M::C('route_rules');
        self::$_app_div = empty($diversion) ? M::D('APP') : M::D('APP') . P_Conf_Autoload::CLASS_NAME_GLUE . $diversion;
        $route_rules = self::$_default_route_rules;
        if (is_array($app_route_rules)) {
            $route_rules = array_merge($app_route_rules, $route_rules);
        }
        //根据rules查找路由
        foreach ($route_rules as $rule) {
            if (!is_array($rule) || !isset($rule['type']) || !in_array($rule['type'], P_Conf_Route::$valid_route)) {
                continue;
            }
            if (false !== ($class = self::$rule['type']($rule))) {
                $obj = new $class;
                $obj->execute();
                break;
            }
        }
    }

}
