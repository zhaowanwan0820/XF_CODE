<?php
/**
 * 进程相关工具
 */
namespace libs\utils;

class Process
{

    /**
     * 获取Pid列表
     */
    public static function getPidList($name)
    {
        $result = array();
        exec('ps -ef | grep "'.$name.'" | grep -v grep | grep -v vi | grep -v sudo | awk \'{print $2}\' | sort', $result);
        return $result;
    }

    /**
     * 检查进程是否已存在
     */
    public static function exists($name)
    {
        $result = array();
        $pid = posix_getpid();
        exec("ps -ef | grep {$name} | grep -v grep | grep -v sudo | grep -v {$pid} | grep -v vi | grep -v /bin/sh", $result);
        return empty($result) ? false : true;
    }

}
