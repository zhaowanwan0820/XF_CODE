<?php
/**
 * UserNameNotExist.php
* @abstract 请求过于频繁异常
* @author zhaohui <zhaohui3@ucfgroup.com>
*/
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\ExceptionBase;

class RequestFrequentException extends ExceptionBase {

    public function __construct($params = '') {
        parent::__construct($params, RPCErrorCode::REQUEST_FREQUENT, RPCErrorCode::getErrorInfo(RPCErrorCode::REQUEST_FREQUENT));
    }
}