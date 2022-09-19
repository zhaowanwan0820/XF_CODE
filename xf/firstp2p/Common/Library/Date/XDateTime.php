<?php
namespace NCFGroup\Common\Library\Date;

class XDateTime implements \JsonSerializable
{
    const DEFAULT_DATE_FORMAT = "Y-m-d";
    const DEFAULT_XDATETIME_FORMAT = "Y-m-d H:i:s";
    const DEFAULT_XDATETIME_SIMPLE_FORMAT = "y-m-d H:i";
    const DEFAULT_START_TIME = "1970-01-01 00:00:00";   //Unix时间戳 起始时间
    const DEFAULT_UNDEFINE_TIME = "0000-00-00 00:00:00";
    const YEAR = "Y";
    const MONTH = "m";
    const DAY = "d";
    const HOUR = "H";
    const MINUTE = "i";
    const SECOND = "s";
    private static $weeks = array("1"=>"星期一", "2"=>"星期二", "3"=>"星期三", "4"=>"星期四", "5"=>"星期五", "6"=>"星期六", "7"=>"星期日");
    private static $workingDays = array("1"=>"星期一", "2"=>"星期二", "3"=>"星期三", "4"=>"星期四", "5"=>"星期五");
    private static $nowForTest = null;
    private $date;  //日期字符串 例:"2008-09-01 08:00:00"


    protected function __construct($date)
    {
        $this->date = $date;
    }

    public function jsonSerialize()
    {
        return $this->__toString();
    }

    /**
     * today 返回今天的时间 默认时间格式为Y-m-d
     *
     * @param  mixed     $format 时间的格式Y-m-d
     * @static
     * @access public
     * @return XDateTime 当前时间对象
     */
    public static function today($format = self::DEFAULT_DATE_FORMAT)
    {
        return XDateTime::valueOf(date($format));
    }

    /**
     * now 返回现在的时间 黑夜时间格式为Y-m-d H:i:s
     *
     * @param  mixed     $format Y-m-d H:i:s
     * @static
     * @access public
     * @return XDateTime
     */
    public static function now($format = self::DEFAULT_XDATETIME_FORMAT)
    {
        if (self::$nowForTest != null) {
            return self::$nowForTest;
        }

        return XDateTime::valueOf(date($format));
    }

    /**
     * createXDateTime 根据各时间参数创建一个xdatetime对象
     *
     * @param  mixed     $year
     * @param  mixed     $month
     * @param  mixed     $day
     * @param  string    $hour
     * @param  string    $minute
     * @param  string    $second
     * @static
     * @access private
     * @return XDateTime
     */
    private static function createXDateTime($year, $month, $day, $hour="00", $minute="00", $second="00")
    {
        $time = mktime($hour, $minute, $second, $month, $day, $year);
        if ($time == false) {
            throw new XDateTimeException(XDateTimeException::FORMAT_XDATETIME_ERROR);
        }
        $date = date(self::DEFAULT_XDATETIME_FORMAT, $time);

        return new XDateTime($date);
    }

    /**
     * valueOf 通过字符串date来构造一个xdatetime对象
     * date可以是例如2002-09-11 05:22这样的字符字符串
     *
     * @param  string    $date 时间字符串
     * @static
     * @access public
     * @return XDateTime
     */
    public static function valueOf($date)
    {
        if ($date == self::DEFAULT_UNDEFINE_TIME || $date == '0000-00-00' || $date == '0') {
            return new XDateTime(0);
        }
        if (strtotime($date) === false) {
            throw new XDateTimeException(XDateTimeException::FORMAT_XDATETIME_ERROR);
        }

        return new XDateTime($date);
    }

    /**
     * valueOfTime 根据unix时间戳构建XDateTime对象
     *
     * @param  mixed     $timestamp 时间戳
     * @static
     * @access public
     * @return XDateTime
     */
    public static function valueOfTime($timestamp)
    {
        $date = date(self::DEFAULT_XDATETIME_FORMAT, $timestamp);

        return new XDateTime($date);
    }

    /**
     * setChinaTimeZone 设置为中国区
     *
     * @static
     * @access public
     * @return void
     */
    public static function setChinaTimeZone()
    {
        date_default_timezone_set('Asia/Shanghai');
    }

    /**
     * before 比所传XDateTime时间早
     *
     * @param  XDateTime $xDateTime
     * @access public
     * @return bool
     */
    public function before(XDateTime $xDateTime)
    {
        return ($this->getTime() < $xDateTime->getTime());
    }

    /**
     * after 在所传XDateTime时间之后
     *
     * @param  mixed $xDateTime
     * @access public
     * @return bool
     */
    public function after(XDateTime $xDateTime)
    {
        return ($this->getTime() > $xDateTime->getTime());
    }

    /**
     * getTime 获得当前时间对象的时间戳
     *
     * @access public
     * @return int
     */
    public function getTime()
    {
        return strtotime($this->date);
    }

    /**
     * addYear 加年
     *
     * @param  mixed     $year
     * @access public
     * @return XDateTime
     */
    public function addYear($year)
    {
        $year = $this->getYear() + $year;
        $month = $this->getMonth();
        $day = $this->getDay();
        $hour = $this->getHour();
        $minute = $this->getMinute();
        $second = $this->getSecond();

        return self::createXDateTime($year, $month, $day, $hour, $minute, $second);
    }

    /**
     * addMonth 加月
     *
     * @param  mixed     $month
     * @access public
     * @return XDateTime
     */
    public function addMonth($month)
    {
        $year = $this->getYear();
        $month = $this->getMonth() + $month;
        $day = $this->getDay();
        $hour = $this->getHour();
        $minute = $this->getMinute();
        $second = $this->getSecond();

        return self::createXDateTime($year, $month, $day, $hour, $minute, $second);
    }

    /**
     * addDay 加天
     *
     * @param  mixed     $day
     * @access public
     * @return XDateTime
     */
    public function addDay($day)
    {
        return $this->addSecond($day * (60 * 60 * 24));
    }

    /**
     * addHour 加时
     *
     * @param  mixed     $hour
     * @access public
     * @return XDateTime
     */
    public function addHour($hour)
    {
        return $this->addSecond($hour * (60 * 60));
    }

    /**
     * addMinute 加分
     *
     * @param  mixed     $minute
     * @access public
     * @return XDateTime
     */
    public function addMinute($minute)
    {
        return $this->addSecond($minute * 60);
    }

    /**
     * addSecond 加秒
     *
     * @param  mixed     $second
     * @access public
     * @return XDateTime
     */
    public function addSecond($second)
    {
        $time = $this->getTime() + $second;

        return self::valueOf(date(self::DEFAULT_XDATETIME_FORMAT, $time));
    }

    /**
     * setYear 设年
     *
     * @param  mixed     $year
     * @access public
     * @return XDateTime
     */
    public function setYear($year)
    {
        return $this->setDate(self::YEAR, $year);
    }

    /**
     * setMonth 设月
     *
     * @param  mixed     $month
     * @access public
     * @return XDateTime
     */
    public function setMonth($month)
    {
        return $this->setDate(self::MONTH, $month);
    }

    /**
     * setMonth 设天
     *
     * @param  mixed     $month
     * @access public
     * @return XDateTime
     */
    public function setDay($day)
    {
        return $this->setDate(self::DAY, $day);
    }

    /**
     * setMonth 设时
     *
     * @param  mixed     $month
     * @access public
     * @return XDateTime
     */
    public function setHour($hour)
    {
        return $this->setDate(self::HOUR, $hour);
    }

    /**
     * setMonth 设分
     *
     * @param  mixed     $month
     * @access public
     * @return XDateTime
     */
    public function setMinute($minute)
    {
        return $this->setDate(self::MINUTE, $minute);
    }

    /**
     * setMonth 设秒
     *
     * @param  mixed     $month
     * @access public
     * @return XDateTime
     */
    public function setSecond($second)
    {
        return $this->setDate(self::SECOND, $second);
    }

    private function setDate($format, $setNum)
    {
        $year = $this->getYear();
        $month = $this->getMonth();
        $day = $this->getDay();
        $hour = $this->getHour();
        $minute = $this->getMinute();
        $second = $this->getSecond();
        switch ($format) {
        case self::YEAR :
            $year = $setNum;
            break;
        case self::MONTH :
            $month = $setNum;
            break;
        case self::DAY :
            $day = $setNum;
            break;
        case self::HOUR :
            $hour = $setNum;
            break;
        case self::MINUTE :
            $minute = $setNum;
            break;
        case self::SECOND :
            $second = $setNum;
            break;
        }

        return self::createXDateTime($year, $month, $day, $hour, $minute, $second);
    }

    public function getYear()
    {
        return date("Y", $this->getTime());
    }

    public function getMonth()
    {
        return date("m", $this->getTime());
    }

    public function getDay()
    {
        return date("d", $this->getTime());
    }

    public function getHour()
    {
        return date("H", $this->getTime());
    }

    public function getMinute()
    {
        return date("i", $this->getTime());
    }

    public function getSecond()
    {
        return date("s", $this->getTime());
    }

    /**
     * getWeekDesc 获得今天是周几
     *
     * @access public
     * @return string
     */
    public function getWeekDesc()
    {
        $index = (int) date('N', $this->getTime());

        return self::$weeks["$index"];
    }

    /**
     * isWorkingDay 判断是否为工作日
     *
     * @access public
     * @return bool
     */
    public function isWorkingDay()
    {
        $index = (int) date('N', $this->getTime());

        return array_key_exists($index, self::$workingDays);
    }

    /**
     * yearDiff 计算两时间差多少年
     *
     * @param  XDateTime $d1
     * @param  XDateTime $d2
     * @static
     * @access public
     * @return int
     */
    public static function yearDiff(XDateTime $d1, XDateTime $d2)
    {
        return ($d2->getYear() - $d1->getYear());
    }

    /**
     * monthDiff 计算两时间差多少月
     *
     * @param  XDateTime $d1
     * @param  XDateTime $d2
     * @static
     * @access public
     * @return int
     */
    public static function monthDiff(XDateTime $d1, XDateTime $d2)
    {
        $diff = self::yearDiff($d1, $d2) * 12 + ($d2->getMonth() - $d1->getMonth());
        if ($d2->getDay() - $d1->getDay() < 0) {
            $diff--;
        }

        return $diff;
    }

    /**
     * dayDiff 计算两时间差多少天
     *
     * @param  XDateTime $d1
     * @param  XDateTime $d2
     * @static
     * @access public
     * @return int
     */
    public static function dayDiff(XDateTime $d1, XDateTime $d2)
    {
        return floor(($d2->getTime() - $d1->getTime()) / (60 * 60 * 24));
    }

    /**
     * hourDiff 计算两时间差多少小时
     *
     * @param  XDateTime $d1
     * @param  XDateTime $d2
     * @static
     * @access public
     * @return int
     */
    public static function hourDiff(XDateTime $d1, XDateTime $d2)
    {
        return floor(($d2->getTime() - $d1->getTime()) / (60 * 60));
    }

    /**
     * minuteDiff 计算两时间差多少分钟
     *
     * @param  XDateTime $d1
     * @param  XDateTime $d2
     * @static
     * @access public
     * @return int
     */
    public static function minuteDiff(XDateTime $d1, XDateTime $d2)
    {
        return floor(($d2->getTime() - $d1->getTime()) / 60);
    }

    /**
     * secondDiff 计算两时间差多少秒
     *
     * @param  XDateTime $d1
     * @param  XDateTime $d2
     * @static
     * @access public
     * @return int
     */
    public static function secondDiff(XDateTime $d1, XDateTime $d2)
    {
        return floor(($d2->getTime() - $d1->getTime()));
    }

    /**
     * between 计算当前时间是否在所传两时间之间 闭区间
     *
     * @param  mixed $beginDate
     * @param  mixed $endDate
     * @access public
     * @return bool
     */
    public function between($beginDate, $endDate)
    {
        return $this->getTime() >= $beginDate->getTime() && $this->getTime() <= $endDate->getTime();
    }

    /**
     * toString 时间对象的字符串 格式为 Y-m-d H:i:s
     *
     * @access public
     * @return string
     */
    public function toString()
    {
        return $this->toStringByFormat();
    }

    /**
     * toShortString 时间对象的字符串 格式为 Y-m-d
     *
     * @access public
     * @return string
     */
    public function toShortString()
    {
        return $this->toStringByFormat(self::DEFAULT_DATE_FORMAT);
    }

    /**
     * toStringByFormat 返回指定格式的时间字符串
     *
     * @param  string $format 时间格式 默认为Y-m-d H:i:s
     * @access public
     * @return string
     */
    public function toStringByFormat($format="Y-m-d H:i:s")
    {
        return date($format, $this->getTime());
    }

    /**
     * equals 判断是否等于所传时间对象
     *
     * @param  XDateTime $otherXDateTime
     * @access public
     * @return void
     */
    public function equals(XDateTime $otherXDateTime)
    {
        return ($this->getTime() == $otherXDateTime->getTime());
    }

    //@override
    public function __toString()
    {
        return $this->toStringByFormat();
    }

    /**
     * isMonday 判断是否为周一
     *
     * @access public
     * @return bool
     */
    public function isMonday()
    {
        $index = (int) date('N', $this->getTime());

        return 1 == $index;
    }

    /**
     * isWednesday 判断是否为周三
     *
     * @access public
     * @return bool
     */
    public function isWednesday()
    {
        return 3 == (int) date('N', $this->getTime());
    }

    /**
     * getDiffInfo 获得两时间的详细diff信息, 返回两时间差几天几小时几分几秒
     *
     * @param  XDateTime $startDate
     * @param  XDateTime $endDate
     * @static
     * @access public
     * @return array     array("day" => days,"hour" => hours,"min" => mins,"sec" => secs);
     */
    public static function getDiffInfo(XDateTime $startDate, XDateTime $endDate)
    {
        return self::seconds2DiffInfo(abs($startDate->getTime() - $endDate->getTime()));
    }

    /**
     * seconds2DiffInfo 将秒数转化为几天几小时几分几秒
     *
     * @param mixed $seconds 秒数
     * @static
     * @access private
     */
    private static function seconds2DiffInfo($seconds)
    {
        $days = intval($seconds/86400);
        $remain = $seconds%86400;
        $hours = intval($remain/3600);
        $remain = $remain%3600;
        $mins = intval($remain/60);
        $secs = $remain%60;
        $res = array("day" => $days,"hour" => $hours,"min" => $mins,"sec" => $secs);

        return $res;
    }
}
