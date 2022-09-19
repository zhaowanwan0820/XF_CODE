<?php

namespace core\exception;

use NCFGroup\Protos\Life\Enum\ErrorCode;

/**
 * Life异常类
 **/
class LifeException extends \Exception {
    // rpc重试，对于需要进行重试的错误码，这个错误码不要随便改变
    const RPC_RETRY_AGAIN_LATER = 16;
    const CODE_RPC_TIMEOUT = 1001;
    const CODE_LIFE_ERROR = 1002;
    const CODE_P2P_ERROR = 1003;
    const CODE_P2P_TIPS = 1004;
    const CODE_PARAM_ERROR = 1005;

    public function __construct($message = '', $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    /*
     * 抛出异常
     * @param string
     * @throws \Exception
     */
    public static function exception($errorCode) {
        if (!is_numeric($errorCode)) {
            throw new \Exception($errorCode, ErrorCode::PARAMETERS_ERROR);
        }
        $args = func_get_args();
        $msg = array_shift($args);
        $errMsg = isset(ErrorCode::$errMsg[$errorCode]) ? ErrorCode::$errMsg[$errorCode] : ErrorCode::$errMsg[ErrorCode::PARAMETERS_ERROR];
        array_unshift($args, $errMsg);
        $errMsg = call_user_func_array('sprintf', $args);
        throw new \Exception($errMsg, $errorCode);
    }
}