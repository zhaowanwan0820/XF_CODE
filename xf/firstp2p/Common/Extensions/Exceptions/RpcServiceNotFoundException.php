<?php

namespace NCFGroup\Common\Extensions\Exceptions;

use NCFGroup\Common\Extensions\Base\ExceptionBase;
use NCFGroup\Common\Extensions\Base\AbstractErrorCodeBase;

class RpcServiceNotFoundException extends ExceptionBase
{
    public function __construct($params) {
        parent::__construct($params, AbstractErrorCodeBase::RPC_SERVICE_NOT_FOUND,
            AbstractErrorCodeBase::getErrorInfo(AbstractErrorCodeBase::RPC_SERVICE_NOT_FOUND));
    }
}