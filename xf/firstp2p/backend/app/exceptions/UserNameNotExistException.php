<?php
/**
 * UserNameNotExist.php
 * @abstract 用户名不存在异常
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\ExceptionBase;

class UserNameNotExistException extends ExceptionBase {

    public function __construct($params = '') {
        parent::__construct($params, RPCErrorCode::USERNAME_NOT_EXIST, RPCErrorCode::getErrorInfo(RPCErrorCode::USERNAME_NOT_EXIST));
    }
}