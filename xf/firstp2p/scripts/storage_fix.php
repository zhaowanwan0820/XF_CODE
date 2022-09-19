<?php
/**
 * #文件存储隐私文件搬迁
 * #0 0 0 0 0 cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php bid_load.php
 * @author qunqiang 2014-06-09 14:10:46
 */
require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../system/utils/es_mail.php';
if(PHP_SAPI != 'cli') exit;//只允许命令行访问
error_reporting(E_ALL);
ini_set('display_errors',1);

$commands = array();
// 用户修改银行卡文件处理
$sql = "SELECT image_id FROM firstp2p_user_bankcard_audit";
$dbo = $GLOBALS['db'];
$res = $dbo->getAll($sql);
if (is_array($res)) {
	foreach ($res as $k => $row) {
		$sql_image = "SELECT `attachment` FROM firstp2p_attachment WHERE id = '{$row['image_id']}'";
		// echo $sql_image . "\n";
		$imgPATH = $dbo->getOne($sql_image);
		// var_dump($imgPATH);
		$commandGroup = generateOpCommand($imgPATH);
		$commands[]  = implode("\n", $commandGroup);
	}
}

// 港澳台用户上传身份证图片
$sql = "SELECT `file` FROM firstp2p_user_passport";
$res = $dbo->getAll($sql);
if (is_array($res)) {
	foreach ($res as $k => $row) {
		$files = unserialize($row['file']);
		if (is_array($files)) {
			foreach ($files as $file) {
				$file = str_replace('./', ']);', $file);
				$commandGroup = generateOpCommand($file);
				$commands[] = implode("\n", $commandGroup);
			}
		}
	}
}

echo implode("\n", $commands);



function generateOpCommand($path) {
	$pathinfo = pathinfo($path);
	// var_dump($pathinfo);
	$target = $pathinfo['dirname'];
	$path = $path;
	$opCreateTarget = "mkdir -p private/{target}";
	$opMoveToTarget = "mv pub/{path} private/{target}/";
	$opDeleteSource = "rm -rf pub/{target}";
	
	return array(
		str_replace('{target}', $target, $opCreateTarget),
		str_replace(array('{path}','{target}'), array($path, $target), $opMoveToTarget),
		str_replace('{target}', $target, $opDeleteSource),
	);
}
