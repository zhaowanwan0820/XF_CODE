<?php

namespace core\exception;

/**
 * WeixinInfo service
 *
 * @packaged default
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 **/
class PassportException extends \Exception
{
    // 鉴权失败
    const AUTH_FAIL = 1001;
    // 本地检查失败，需二次校验
    const LOCAL_CHECK_FAIL = 1002;

    public function __construct($message = '', $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
