<?php
/**
 * Runtime class file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

namespace libs\utils;

/**
 * 用于计算执行时间
 **/
class Runtime
{ 
    /**
     * 计时开始的时间
     *
     * @var array
     **/
    private static $start_times = array(); 

    /**
     * 计时结束的时间
     *
     * @var array
     **/
    private static $stop_times = array(); 
 
    /**
     * 获取当前时间(毫秒)
     *
     * @return integer 当前时间(毫秒)
     **/
    private static function getMicrotime() 
    { 
        list($msec, $sec) = explode(' ', microtime()); 
        return ((double)$msec + (double)$sec); 
    } 
 
    /**
     * 开始计时
     *
     * @param string $key 计时标记，区分不同计时区间
     *
     * @return integer 开始计时的时间(毫秒)
     **/
    public static function start($key = "_default") 
    { 
        self::$start_times[$key] = self::getMicrotime(); 
    } 
 
    /**
     * 停止计时
     *
     * @param string $key 计时标记，区分不同计时区间
     *
     * @return integer 结束计时的时间(毫秒)
     **/
    public function stop($key = "_default") 
    { 
        self::$stop_times[$key] = self::getMicrotime(); 
    } 
 
    /**
     * 计算执行时间(毫秒)
     *
     * @param string $key 计时标记，区分不同计时区间
     *
     * @return integer  执行时间(毫秒)
     **/
    public function spent($key = "_default") 
    { 
        return round((self::$stop_times[$key] - self::$start_times[$key]) * 1000, 3); 
    } 
}
