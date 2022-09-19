<?php

namespace NCFGroup\Common\Extensions\Exceptions;

use NCFGroup\Common\Extensions\Base\ExceptionBase;
use NCFGroup\Common\Extensions\Base\AbstractErrorCodeBase;

class RpcSignatureRestrictionException extends ExceptionBase
{
    public function __construct($params) {
        parent::__construct($params, AbstractErrorCodeBase::RPC_SIGNATURE_RESTRICTION,
            AbstractErrorCodeBase::getErrorInfo(AbstractErrorCodeBase::RPC_SIGNATURE_RESTRICTION));
    }
}