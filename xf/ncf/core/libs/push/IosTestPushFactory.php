<?php

/**
 * @author zhanglei5@ucfgroup.com
 * @encode UTF-8编码
 */
require_once 'PushFactory.php';
require_once 'IosTest.php';
class IosTestPushFactory extends PushFactory
{
    public function __construct() {
        $this->_api_key = 'pWBkRu2RscBTN0Ni5hVZYHFX';
        $this->_secret_key = 'b82xtmY07nmN5DNuBhtPaCralqWz4e9D';
    }

    public function createPush() {
        $push = new IosTest($this->_api_key,$this->_secret_key);
        return $push;
    }

}
