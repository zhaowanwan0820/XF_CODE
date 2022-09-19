<?php
/**
 * UserAgeException.php
 * @abstract 请求参数异常
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\ExceptionBase;

class UserAgeException extends ExceptionBase {

    public function __construct($params = '') {
        parent::__construct($params, RPCErrorCode::AGE_ERROR, RPCErrorCode::getErrorInfo(RPCErrorCode::AGE_ERROR));
    }

}