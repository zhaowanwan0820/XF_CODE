<?php
/**
 * IdnoAreadyExistException.php
 * @abstract 请求参数异常
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\ExceptionBase;

class IdnoAreadyExistException extends ExceptionBase {

    public function __construct($params = '') {
        parent::__construct($params, RPCErrorCode::IDNO_AREADY_EXIST, RPCErrorCode::getErrorInfo(RPCErrorCode::IDNO_AREADY_EXIST));
    }

}