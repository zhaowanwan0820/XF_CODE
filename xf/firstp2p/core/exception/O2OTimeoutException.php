<?php

namespace core\exception;

/**
 * O2O超时异常
 */
class O2OTimeoutException extends \Exception {
    public function __construct($message = '系统繁忙，请稍后再试', $code = 1001, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}