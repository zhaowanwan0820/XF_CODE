<?php

namespace core\service\candyactivity;

use core\service\BaseService;
use libs\utils\Logger;

class CandyActivityService extends BaseService {
    private static $funcMap = array(
        'activityCreateByType' => array('token','userId','sourceType','sourceValue','sourceValueExtra'),

    );

    /**
     * Handles calls to static methods.
     *
     * @param string $name Method name
     * @param array $params Method parameters
     * @return mixed
     */
    public static function __callStatic($name, $params) {

        if (!array_key_exists($name, self::$funcMap)) {
            self::setError($name.' method not exist', 1);
            return false;
        }
        $args = array();
        $argNames = self::$funcMap[$name];
        foreach ($params as $key=>$arg) {
            if (!empty($argNames[$key])) {
                $args[$argNames[$key]] = $arg;
            }
        }
        return self::rpc('ncfwx', 'CandyActivity/'.$name, $args);
    }
}
