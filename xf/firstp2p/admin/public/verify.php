<?php 
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------\
ini_set("display_errors", 'on');

error_reporting(E_ALL);
ini_set("display_errors", 'on');

defined('APP_ROOT_PATH') or define('APP_ROOT_PATH', str_replace(array('public','admin','//'), array('','','/'), rtrim(str_replace('\\', '/', dirname(__FILE__)), '/')));
require APP_ROOT_PATH."system/utils/es_session.php";
es_session::start();

require APP_ROOT_PATH."system/utils/es_image.php";
$verify = isset($_REQUEST['vname']) ? !empty($_REQUEST['vname']) ? $_REQUEST['vname'] : 'verify' : 'verify';
$w = isset($_REQUEST['w']) ? intval($_REQUEST['w']) : 50;
$h = isset($_REQUEST['h']) ? intval($_REQUEST['h']) : 22;
es_image::buildImageVerify(4,1,'gif',$w,$h,$verify);
?>