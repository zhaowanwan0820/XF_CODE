<?php
namespace libs\vfs;

use libs\vfs\Vfs;

class VfsHelper {
	public static function image($picPath, $private = false) {
		if ($private) {
			// $content = Vfs::read($picPath);
			// $fileSizeInfo = getimagesize(APP_STATIC_HOST . $picPath);
			// var_dump($picPath);
			$streamContent = Vfs::read($picPath);
			return $streamContent;
		} else {
            return 'http:'.(isset($GLOBALS['sys_config']['STATIC_HOST']) ? $GLOBALS['sys_config']['STATIC_HOST'] : Vfs::$staticHost) . str_replace('//' , '/', '/' .$picPath);
		}
	}
	
	public static function remoteImage($url) {
		$fileSizeInfo = getimagesize($url);
		if (!headers_sent()) {
			header('content-type:' . $fileSizeInfo['mime']);
		}
		$contents = null;
		do {
			$contents = file_get_contents($url);
			if ($contents || $icounter ++ >= 1) {
				break;
			}
		} while (!$contents);
		if (empty($contents)) {
			$contents = defaultImage(true);
		}
		echo $contents;
	}
}
