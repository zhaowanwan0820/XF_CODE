<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

// 前后台加载的系统配置文件


// 加载数据库中的配置与数据库配置
if(file_exists(APP_ROOT_PATH.'public/db_config.php'))
{
	$db_config	=	require APP_ROOT_PATH.'public/db_config.php';
}

//加载系统配置信息
if(file_exists(APP_ROOT_PATH.'public/sys_config.php'))
{
	$db_conf	=	require APP_ROOT_PATH.'public/sys_config.php';
}

//加载系统信息
if(file_exists(APP_ROOT_PATH.'public/version.php'))
{
	$version	=	require APP_ROOT_PATH.'public/version.php';
}

//加载时区信息
if(file_exists(APP_ROOT_PATH.'public/timezone_config.php'))
{
	$timezone	=	require APP_ROOT_PATH.'public/timezone_config.php';
}

//加载字典
if(file_exists(APP_ROOT_PATH.'public/sys_dictionary.php'))
{
	$dict	=	require APP_ROOT_PATH.'public/sys_dictionary.php';
}

//加载oauth配置信息
if(file_exists(APP_ROOT_PATH.'public/sys_oauth.php'))
{
	$oauth = require APP_ROOT_PATH.'public/sys_oauth.php';
}

//加载adviser配置信息
if(file_exists(APP_ROOT_PATH.'public/sys_adviser.php'))
{
	$adviser = require APP_ROOT_PATH.'public/sys_adviser.php';
}

//加载id5配置信息
if(file_exists(APP_ROOT_PATH.'public/sys_id5.php'))
{
	$id5 = require APP_ROOT_PATH.'public/sys_id5.php';
}

if(is_array($db_config))
$config = array_merge($db_conf,$db_config,$version,$timezone,$dict,$oauth,$adviser,$id5);
elseif(is_array($db_conf))
$config = array_merge($db_conf,$version,$timezone,$dict,$oauth,$adviser,$id5);
else
$config = array_merge($version,$timezone,$dict,$oauth,$adviser,$id5);
return $config;
?>