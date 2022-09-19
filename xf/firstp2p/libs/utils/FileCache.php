<?php

namespace libs\utils;

class FileCache
{

    const DEFAULT_EXPIRE_TIME = 600;

    const IGNORE_EXPRIE_TIME = true;

    private $_cacheDir;

    private function __construct()
    {
        $this->_cacheDir = APP_RUNTIME_PATH . "file_cache";  //为了不跟业务耦合在一起，没有采用APP_RUNTIME_PATH
    }

    public static function getInstance()
    {
        static $instance = null;
        if($instance == null) {
            $instance = new FileCache();
        }
        return $instance;
    }

    /**
     * 读缓存
     */
    public function get($key, $ignoreExpireTime = false)
    {
        if (empty($key)) {
            return false;
        }

        $fileName = $this->_getFileName($key);
        if (!file_exists($fileName)) {
            return false;
        }

        $data = file_get_contents($fileName);
        $data = unserialize($data);
        $time = (int) $data["time"];

        //是否过期
        if ($ignoreExpireTime === false && $time < time()) {
            return false;
        }

        return $data['data'];
    }

    private function _getFileName($key) {
        $md5Key = md5($key);
        $fileName = rtrim($this->_cacheDir, '/') . '/' . substr($md5Key, 0, 3) . '/' . $md5Key;
        return $fileName;
    }

    public function set($key, $value, $expireTime = 600) {
        if(empty($key)) {
            return false;
        }

        if(!isset($expireTime) || intval($expireTime) <= 0) {
            $expireTime = self::DEFAULT_EXPIRE_TIME; 
        } 
        
        $fileName = $this->_getFileName($key);
        if(empty($fileName)) {
            return false;
        }

        if(!$this->_makeDir(dirname($fileName))) {
            return false; 
        }

        $data['time'] = time() + $expireTime;
        $data['data'] = $value;
        $data = serialize($data);

        $result = @file_put_contents($fileName, $data, LOCK_EX);//更新的时候加了写独占锁，但是不影响读，这里可能会有并发的问题。
        return $result;     
    }

    private function _makeDir($dir) {
        if(!is_writable($dir)) {
            if(!@mkdir($dir, 0777, true)) {
                return false;
            }
        }
        return true;
    }

    public function delete($key) {
        if(empty($key)) {
            return false;
        }
        $fileName = $this->_getFileName($key);
        if(empty($fileName)) {
            return false;
        }
        $result = @unlink($fileName);
        return $result;
    }
}

