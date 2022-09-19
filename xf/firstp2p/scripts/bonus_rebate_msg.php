<?php
require(dirname(__FILE__) . '/../app/init.php');
require_once(APP_ROOT_PATH.'system/libs/msgcenter.php');
$mobile = $argv[1];
$userId = $argv[2];
$userRealName = $argv[3];
$money = $argv[4];
$tpl = $argv[5];
$title = $argv[6];
$useLimitDay = $argv[7];
$params = array($userRealName, $money, ($useLimitDay*24).'小时');
$msgcenter = new \Msgcenter();
$msgcenter->setMsg($mobile, $userId, $params, $tpl, $title);
$msgcenter->save();
