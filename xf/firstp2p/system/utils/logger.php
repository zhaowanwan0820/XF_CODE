<?php

///!!!!!!!!
//
// 使用这个类的话，请移步\libs\utils\Logger, 此类不再维护。
//
//!!!!!!!!!

// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class logger
{
    // 日志级别 从上到下，由低到高
    const EMERG  = 'EMERG'; // 严重错误: 导致系统崩溃无法使用
    const ALERT  = 'ALERT'; // 警戒性错误: 必须被立即修改的错误
    const CRIT   = 'CRIT';  // 临界值错误: 超过临界值的错误，例如一天24小时，而输入的是25小时这样
    const ERR    = 'ERR';   // 一般错误: 一般性错误
    const WARN   = 'WARN';  // 警告性错误: 需要发出警告的错误
    const NOTICE = 'NOTIC'; // 通知: 程序可以运行但是还不够完美的错误
    const INFO   = 'INFO';  // 信息: 程序输出信息
    const DEBUG  = 'DEBUG'; // 调试: 调试信息
    const SQL    = 'SQL';   // SQL：SQL语句 注意只在调试模式开启时有效

    // 日志记录方式
    const SYSTEM = 0;
    const MAIL   = 1;
    const TCP    = 2;
    const FILE   = 3;

    // 日志信息
    static $log = array();

    // 日期格式
    //static $format = '[ c ]';
    static $format = '[Y-m-d H:i:s]';
    const LOG_DIR = "log/logger/";
    /**
     +----------------------------------------------------------
     * 记录日志 并且会过滤未经设置的级别
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @param string $message 日志信息
     * @param string $level  日志级别
     * @param boolean $record  是否强制记录
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function record($message, $level=self::ERR, $record=false) {
        if($record || in_array($level,array('EMERG','ALERT','CRIT','ERR'))) {
            $now = date(self::$format);
            self::$log[] = "{$now} {$level}: {$message}\r\n";
        }
    }

    /**
     +----------------------------------------------------------
     * 日志保存
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @param integer $type 日志记录方式
     * @param string $destination  写入目标
     * @param string $extra 额外参数
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function save($type=self::FILE, $destination='', $extra='') {
        if(empty($destination)) {
            if(!is_dir( APP_ROOT_PATH."log/logger/")) {
                if(!mkdir(APP_ROOT_PATH."log/logger/")) {
                    return false;
                }
            }
            $destination = APP_ROOT_PATH."log/logger/".date('y_m_d').".log";
        }
        error_log(implode("",self::$log), $type,$destination ,$extra);
        // 保存后清空日志缓存
        self::$log = array();
        //clearstatcache();
    }

    /**
     +----------------------------------------------------------
     * 日志直接写入
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @param string $message 日志信息
     * @param string $level  日志级别
     * @param integer $type 日志记录方式
     * @param string $destination  写入目标
     * @param string $extra 额外参数
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function write($message, $level=self::ERR, $type=self::FILE, $destination='', $extra='') {
        $now = date(self::$format);
        if(empty($destination))
            $destination = LOG_PATH.date('y_m_d').".log";
        error_log("{$now} {$level}: {$message}\r\n", $type,$destination,$extra );
        //clearstatcache();
    }

    /**
     * 记录调试信息
     *
     * @param mixed $message 日志信息
     * @param $message
     * @return void
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    static function debug($message)
    {
        self::wLog($message, self::DEBUG);
    }

    /**
     * 记录一般信息
     *
     * @param mixed $message 日志信息
     * @return void
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    static function info($message)
    {
        self::wLog($message, self::INFO);
    }

    /**
     * 记录警告信息
     *
     * @param mixed $message 日志信息
     * @return void
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    static function warn($message)
    {
        self::wLog($message, self::WARN);
    }

    /**
     * 记录错误信息
     *
     * @param mixed $message 日志信息
     * @return void
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    static function error($message)
    {
        self::wLog($message, self::ERR);
    }

    /**
     * 记录通知性错误
     *
     * @param mixed $message 日志信息
     * @return void
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    static function notice($message)
    {
        self::wLog($message, self::NOTICE);
    }

    /**
     * 写日志，对日志内容格式进行简单处理
     *
     * @param mixed $message 日志信息
     * @param string $level  日志级别
     * @param integer $type 日志记录方式
     * @param string $destination  写入目标
     * @param string $extra 额外参数
     * @return void
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    static function wLog($message, $level=self::INFO, $type=self::FILE, $destination='', $extra='') {
//        $message = str_replace("\n", "", print_r($message, true) );
//        $now = date(self::$format);
//        self::$log[] = "{$now} {$level}: {$message}\r\n";
//        self::save($type, $destination, $extra);
        $_message = self::logInfoFormat($message,$level);
        if($destination==''){
            $_destination=APP_ROOT_PATH.self::LOG_DIR.self::getLogFileName($message,$level);
        }else{
            $_destination=$destination;
        }
        //self::save($type, $destination, $extra);
        if(self::mkLogDir()){
            error_log($_message, $type,$_destination ,$extra);
        }
    }
    private static function mkLogDir(){
        if(!is_dir( APP_ROOT_PATH.self::LOG_DIR)) {
            if(!mkdir(APP_ROOT_PATH.self::LOG_DIR)) {
                return false;
            }
        }
        return true;
    }
    /**
     * 日志信息保存前格式化处理
     * @param mixed $message 日志信息
     * @param string $level  日志级别
     * @return string
     **/
    private static function logInfoFormat($message,$level=self::INFO){
        $message = str_replace("\n", "", print_r($message, true) );
        $now = date(self::$format);
        $logId = self::getLogId();
        return "{$now} [{$logId}] [{$level}] {$message}\n";

    }

    /**
     * 获取保存日志的文件名
     * @param string $level  日志级别
     * @return string fileName
     **/
    private static  function getLogFileName($message,$level=self::INFO){
        $fileName = "p2p_".date('y_m_d').".log";
        switch($level){
            case self::DEBUG:
                $fileName = "p2p_debug_".date('y_m_d').".log";
                break;
            case self::INFO:
                $fileName = "p2p_info_".date('y_m_d').".log";
                break;
            case self::ERR:
                $fileName = "p2p_err_".date('y_m_d').".log";
                break;
            default:
                $fileName;
        }
        return $fileName;

    }

    public static function getLogId()
    {
        // 为了日志id的统一
        return \libs\utils\Logger::getLogId();
    }
}
