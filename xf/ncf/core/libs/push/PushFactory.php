<?php

/**
 * @author zhanglei5@ucfgroup.com
 * @encode UTF-8编码
 */

abstract class PushFactory
{
    protected  $_api_key;
    protected $_secret_key;
    abstract public function createPush();

}
