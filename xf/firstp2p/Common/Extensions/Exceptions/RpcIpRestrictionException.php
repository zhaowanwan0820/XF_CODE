<?php
namespace NCFGroup\Common\Extensions\Exceptions;

use NCFGroup\Common\Extensions\Base\AbstractErrorCodeBase;
use NCFGroup\Common\Extensions\Base\ExceptionBase;

class RpcIpRestrictionException extends ExceptionBase
{

    public function __construct($params) {
        parent::__construct($params, AbstractErrorCodeBase::RPC_IP_RESTRICTION,
            AbstractErrorCodeBase::getErrorInfo(AbstractErrorCodeBase::RPC_IP_RESTRICTION));
    }
}
