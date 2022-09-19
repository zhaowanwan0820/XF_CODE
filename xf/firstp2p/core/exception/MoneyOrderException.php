<?php

namespace core\exception;

/**
 * MoneyOrderException
 *
 * @packaged core\exception
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 **/
class MoneyOrderException extends \Exception
{
    const CODE_ORDER_EXIST = 1001;
    public function __construct($message = '', $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
