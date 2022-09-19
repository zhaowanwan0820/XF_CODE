<?php
namespace libs\utils;

class XDateTime
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
    const FAKE_INFINITYDATE = "9999-01-01 00:00:00";
    private static $weeks = array("1"=>"星期一", "2"=>"星期二", "3"=>"星期三", "4"=>"星期四", "5"=>"星期五", "6"=>"星期六", "7"=>"星期日");
    private static $workingDays = array("1"=>"星期一", "2"=>"星期二", "3"=>"星期三", "4"=>"星期四", "5"=>"星期五");
    private static $nowForTest = null;
    private $date;  //日期字符串 例:"2008-09-01 08:00:00"


    protected function __construct($date)
    {/*{{{*/
        $this->date = $date;
    }/*}}}*/


    public static function today($format = self::DEFAULT_DATE_FORMAT)
    {/*{{{*/
        return XDateTime::valueOf(date($format));
    }/*}}}*/


    public static function now($format = self::DEFAULT_XDATETIME_FORMAT)
    {/*{{{*/
        if(self::$nowForTest != null) {
            return self::$nowForTest;
        }
        return XDateTime::valueOf(date($format));
    }/*}}}*/


    public static function createXDateTime($year, $month, $day, $hour="00", $minute="00", $second="00")
    {/*{{{*/
        $time = mktime($hour, $minute, $second, $month, $day, $year);
        if($time == false) {
            throw new XDateTimeException(XDateTimeException::FORMAT_XDATETIME_ERROR);
        }
        $date = date(self::DEFAULT_XDATETIME_FORMAT, $time);
        return new XDateTime($date);
    }/*}}}*/

    public static function valueOf($date)
    {/*{{{*/
        if ($date == self::DEFAULT_UNDEFINE_TIME || $date == '0000-00-00' || $date == '0') {
            return new XDateTime(0);
        }
        if(strtotime($date) === false) {
            throw new XDateTimeException(XDateTimeException::FORMAT_XDATETIME_ERROR);
        }
        return new XDateTime($date);
    }/*}}}*/

    public static function valueOfNow()
    {/*{{{*/
        return self::now();
    }/*}}}*/

    public static function valueOfTime($timestamp)
    {/*{{{*/
        $date = date(self::DEFAULT_XDATETIME_FORMAT, $timestamp);
        return new XDateTime($date);
    }/*}}}*/

    public static function setChinaTimeZone()
    {/*{{{*/
        date_default_timezone_set('Asia/Shanghai');
    }/*}}}*/

    public function before($xDateTime)
    {/*{{{*/
        return ($this->getTime() < $xDateTime->getTime());
    }/*}}}*/

    public function after($xDateTime)
    {/*{{{*/
        return ($this->getTime() > $xDateTime->getTime());
    }/*}}}*/

    public function getDate()
    {/*{{{*/
        return $this->date;
    }/*}}}*/

    public function getTime()
    {/*{{{*/
        return strtotime($this->date);
    }/*}}}*/

    public function addYear($year)
    {/*{{{*/
        $year = $this->getYear() + $year;
        $month = $this->getMonth();
        $day = $this->getDay();
        $hour = $this->getHour();
        $minute = $this->getMinute();
        $second = $this->getSecond();
        return self::createXDateTime($year, $month, $day, $hour, $minute, $second);
    }/*}}}*/

    public function addMonth($month)
    {/*{{{*/
        $year = $this->getYear();
        $month = $this->getMonth() + $month;
        $day = $this->getDay();
        $hour = $this->getHour();
        $minute = $this->getMinute();
        $second = $this->getSecond();
        return self::createXDateTime($year, $month, $day, $hour, $minute, $second);
    }/*}}}*/

    public function addDay($day)
    {/*{{{*/
        return $this->addSecond($day * (60 * 60 * 24));
    }/*}}}*/

    public function addHour($hour)
    {/*{{{*/
        return $this->addSecond($hour * (60 * 60));
    }/*}}}*/

    public function addMinute($minute)
    {/*{{{*/
        return $this->addSecond($minute * 60);
    }/*}}}*/

    public function addSecond($second)
    {/*{{{*/
        $time = $this->getTime() + $second;
        return self::valueOf(date(self::DEFAULT_XDATETIME_FORMAT, $time));
    }/*}}}*/

    public function setYear($year)
    {/*{{{*/
        return $this->setDate(self::YEAR, $year);
    }/*}}}*/

    public function setMonth($month)
    {/*{{{*/
        return $this->setDate(self::MONTH, $month);
    }/*}}}*/

    public function setDay($day)
    {/*{{{*/
        return $this->setDate(self::DAY, $day);
    }/*}}}*/

    public function setHour($hour)
    {/*{{{*/
        return $this->setDate(self::HOUR, $hour);
    }/*}}}*/

    public function setMinute($minute)
    {/*{{{*/
        return $this->setDate(self::MINUTE, $minute);
    }/*}}}*/

    public function setSecond($second)
    {/*{{{*/
        return $this->setDate(self::SECOND, $second);
    }/*}}}*/

    private function setDate($format, $setNum)
    {/*{{{*/
        $year = $this->getYear();
        $month = $this->getMonth();
        $day = $this->getDay();
        $hour = $this->getHour();
        $minute = $this->getMinute();
        $second = $this->getSecond();
        switch($format)
        {
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
    }/*}}}*/

    public function getYear()
    {/*{{{*/
        return date("Y", $this->getTime());
    }/*}}}*/

    public function getMonth()
    {/*{{{*/
        return date("m", $this->getTime());
    }/*}}}*/

    public function getDay()
    {/*{{{*/
        return date("d", $this->getTime());
    }/*}}}*/

    public function getHour()
    {/*{{{*/
        return date("H", $this->getTime());
    }/*}}}*/

    public function getMinute()
    {/*{{{*/
        return date("i", $this->getTime());
    }/*}}}*/

    public function getSecond()
    {/*{{{*/
        return date("s", $this->getTime());
    }/*}}}*/

    public function getMonthAndDay()
    {/*{{{*/
        return date("m-d", $this->getTime());
    }/*}}}*/

    public function getDateTime()
    {/*{{{*/
        return date("Y-m-d", $this->getTime());
    }/*}}}*/

    public function getWeekDesc()
    {/*{{{*/
        $index = (int)date('N', $this->getTime());
        return self::$weeks["$index"];
    }/*}}}*/

    public function isWorkingDay()
    {/*{{{*/
        $index = (int)date('N', $this->getTime());
        return array_key_exists($index, self::$workingDays);        
    }/*}}}*/

    public static function yearDiff(XDateTime $d1, XDateTime $d2)
    {/*{{{*/
        return ($d2->getYear() - $d1->getYear());
    }/*}}}*/

    public static function monthDiff(XDateTime $d1, XDateTime $d2)
    {/*{{{*/
        $diff = self::yearDiff($d1, $d2) * 12 + ($d2->getMonth() - $d1->getMonth());
        if ($d2->getDay() - $d1->getDay() < 0) {
            $diff--;
        }
        return $diff;
    }/*}}}*/

    public static function dayDiff(XDateTime $d1, XDateTime $d2)
    {/*{{{*/
        return floor(($d2->getTime() - $d1->getTime()) / (60 * 60 * 24));
    }/*}}}*/

    public static function hourDiff(XDateTime $d1, XDateTime $d2)
    {/*{{{*/
        return floor(($d2->getTime() - $d1->getTime()) / (60 * 60));
    }/*}}}*/

    public static function minuteDiff(XDateTime $d1, XDateTime $d2)
    {/*{{{*/
        return floor(($d2->getTime() - $d1->getTime()) / 60);
    }/*}}}*/

    public static function secondDiff(XDateTime $d1, XDateTime $d2)
    {/*{{{*/
        return floor(($d2->getTime() - $d1->getTime()));
    }/*}}}*/

    public function between($beginDate, $endDate)
    {/*{{{*/
        return $this->getTime() >= $beginDate->getTime() && $this->getTime() <= $endDate->getTime();
    }/*}}}*/

    public function toString()
    {/*{{{*/
        return $this->toStringByFormat();
    }/*}}}*/

    public function toShortString()
    {/*{{{*/
        return $this->toStringByFormat(self::DEFAULT_DATE_FORMAT);
    }/*}}}*/

    public function toStringByFormat($format="Y-m-d H:i:s")
    {/*{{{*/
        return date($format, $this->getTime());
    }/*}}}*/

    public function equals($otherXDateTime)
    {/*{{{*/
        if(is_object($otherXDateTime) && (get_class($this) == get_class($otherXDateTime))) {
            return ($this->getTime() == $otherXDateTime->getTime());
        }
        return false;
    }/*}}}*/

    //@override
    public function __toString()
    {/*{{{*/
        return $this->toStringByFormat();
    }/*}}}*/

    public function getAge()
    {/*{{{*/
        return (time() - ($this->getTime())) / (365*24*3600);
    }/*}}}*/

    public function isUnixStartTime()
    {/*{{{*/
        return ($this->getTime() == XDateTime::valueOf(self::DEFAULT_START_TIME)->getTime());
    }/*}}}*/

    public function isMonday()
    {/*{{{*/
        $index = (int)date('N', $this->getTime());
        return 1 == $index;        
    }/*}}}*/

    public static function infinityDate()
    {/*{{{*/
        return XDateTime::valueOf(self::FAKE_INFINITYDATE); 
    }/*}}}*/

    public function isWednesday()
    {/*{{{*/
        return 3 == (int)date('N', $this->getTime());
    }/*}}}*/

    public static function getDiffInfo(XDateTime $startDate, XDateTime $endDate)
    {/*{{{*/
        return self::seconds2DiffInfo(abs($startDate->getTime() - $endDate->getTime())); 
    }/*}}}*/

    /**
     * seconds2DiffInfo 将秒数转化为几天几小时几分几秒
     * 
     * @param mixed $seconds 秒数
     * @static
     * @access private
     */
    private static function seconds2DiffInfo($seconds)
    {/*{{{*/
        $days = intval($seconds/86400); 
        $remain = $seconds%86400; 
        $hours = intval($remain/3600); 
        $remain = $remain%3600; 
        $mins = intval($remain/60); 
        $secs = $remain%60; 
        $res = array("day" => $days,"hour" => $hours,"min" => $mins,"sec" => $secs); 
        return $res; 
    }/*}}}*/
}

class XDateTimeException extends \Exception
{
    const FORMAT_XDATETIME_ERROR = "转换时间类型失败";
}
