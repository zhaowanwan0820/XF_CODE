<?php

define('TASK_APP_NAME', 'p2p');
require dirname(dirname(dirname(__DIR__))) . '/Common/Phalcon/Bootstrap.php';
$di = new \Phalcon\DI\FactoryDefault\CLI();
$bootstrap = new \NCFGroup\Common\Phalcon\Bootstrap(dirname(dirname((__DIR__))));
$bootstrap->execTaskforTest(array(), $di);
