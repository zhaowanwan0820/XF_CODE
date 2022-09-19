<?php

namespace core\exception;

/**
 * WeixinInfo service
 *
 * @packaged default
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 **/
class O2OException extends \Exception
{
    const CODE_RPC_TIMEOUT = 1001;
    const CODE_O2O_ERROR = 1002;
    const CODE_P2P_ERROR = 1003;
    const CODE_P2P_TIPS = 1004;
    const CODE_PARAM_ERROR = 1005;

    public function __construct($message = '', $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
