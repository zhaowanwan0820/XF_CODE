<?php
/**
 * LogLevel class file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

namespace libs\logging;

/**
 * Describes log levels
 */
class LogLevel
{

    /**
     * 严重错误: 导致系统崩溃无法使用
     **/
    const EMERGENCY = 'emergency';

    /**
     * 警戒性错误: 必须被立即修改的错误
     **/
    const ALERT = 'alert';

    /**
     * 临界值错误: 超过临界值的错误，例如一天24小时，而输入的是25小时这样
     **/
    const CRITICAL = 'critical';

    /**
     * 一般错误: 一般性错误
     **/
    const ERROR = 'error';

    /**
     * 警告性错误: 需要发出警告的错误
     **/
    const WARNING = 'warning';

    /**
     * 通知: 程序可以运行但是还不够完美的错误
     **/
    const NOTICE = 'notice';

    /**
     * 信息: 程序输出信息
     **/
    const INFO = 'info';

    /**
     * 调试: 调试信息
     **/
    const DEBUG = 'debug';
}
