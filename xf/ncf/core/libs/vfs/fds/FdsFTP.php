<?php
namespace libs\vfs\fds;

use libs\vfs\IVfsEngine;
use libs\vfs\VfsException;
use libs\utils\Logger;

class FdsFTP implements IVfsEngine {

    static $_ftpHandler;

    public function __construct($config = array()) {
        $host = $port = $username = $password = '';
        if (isset($config['ftp_host'])) {
            $host = trim($config['ftp_host']);
        }
        if (isset($config['ftp_port'])) {
            $port = trim($config['ftp_port']);
        }
        if (isset($config['ftp_username'])) {
            $username = trim($config['ftp_username']);
        }
        if (isset($config['ftp_password'])) {
            $password = trim($config['ftp_password']);
        }

        if (!function_exists('ftp_connect')) {
            throw new VfsException(VfsException::VSF_FTP_NOT_SUPPORT);
        }
        self::$_ftpHandler = ftp_connect($host);
        if (!is_resource(self::$_ftpHandler)) {
            throw new VfsException(VfsException::VFS_ERROR, '无法打开fds流，请与管理员联系。');
        }
        if (!ftp_login(self::$_ftpHandler, $username, $password)) {
            throw new VfsException(VfsException::VFS_ERROR, sprintf('无法登录账户%s，请与管理员联系。', $username));
        }
    }

    public function read($filePath, $filename) {
        // rewind
        $this->ftp_chroot(self::$_ftpHandler);
        if (!ftp_chdir(self::$_ftpHandler, $filePath)) {
            // 读取模式不创建目录，一旦失败返回
            $this->_createNestedDir($filePath, false);
        }
        $tmpFile = '/tmp/'. microtime(true);
        $res = ftp_get(self::$_ftpHandler,  $tmpFile, $filename, FTP_BINARY);
        // var_dump($res);
        if (!$res) {
            // fclose($fHandle);
            $data = array(
                'readPath' => $filePath,
                'readName' => $filename,
                'tempFile' => $tmpFile,
                'reason' => 'fds服务不可用，无法读取远程文件',
                'creatime' => date('Y-m-d H:i:s'),
            );
            Logger::wLog($data, null, null, 'fds-exception-'.date('Y-m-d') . '.log');
            throw new VfsException(VfsException::VFS_READ_FAILED);
        }
        $streamContent = null;
        $icounter = 0;
        // 尝试两次获取stream
        do {
            $streamContent = '';
            $streamContent = file_get_contents($tmpFile);
            if ($icounter ++ >= 1 || $streamContent) {
                break;
            }
            usleep(200);
        }while (!$streamContent);
        // 先清理收尾工作，再跳出
        unlink($tmpFile);
        // 记日志，抛异常
        if (!$streamContent) {
            $data = array(
                'readPath' => $filePath,
                'readName' => $filename,
                'tempFile' => $tmpFile,
                'reason' => '无法获取文件内容在尝试2次失败以后',
                'creatime' => date('Y-m-d H:i:s'),
            );
            Logger::wLog($data, null, null, 'fds-exception-'.date('Y-m-d') . '.log');
            throw new VfsException(VfsException::VFS_READ_FAILED);
        }
        return $streamContent;
    }

    /**
     * ftp同步文件
     */
    public function write( $filePath, $filename, $sourceFile) {
            // rewind
            $this->ftp_chroot(self::$_ftpHandler);
            if (!ftp_chdir(self::$_ftpHandler, $filePath)) {
                $this->_createNestedDir($filePath);
            }
            $mode = FTP_BINARY;
            $res = false;
            $icounter = 0;
            // 尝试2次写vfs
            do {
                $res = ftp_put(self::$_ftpHandler, $filename,  $sourceFile, $mode);
                if ($res) {
                    return true;
                }
                if ($icounter ++ >= 1) {
                    break;
                }
                usleep(200);
            }while (!$res);
            return false;
    }

    /**
     * 下载ftp服务器上的文件
     * @param $remoteFile 远程文件路径
     * @param $localFile 本地文件路径
     */
    public function download($remoteFile, $localFile) {
        // turn passive mode on
        ftp_pasv(self::$_ftpHandler, TRUE);

        $filePath = dirname($remoteFile);
        $filename = basename($remoteFile);
        if (!ftp_chdir(self::$_ftpHandler, $filePath)) {
            throw new VfsException(VfsException::VFS_ERROR, sprintf('不能更换目录, filePath: %s', $filePath));
        }

        $res = ftp_get(self::$_ftpHandler,  $localFile, $filename, FTP_BINARY);
        if (!$res) {
            throw new VfsException(VfsException::VFS_ERROR, sprintf('ftp服务不可用，无法读取远程文件, remoteFile: %s', $remoteFile));
        }
    }


    private function _createNestedDir($filePath, $autoCreate = true) {
        if (empty($filePath)) {
            throw new VfsException(VfsException::VFS_ERROR, '目标文件的路径不能为空');
        }
        $pathExists = ftp_chdir(self::$_ftpHandler, $filePath);
        if ($pathExists)
        {
            return true;
        }
        // 如果目录不存在并且不自动创建目录，抛出异常
        if (!$pathExists && !$autoCreate){
            throw new VfsException(VfsException::VFS_ERROR, '目标文件的路径不能为空');
        }
        $parts = explode('/',trim($filePath, '/'));
        foreach($parts as $part){
            if(!ftp_chdir(self::$_ftpHandler, $part)){
                ftp_mkdir(self::$_ftpHandler, $part);
                ftp_chdir(self::$_ftpHandler, $part);
                //ftp_chmod(self::$_ftpHandler, 0777, $part);
            }
        }
        ftp_mkdir(self::$_ftpHandler, $filePath);
    }

    private function ftp_chroot($ftpHandler) {
        $curPath = ftp_pwd($ftpHandler);
        $curPath = trim($curPath, '/');
        $parts = explode('/', $curPath);
        $iC = 0;
        $depth = count($parts);
        for(;$iC < $depth; ++ $iC) {
            ftp_cdup($ftpHandler);
        }
    }

    public function __destruct() {
        ftp_close(self::$_ftpHandler);
    }

    /**
     * [删除ftp上指定的文件]
     * @author <fanjingwen@ucf>
     * @param string [要删除的文件_eg. test/img.png]
     * @return boolen
     */
    public function delete($filePath)
    {
        if (empty($filePath)) {
            return false;
        }

        // 删除ftp文件
        return ftp_delete(self::$_ftpHandler, $filePath);
    }


    /**
     * [对外提供创建dir接口]
     * @param string [$filePath]
     */
    public function createDir($filePath)
    {
        if (empty($filePath)) {
            throw new VfsException(VfsException::VFS_ERROR, '目标文件的路径不能为空');
        }

        // 获取ftp服务器权限
        $this->ftp_chroot(self::$_ftpHandler);

        // 不存在，就创建
        if (!ftp_chdir(self::$_ftpHandler, $filePath)) {
            $this->_createNestedDir($filePath);
        }
    }
}
