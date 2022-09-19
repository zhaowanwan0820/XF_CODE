<?php

/**
 * @author zhanglei5@ucfgroup.com
 * @encode UTF-8编码
 */
require_once 'PushFactory.php';
class AndroidPushFactory extends PushFactory
{
    public function __construct() {
        $this->_api_key = '';
        $this->_secret_key = '';
    }

    public function createPush() {
        $push = new Android($this->_api_key,$this->_secret_key);
        return $push;
    }

}
