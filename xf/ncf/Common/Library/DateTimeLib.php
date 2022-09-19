<?php
namespace NCFGroup\Common\Library;

class DateTimeLib
{
    const PTP_DB_STEP_TIME = 28800; // 3600 * 8

    /**
     * 获得当前日期和时间
     *
     * @return string
     */
    public static function getCurrentDateTime()
    {
        return date("Y-m-d H:i:s");
    }

    /**
     * 获得当前日期
     *
     * @return string
     */
    public static function getCurrentDate()
    {
        return date("Y-m-d");
    }

    /**
     * realPtpDbTime
     * 返回实际时间，flag: 0:timestamp(1427270845) 1:timestring('Y-m-d H:i:s')
     * P2P系统数据库中时间比实际系统时间差8个时区
     *
     * @param mixed $time
     * @param mixed $flag
     * @static
     * @access public
     * @return void
     */
    public static function realPtpDbTime($time, $flag = 0)
    {
        if ($flag) {
            return strtotime($time) + self::PTP_DB_STEP_TIME;
        } else {
            return $time + self::PTP_DB_STEP_TIME;
        }
    }
}

?>
