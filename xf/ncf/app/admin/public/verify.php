<?php 
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------\
error_reporting(0);

define('ROOT_PATH', realpath(dirname(__FILE__).'/../../../').DIRECTORY_SEPARATOR);
require(ROOT_PATH.'core/framework/init.php');

es_session::start();
FP::import('libs.utils.es_image');
$verify = isset($_REQUEST['vname']) ? !empty($_REQUEST['vname']) ? $_REQUEST['vname'] : 'verify' : 'verify';
$w = isset($_REQUEST['w']) ? intval($_REQUEST['w']) : 50;
$h = isset($_REQUEST['h']) ? intval($_REQUEST['h']) : 22;
es_image::buildImageVerify(4,1,'gif',$w,$h,$verify);
?>
