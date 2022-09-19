<?php
/**
 * FastDfsModel
 *
 * @date 2014-04-01
 * @author wangfei5<wangfei5@ucfgroup.com>
 */
namespace core\dao;

use libs\utils\Logger;

class FastDfsModel{

    private $tracker;
    private $dfs;
    private $connectErr;

    public function getError(){
        $str =  "fastdfs errno: " . fastdfs_get_last_error_no() . ", error info: " . fastdfs_get_last_error_info().", ext: ".$this->connectErr;
        return $str;
    }

    private function connect(){
        $this->dfs = new \FastDFS();
        // 连接tracker。获取实体的地址
        $this->tracker = $this->dfs->tracker_get_connection();
        if(empty($this->tracker)){
            $this->connectErr = "failed to connect to fdfs";
            return false; 
        }
    }

    public function __construct(){
        $this->connect();
    }

    public function __destruct(){
        $this->dfs->close();
        if(empty($this->tracker)){
            Logger::wLog("关闭失败!" . PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH. "/logger/" ."contractSign_" . date('Y_m_d') .'.log');
        }else{
            Logger::wLog("已关闭!" . PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH. "/logger/" ."contractSign_" . date('Y_m_d') .'.log');
        }
    }

    /**
    *   把文件写到里面
    */
    public function write($filePath){
        if(empty($filePath)){
            return false;
        }
        $file_info = $this->dfs->storage_upload_by_filename($filePath);
        if(empty($file_info)){
            $err = $this->getError();
            // logloglog
            return false;
        }
        $groupName = $file_info['group_name'];
        $fileName = $file_info['filename'];
        return array('group_name'=>$groupName,'file_name'=>$fileName);
    }

    /**
    *   直接把二进制流写到里面
    */
    public function writeFileContent($fileContent,$ext='pdf'){
        if(empty($fileContent)){
            return false;
        }
        $file_info = $this->dfs->storage_upload_by_filebuff($fileContent, $ext);
        if(empty($file_info)){
            return false;
        }
        $groupName = $file_info['group_name'];
        $fileName = $file_info['filename'];
        return array('group_name'=>$groupName,'file_name'=>$fileName);
    }
    /**
    *   文件是否存在
    */
    public function exist($group_name,$remote_filename){
        $ret = $this->dfs->storage_file_exist($group_name, $remote_filename);
        return $ret;
    }

    /**
    *   读出来的是文件的内容
    */
    public function readTobuff($groupName,$fileName){
        $fileContent = $this->dfs->storage_download_file_to_buff($groupName, $fileName);
        if(!empty($fileContent)){
            return $fileContent;
        }else{
            return false;
        }
    }

    /**
    *   读出来的直接就写到固定的目录了
    */
    public function readToFile($groupName,$fileName,$localFileName){
        $ret = $this->fdfs->storage_download_file_to_file($groupName, $fileName, $localFileName);
        if($ret){
            return $localFileName;
        }else{
            return false;
        }
    }
}

?>
