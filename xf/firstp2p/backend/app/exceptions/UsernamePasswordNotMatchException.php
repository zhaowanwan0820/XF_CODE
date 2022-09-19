<?php
/**
 * UserNameNotExist.php
* @abstract 用户名密码不匹配异常
* @author zhaohui <zhaohui3@ucfgroup.com>
*/
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\ExceptionBase;

class UsernamePasswordNotMatchException extends ExceptionBase {

    public function __construct($params = '') {
        parent::__construct($params, RPCErrorCode::USERNAME_PASSWORD_NOT_MATCH, RPCErrorCode::getErrorInfo(RPCErrorCode::USERNAME_PASSWORD_NOT_MATCH));
    }
}