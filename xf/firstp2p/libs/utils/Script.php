<?php
/**
 * 脚本日志工具
 */

namespace libs\utils;

use libs\utils\Logger;

class Script
{

    private static $startTime = 0;

    const START_FLAG = 'WXLC_SCRIPT_START';

    const END_FLAG = 'WXLC_SCRIPT_END';

    /**
     * 脚本执行开始
     */
    public static function start()
    {
        self::$startTime = microtime(true);

        $args = json_encode(array_slice($_SERVER['argv'], 1));
        self::log(self::START_FLAG.". file:{$_SERVER['SCRIPT_NAME']}, args:{$args}");
    }

    /**
     * 脚本执行结束
     */
    public static function end()
    {
        $cost = round(microtime(true) - self::$startTime, 4);
        $memoryMax = round(memory_get_peak_usage() / 1024 / 1024, 2);

        self::log(self::END_FLAG.". cost:{$cost}s, memoryMax:{$memoryMax}m");
    }

    /**
     * 脚本日志
     */
    public static function log($content, $level = Logger::INFO)
    {
        Logger::wLog($content, $level);
        echo date('[Y-m-d H:i:s]').' ['.Logger::getLogId()."] [{$level}] {$content}\n";
    }

}
