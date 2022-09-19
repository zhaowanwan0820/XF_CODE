<?php
/**
 * /system/utils/logger.php 移植到 /libs/utils/Logger.php
 * 加入名字空间
 * @author 王群强 <wangqunqiang@ncfgroup.com>
 */
namespace libs\utils;

use NCFGroup\Common\Library\TraceSdk;

class Logger
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
    const RPC    = 'RPC';   // RPC:rpc 调用时写入的log
    const ADMIN  = 'ADMIN';   // ADMIN:后台调用时写入的log
    const STATS = 'STATS'; //日志分析
    const BUSINESS = 'BUSINESS'; //业务日志

    // 日志记录方式
    const SYSTEM = 0;
    const MAIL   = 1;
    const TCP    = 2;
    const FILE   = 3;

    const LOG_DIR = "log/logger/";
    // 日志信息
    static $log = array();

    // 日期格式
    //static $format = '[ c ]';
    static $format = '[Y-m-d H:i:s]';

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

        //将PHP错误日志导向了错误的位置，坑了几代人
        //ini_set('error_log', APP_ROOT_PATH."log/logger/".$destination);
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
     * 跟踪日志
     * @param $msg mixed 日志内容
     * @param $tag string 消息tag，比如trace等
     * @param $file string 文件名，一般传__FILE__
     * @param $line string 文件行数，一般传__LINE__
     * @param $type int 日志跟踪类型
     */
    public static function trace($msg, $tag, $file, $line, $type = TraceSdk::LOG_TYPE_TRACE) {
        // 日志level
        $loggerLevels = array(
            'EMERG' => TraceSdk::LOG_TYPE_EMEGENCY,
            'ALERT' => TraceSdk::LOG_TYPE_EXCEPTION,
            'CRIT' => TraceSdk::LOG_TYPE_ERROR,
            'ERR' => TraceSdk::LOG_TYPE_ERROR,
            'WARN' => TraceSdk::LOG_TYPE_INFO,
            'NOTIC' => TraceSdk::LOG_TYPE_INFO,
            'INFO' => TraceSdk::LOG_TYPE_INFO,
            'DEBUG' => TraceSdk::LOG_TYPE_DEBUG,
            'SQL' => TraceSdk::LOG_TYPE_INFO,
            'RPC' => TraceSdk::LOG_TYPE_TRACE,
            'ADMIN' => TraceSdk::LOG_TYPE_INFO,
            'STATS' => TraceSdk::LOG_TYPE_TRACE
        );

        if (array_key_exists($type, $loggerLevels)) {
            if (empty($tag)) {
                $tag = $type;
            }
            $type = $loggerLevels[$type];
        }

        TraceSdk::record($type, $file, $line, $tag, $msg);
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
        $tag = empty($extra['tag']) ? $level : $extra['tag'];
        $backtrace = debug_backtrace();
        $file = isset($backtrace[1]['file']) ? basename($backtrace[1]['file']) : '';
        $line = isset($backtrace[1]['line']) ? $backtrace[1]['line'] : '';
        // self::trace($message, $tag, $file, $line, $level);

        $_message = self::logInfoFormat($message,$level);
        if($destination==''){
            $_destination=APP_ROOT_PATH.self::LOG_DIR.self::getLogFileName($message,$level);
        }else{
            $_destination=$destination;
        }
        //self::save($type, $destination, $extra);
        if (self::mkLogDir()){
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
        if (self::BUSINESS == $level) {//业务日志格式只需要message
            return "{$message}\n";
        }
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
                case self::STATS:
                    $fileName = "p2p_stats_".date('y_m_d').".log";
                    $jsonMessage = json_decode($message);
                    if(property_exists($jsonMessage,"platform") &&$jsonMessage->platform=="admin"){
                        $fileName = "p2p_stats_admin_".date('y_m_d').".log";
                    }
                    break;
                case self::BUSINESS:
                    $fileName = "p2p_business_".date('y_m_d').".log";
                    break;
                case self::DEBUG:
                    $fileName = "p2p_debug_".date('y_m_d').".log";
                    break;
                case self::RPC:
                    $fileName = "p2p_rpc_".date('y_m_d').".log";
                    break;
                case self::INFO:
                    $fileName = "p2p_info_".date('y_m_d').".log";
                    break;
                case self::ADMIN:
                    $fileName = "p2p_admin_".date('y_m_d').".log";
                    break;
                case self::ERR:
                    $fileName = "p2p_err_".date('y_m_d').".log";
                    break;
                default:
                    $fileName;
            }
        return $fileName;

    }

    /**
     * 记录统计日志，数据分析meta日志
     *
     * @param mixed $message 日志信息
     * @return void
     * @author vincent <daiyuxin@ucfgroup.com>
     **/
    static function stats($message)
    {
        self::wLog($message, self::STATS);
    }

    /**
     * 记录业务日志信息
     *
     * @param $message 日志json
     * @return void
     */
    static function business($message)
    {
        self::wLog($message, self::BUSINESS);
    }

    /**
     * 远程日志
     */
    public static function remote($message, $level = self::INFO)
    {
        $ip = app_conf('REMOTE_LOG_SERVER_IP');
        $port = app_conf('REMOTE_LOG_SERVER_PORT');

        PaymentRemoteLog::instance($ip, $port)->add($level, $message);
    }

    /**
     * 全局唯一日志ID
     */
    private static $logId = '';

    public static function getLogId()
    {
        if (self::$logId === '') {
            self::$logId = sprintf('%x', (intval(microtime(true) * 10000) % 864000000) * 10000 + mt_rand(0, 9999));
        }

        return self::$logId;
    }

}
