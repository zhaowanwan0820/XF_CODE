<?php

namespace core\exception;

/**
 * O2O超时异常
 */
class O2OBuyDiscountGoldException extends \Exception {
    public function __construct($message = '黄金券购活期黄金失败', $code = 1005, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}