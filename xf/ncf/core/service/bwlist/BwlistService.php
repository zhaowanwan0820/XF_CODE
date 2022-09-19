<?php

namespace core\service\bwlist;

use core\service\BaseService;

class BwlistService  extends BaseService
{
    private  static  $funcMap =  array(
            'inList' => array('type_key', 'value','value2','value3'),
            );

    public static function __callStatic($name, $params)
    {
        if (!array_key_exists($name, self::$funcMap)) {
            self::setError('invalid method', 1);
            return false;
        }
        $args = array();
        $argNames = self::$funcMap[$name];

        foreach ($params as $key => $arg) {
            if (!empty($argNames[$key])) {
                $args[$argNames[$key]] = $arg;
            }
        }
        return self::rpc('ncfwx', 'bwlist/'.$name, $args);
    }

}
