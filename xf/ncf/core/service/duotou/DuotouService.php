<?php

namespace core\service\duotou;

use core\service\BaseService;

/**
 * 优惠码相关接口
 */
class DuotouService extends BaseService
{
    private static $funcMap = array(
        'callByObject' => array('object')
    );


    /**
     * Handles calls to static methods.
     *
     * @param string $name Method name
     * @param array $params Method parameters
     * @return mixed
     */
    public static function __callStatic($name, $params)
    {
        if (!array_key_exists($name, self::$funcMap)) {
            self::setError('invalid method', 1);
            return false;
        }

        $args = array();
        $argNames = self::$funcMap[$name];

        foreach ($params as $key=>$arg) {
            if (!empty($argNames[$key])) {
                $args[$argNames[$key]] = $arg;
            }
        }

        $response = self::rpc('duotou', 'index/'.$name, $args,false,5);
        return array(
            'errCode' => self::getErrorCode(),
            'errMsg' => self::getErrorMsg(),
            'data' => $response,
            );
    }

    public function call($object){
        return self::callByObject($object);
    }
}