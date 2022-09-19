<?php
ini_set('display_errors', 0);
error_reporting(0);
class AttachmentAction extends CommonAction{
	public function index() {
		$file = trim($_GET['file']);
		$path = pathinfo($file);
		if(empty($file)) {
			return false;
		}
		require_once APP_ROOT_PATH . '/libs/vfs/VfsHelper.php';
		$streamContent = libs\vfs\VfsHelper::image($file, true);
		if ($path['extension'] == 'jpg' || $path['extension'] == 'jpeg') {
			header('content-type:image/jpeg');
		} else  {
			header('content-type:application/octet-stream');
			header("Content-Disposition:attachment;filename=". $path['basename']);
		}
		echo $streamContent;
		return true;
	}
}