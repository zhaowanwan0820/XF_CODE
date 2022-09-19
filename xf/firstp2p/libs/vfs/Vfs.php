<?php
/**
 * @namespace lib\vfs
 * 本类是用来实现基于各种vfs storage engine规则进行文件分发、读取功能
 */

namespace libs\vfs;
use libs\vfs\fds\FdsFTP;
// use libs\vfs\fds\FdsNfs;
// use libs\vfs\fds\FdsStream;
// use libs\vfs\fds\FdsRsync;
use libs\vfs\VfsException;

// if (!class_exists('FdsFtp')) {
// 	var_dump(APP_ROOT_PATH.'libs/vfs/fds/FdsFtp.php');
// 	require_once APP_ROOT_PATH.'libs/vfs/fds/FdsFtp.php';
// 	require_once APP_ROOT_PATH.'libs/vfs/VfsException.php';
// }
class Vfs {
	
	static $fds = null;
	
	static $staticHost = '//static.firstp2p.com';

	private function __construct() {
	}

	/**
	 * 支持不同类型的fds服务，ftp, sftp, rsync, stream等等
	 */
	public static function getFds($fdsType = 'ftp') {
		// 线上生产测试环境
                $ftpConfig = $GLOBALS['sys_config']['vfs_ftp'];
		// 50测试环境
		// $ftpConfig = array('ftp_host' => '10.18.6.15', 'ftp_username' => 'ftp', 'ftp_password' => 'ftp');
		if (!self::$fds) {
			self::$fds = new FdsFTP($ftpConfig);
		}
		return self::$fds;
	}

	/**
	 * read 方法目前只对私有文件目录访问使用，后期如果需要stream 访问公共目录，需要增加$isPrivate属性
	 */
	public static function read($filename, $isPrivate = true) {
		if (empty($filename)) {
			throw new VfsException(VfsException::VFS_ERROR, '读取的文件不能为空！');
		}

		// http路径处理
		$filename = self::dealHTTPPath($filename);

		// 增加公共目录访问 add by fanjingwen
		$filename = $isPrivate ? '/private/' . $filename : '/pub/' . $filename;

		$fileInfo = pathinfo($filename);
		$path = $fileInfo['dirname'];
		$filename = $fileInfo['basename'];
		$streamContent = Vfs::getFds()->read($path, $filename);
		return $streamContent;
	}
	
	public static function write($filename,  $sourceFile, $isPrivate = false) {
		if (empty($filename)) {
			throw new VfsException(VfsException::VFS_ERROR, '读取的文件不能为空！');
		}
		// var_dump($isPrivate);
		$fileInfo = pathinfo($filename);
		// var_dump($fileInfo);
		$path = $fileInfo['dirname'];
		// 增加文件前缀
		if (!$isPrivate) {
			$path = '/pub/' . $path;
		} else {
			$path = '/private/' . $path;
		}
		$path = str_replace('//', '/', $path);
		$filename = $fileInfo['basename'];
		// var_dump($path, $filename);
		return Vfs::getFds()->write($path, $filename, $sourceFile);
	}

	/**
	 * [根据向ftp写入文件-new]
	 * @author <fanjingwen@ucf>
	 * @param string [$filePath]
	 * @param string [$fileName]
	 * @param resource [$fileSource]
	 * @param boolen [$isPrivate:default-false]
	 * @return boolen
	 */
	public static function writeNew($filePath, $fileName, $fileSource, $isPrivate = false)
	{
		if (empty($filePath) || empty($fileName)) {
			throw new VfsException(VfsException::VFS_ERROR, '文件名或文件路径不能为空！');
		}

		// 增加文件前缀
		$path = (true == $isPrivate) ? "/private/" : "/pub/";
		$path .= $filePath;

		$path = str_replace('//', '/', $path);
		return Vfs::getFds()->write($path, $fileName, $fileSource);
	}

	/**
	 * [删除ftp文件]
	 * @param string [$filePath:兼容以http开头的ftp服务器路径]
	 * @param boolen [$isPrivate:default-false]
	 * @return boolen
	 */
	public static function delete($filePath, $isPrivate = false)
	{
		if (empty($filePath)) {
			return false;
		}

		// 若文件不存在，默认为已经被删除
		if (-1 == ftp_size($filePath)) {
			return true;
		}

		// http路径处理
		$filePath = self::dealHTTPPath($filePath);

		// 增加文件前缀
		$path = (true == $isPrivate) ? "/private/" : "/pub/";
		$path .= $filePath;

		$path = str_replace('//', '/', $path);

		return Vfs::getFds()->delete($path);
	}

	/**
	 * [处理传过来的ftp文件路径以http开头的情况]
	 * @author <fanjingwen@ucf>
	 * @param string [$filePath]
	 * @return string [$filePath New]
	 */
	public static function dealHTTPPath($filePath)
	{
		// 如果是http协议路径，截取服务器路径部分
		if (preg_match("/^http:\/\/.*/", $filePath)) {
			$filePath = substr($filePath, 7);
			$pos = strpos($filePath, "/");
			if (false === $pos) {
				return $filePath;
			}
			$filePath = substr($filePath, $pos + 1);
		}

		return $filePath;
	}

	/**
	 * [创建文件夹]
	 * @param string [$path]
	 */
	public static function createDir($filePath, $isPrivate = false)
	{
		// 增加文件前缀
		$path = (true == $isPrivate) ? "/private/" : "/pub/";
		$path .= $filePath;

		Vfs::getFds()->createDir($path);
	}
}