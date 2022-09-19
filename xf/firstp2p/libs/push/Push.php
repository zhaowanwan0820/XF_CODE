<?php

/**
 * @author zhanglei5@ucfgroup.com
 * @encode UTF-8编码
 * namespace libs\push;
 */

class Push
{
    protected  $_api_key;
    protected $_secret_key;

    public function __construct($api_key,$secret_key) {
        $this->_api_key = $api_key;
        $this->_secret_key = $secret_key;
    }
}
