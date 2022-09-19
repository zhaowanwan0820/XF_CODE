<?php

namespace core\service;

use NCFGroup\Common\Library\ApiService;

/**
 * services 基类
 **/
class BaseService extends ApiService {
    public function getPhalconRpc($rpcName) {
        if(!isset($GLOBALS[$rpcName])) {
            \libs\utils\PhalconRPCInject::init();
        }
        return $GLOBALS[$rpcName];
    }
} // END class BaseService