<?php

namespace libs\vfs;

class VfsException  extends \Exception{
	const VFS_SUCCESS = 0;
	const VFS_ERROR = 1;
	const VFS_NETWORK_ERROR =2;
	const VFS_READ_FAILED = 3;
	const VFS_WRITE_FAILED = 4;
	const VSF_FTP_NOT_SUPPORT = 5;
	
	static $_exceptions = array(
		self::VFS_READ_FAILED => '读取文件失败',
		self::VFS_WRITE_FAILED => '文件写入失败',
		self::VFS_NETWORK_ERROR => '网络异常',
		self::VSF_FTP_NOT_SUPPORT => 'fds: ftp服务不支持，请在php配置中打开ftp扩展',
	);
	
	public function __construct($code, $reason = '') {
		if (isset(self::$_exceptions[$code])) {
			$this->message .= self::$_exceptions[$code];
		}
		$this->message .= $reason;
	}
}