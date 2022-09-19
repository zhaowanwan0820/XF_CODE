<?php
/**
 * sftp 服务
 * upload -- 上传
 * download -- 下载
 * 登陆方式目前仅支持 password
 *
 * 使用示例:
 * try {
 *  $sftp = new Sftp("host","username","passwordt",22);
 *  //$sftp->setMaxReadSize(10485760);
 *  $res = $sftp->upload("./aa.sql","/tmp/aa.sql");
 * }catch (\Exception $e) {
 *   echo $e->getMessage() . "\n";
 * }
 *
 * @author jinhaidong
 * @date 2015-8-3 19:08:30
 */

namespace libs\utils;

class Sftp {

    /**
     * 每次读取的最大字节数 默认10M
     */
    private $maxReadSize = 10485760;

    /**
     * sftp 链接
     */
    private $connection;

    /**
     * 认证方式
     */
    private $authType = 'password';

    /**
     * sftp
     */
    private $sftp;

    public function __construct($host,$username, $password,$port=22) {
        if(!function_exists('ssh2_connect')) {
            throw new \Exception("ssh2 are not available");
        }
        $this->connection = ssh2_connect($host, $port);
        if (! $this->connection) {
            throw new \Exception("Could not connect to $host on port $port.");
        }
        $this->login($username, $password);
    }

    /**
     * 设置每次读取的最大字节数
     * @param $size 单位 bytes
     */
    public function setMaxReadSize($size) {
        $this->maxReadSize = $size;
    }

    /**
     * 获取每次读取的最大字节数
     */
    public function getMaxLoopSize() {
        return $this->maxReadSize;
    }

    /**
     * 设置认证方式
     * @authType 认证方式
     */
    public function setAuthType($authType) {
        if(!in_array($authType,array('password','pubkey'))) {
            throw new \Exception("The valid auth type is password or pubkey");
        }
        $this->authType = $authType;
    }

    /**
     * 获取当前认证方式
     */
    public function getAuthType() {
        return $this->authType;
    }

    /**
     * @param $username sftp 账号名称
     * @param $password sftp 账目密码
     */
    private function login($username, $password) {
        if (!ssh2_auth_password($this->connection, $username, $password)) {
            throw new \Exception("Could not authenticate with username $username " .
                "and password $password.");
        }

        $this->sftp = ssh2_sftp($this->connection);
        if (!$this->sftp) {
            throw new \Exception("Could not initialize SFTP subsystem.");
        }
    }

    /**
     * 下载远程sftp服务器上的文件
     *
     * @param $remote_file
     * @param $local_file
     */
    public function download($remote_file, $local_file) {
        if (!file_exists("ssh2.sftp://{$this->sftp}$remote_file")) {
            throw new \Exception("file not exist: $remote_file");
        }
        $filesize = filesize("ssh2.sftp://{$this->sftp}$remote_file");
        if($filesize > $this->maxReadSize) {
            $stream = fopen("ssh2.sftp://{$this->sftp}$remote_file", 'r');
            if (!$stream) {
                throw new \Exception("Could not open file: $remote_file");
            }
            $this->batchWrite($local_file,$stream);
            fclose($stream);
        }else{
            $contents = file_get_contents("ssh2.sftp://{$this->sftp}$remote_file");
            file_put_contents ($local_file, $contents);
        }
    }

    /**
     * 向远程sftp服务器上传文件
     *
     * @param $local_file
     * @param $remote_file
     */
    public function upload($local_file, $remote_file) {
        $filesize = filesize($local_file);
        if($filesize > $this->maxReadSize) {
            $fileStream = fopen($local_file,'r');
            if(!$fileStream) {
                throw new \Exception("Could not open file: $local_file");
            }
            $this->batchWrite("ssh2.sftp://{$this->sftp}$remote_file", $fileStream);
            fclose($fileStream);
        }else{
            $stream = fopen("ssh2.sftp://{$this->sftp}$remote_file", 'w');
            if(! $stream) {
                throw new \Exception("Could not open file: $remote_file");
            }
            $data_to_send = file_get_contents($local_file);
            if($data_to_send === false) {
                throw new \Exception("Could not open local file: $local_file.");
            }
            if(fwrite($stream, $data_to_send) === false) {
                throw new \Exception("Could not send data from file: $local_file.");
            }
            fclose($stream);
        }
    }

    /**
     * 批次写入 防止file_get_contents一次加载
     * 导致 Memory Overflow
     *
     * @param $file 待写入的文件
     * @param $fileStream 已经打开文件resource
     */
    private function batchWrite($file, $fileStream) {
        if(!$fileStream) {
            throw new \Exception("Could not open file stream");
        }
        $fp = fopen($file,'w');
        if(!$fp) {
            throw new \Exception("Could not open file ".$file);
        }
        while(!feof($fileStream)) {
            $content = fread($fileStream,$this->maxReadSize);
            fwrite($fp,$content);
        }
        fclose($fp);
    }

    /**
     * 从远程sftp服务器删除文件
     *
     * @param $remote_file
     */
    public function deleteFile($remote_file){
        $sftp = $this->sftp;
        unlink("ssh2.sftp://$sftp$remote_file");
    }
}
