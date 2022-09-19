<?php
/**
 * UserAreadyAuthException.php
 * @abstract 请求参数异常
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\ExceptionBase;

class UserAreadyAuthException extends ExceptionBase {

    public function __construct($params = '') {
        parent::__construct($params, RPCErrorCode::USER_AREADY_AUTH, RPCErrorCode::getErrorInfo(RPCErrorCode::USER_AREADY_AUTH));
    }

}