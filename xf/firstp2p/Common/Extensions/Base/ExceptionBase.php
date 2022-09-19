<?php
/* ExceptionBase.php ---
 *
 * Filename: ExceptionBase.php
 * Description: <put the file description here>
 * Author: zhounew
 * Maintainer: <put maintainers here>
 * Created: 2014-10-03 23:27
 * Version: v1.0
 *
 * Copyright (c) 2014-2020 NCFGroup.com
 * http://www.firstp2p.com
 */
namespace NCFGroup\Common\Extensions\Base;

class ExceptionBase extends \Exception {
    /**
     * ExceptionBase的构造函数
     * @param string $params
     * <code>
     * $params = array(
     *     'message' => '',
     *     'param'   => array("hello"),
     * );
     * </code>
     * @param int $errorCode
     * @param array $errorInfo
     */
    public function __construct($params, $errorCode=0, $errorInfo=array()) {
        if (is_scalar($params)) {
            $params = array("message" => (string)$params);
        }
        $params = (array) $params;
        if (!is_array($params)) {
            throw new \InvalidArgumentException(__CLASS__ . ": params is not array!");
        }

        if (!array_key_exists('message', $params)) {
            $params['message'] = '';
        }

        if (!array_key_exists('param', $params)) {
            $params['param'] = array();
        }

        $message = $params['message'];
        $paramArray = $params['param'];

        if (!is_array($paramArray)) {
            throw new \InvalidArgumentException(__CLASS__ . ": paramArray is not an Array!", AbstractErrorCodeBase::FAILED);
        }

        if (!$message) {
            $message = strval(end($errorInfo));
        }
        $actualParamCount = substr_count($message, '%s');
        $inputParamCount = count($paramArray);
        if ($actualParamCount != $inputParamCount) {
            throw new \InvalidArgumentException(__CLASS__ . sprintf(": message(%s) and parameters(%s) does not match!", $message, implode(',', $paramArray)), AbstractErrorCodeBase::FAILED);
        }

        $message = vsprintf($message, $paramArray);
        parent::__construct($message, $errorCode);
    }
}
