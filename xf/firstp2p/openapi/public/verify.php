<?php

// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------\
error_reporting(0);
defined('APP_ROOT_PATH') or define('APP_ROOT_PATH', str_replace('openapi', '', rtrim(str_replace('public', '', str_replace('\\', '/', dirname(__FILE__))), '/')));
require APP_ROOT_PATH . "system/utils/es_session.php";
es_session::start();
require APP_ROOT_PATH . "system/utils/es_image.php";
$verify = isset($_REQUEST['vname']) ? !empty($_REQUEST['vname']) ? $_REQUEST['vname'] : 'verify' : 'verify';
$rand_bg = isset($_REQUEST['rb']) ? ($_REQUEST['rb'] == '0') ? 0 : 1 : 1;
$w = isset($_REQUEST['w']) ? intval($_REQUEST['w']) : 50;
$h = isset($_REQUEST['h']) ? intval($_REQUEST['h']) : 22;
$w = $w > 100 ? 100 : $w;
$h = $h > 50 ? 50 : $h;
$l = rand(4, 7);
es_image::buildImageVerify($l, 1, 'gif', $w, $h, $verify, $rand_bg);
?>
