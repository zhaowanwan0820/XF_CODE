<?php

/**
 * ModelSaveException.php
 * @abstract model保存异常
 * @date   2015-10-26
 */
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\ExceptionBase;

class ModelSaveException extends ExceptionBase {

    public function __construct($params = '') {
        parent::__construct($params, RPCErrorCode::MODEL_SAVE_EXCEPTION, RPCErrorCode::getErrorInfo(RPCErrorCode::MODEL_SAVE_EXCEPTION));
    }

}
