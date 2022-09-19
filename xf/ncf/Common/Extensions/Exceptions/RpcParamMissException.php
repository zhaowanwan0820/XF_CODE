<?php
namespace NCFGroup\Common\Extensions\Exceptions;

use NCFGroup\Common\Extensions\Base\ExceptionBase;
use NCFGroup\Common\Extensions\Base\AbstractErrorCodeBase;

class RpcParamMissException extends ExceptionBase
{
    public function __construct($params) {
        parent::__construct($params, AbstractErrorCodeBase::RPC_PARAM_MISS,
            AbstractErrorCodeBase::getErrorInfo(AbstractErrorCodeBase::RPC_PARAM_MISS));
    }
}