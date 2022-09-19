<?php

namespace core\exception;

/**
 * UserThirdBalanceException
 *
 * @packaged default
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 **/
class UserThirdBalanceException extends \Exception
{
    public function __construct($message = '', $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
