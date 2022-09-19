<?php

/**
 * @author zhanglei5@ucfgroup.com
 * @encode UTF-8编码
 * namespace libs\push;
use libs\push\PushFactory;
 */

include_once 'PushFactory.php';
include 'AndroidTest.php';
class AndroidTestPushFactory extends PushFactory
{
    public function __construct() {
        $this->_api_key = 'pWBkRu2RscBTN0Ni5hVZYHFX';
        $this->_secret_key = 'b82xtmY07nmN5DNuBhtPaCralqWz4e9D';
    }

    public function createPush() {
        $push = new AndroidTest($this->_api_key,$this->_secret_key);
        return $push;
    }

}
