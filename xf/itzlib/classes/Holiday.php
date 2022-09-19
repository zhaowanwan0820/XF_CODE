<?php

/*
获取某年的可提现日，根据百度搜索结果页的日历，在控制台执行以下js，可得到json字符
r = {};
jQuery('.op-calendar-new-relative').each(function(i, day){
    if (!jQuery(day).find('a').hasClass('op-calendar-new-table-other-month')) { 
        // 若是 周五及周末
        if(jQuery(day).parent('td').index() > 4){
            // 若是 周末
            if (jQuery(day).find('a').hasClass('op-calendar-new-table-weekend')){
                // 若是 周末 且工作
                if(jQuery(day).find('a').hasClass('op-calendar-new-table-work')){
                    r[jQuery(day).find('a').attr('date')] = true;
                // 若是 休
                }else if(jQuery(day).find('a').hasClass('op-calendar-new-table-rest')){
                    r[jQuery(day).find('a').attr('date')] = false;
                }else{
                    r[jQuery(day).find('a').attr('date')] = false;
                }
            }
        }else{
            r[jQuery(day).find('a').attr('date')] = true;
        }
    }
})
JSON.stringify(r)
-----------------------------------------------------------------------------------------
以上为笨办法，因为节假日随时可能调整，出错率高

可使用下边这个API

http://www.easybots.cn/api/holiday.php?d=20150904

返回的结果为

{
    "20150904": "1"
}

0 表示上班
1 表示休息日
2 表示节假日


提现日 = Holiday::getInstance()->getWithdrawDay();
还款日 = Holiday::getInstance()->getRepaymentTime( time() );
*/

class Holiday{

    /**
     * instances of this class
     * @var new Object
     */
    private static $instance;

    /**
     * [$days_arr 工作日与非工作日数据]
     * @var array
     */
    private static $days_arr;

    /**
     * [__construct 初始化时加载数据]
     * @return null
     */
    public function __construct() {
        $_configPath = dirname(dirname (__FILE__)).'/config/holiday.php';
        try {
            self::$days_arr = include($_configPath);
        } catch (Exception $e) {
            self::$days_arr = array();
        }
    }

    /**
     * Get different instance of class with different params
     *
     * @param  string $_collection     The collection name
     * @param  string $_db             The db name
     * @param  array  $_server         The servers config
     * @param  string $_replication    //
     *
     * @return object
     */
    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * [days 返回传参日的结果]
     * @param  [int] $day [秒]
     * @return [bool]     [是否是工作日]
     */
    public static function days($day) {
        // try {
        //     $time = date('Ymd', $day);
        //     $r_json = self::curl( 'http://www.easybots.cn/api/holiday.php?d='.$time );
        //     if ( $r_json[$time] == 0 ) return true;
        //     return false;
        // } catch (Exception $e) {
        // 不使用 api 查询
        return self::$days_arr[date('Y-n-j', $day)];
        // }
    }

    /**
     * [$weeks 周]
     * @var array
     */
    private static $weeks = array(
        '1' => '一',
        '2' => '二',
        '3' => '三',
        '4' => '四',
        '5' => '五',
        '6' => '六',
        '7' => '日'
    );

    /**
     * [curl description]
     * @param  [type] $url      [description]
     * @param  array  $curlPost [description]
     * @return [type]           [description]
     */
    private function curl($url, $curlPost=array()){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,0); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 2); //timeout in seconds
        $data = curl_exec($ch);
        #如果错误
        if (curl_errno($ch)) {
            throw new InvalidArgumentException('curl error');
        }
        curl_close($ch);
        return json_decode($data, true);
    }
    
    /**
     * [getWeekDay 获取下一个工作日是周几]
     * @param  [int] $i
     * @return [string]    [ex: 下周一 3月14日]
     */
    private function getWeekDay($i) {
        $prefix = '';
        $date = '';
        if ($i == 2) {
            $prefix = '后天：';
            $date = date('n月j日', time()+(86400*$i));
        } else {
            if (date('N') < 4 && $i < 6) {
                $prefix = '本周';
            } else {
                if ($i < 6) {
                    $prefix = '下周';
                } else {
                    $prefix = '下下周';
                }
            }
            $date = self::$weeks[date('N', time()+(86400*$i))].' '.date('n月j日', time()+(86400*$i));
        }
        return $prefix.$date;
    }

    /**
     * [getWithdrawDay 提现页获取提现预计到账时间]
     * @return [string] [ex: 下周一 3月14日]
     */
    public static function getWithdrawDay() {
        // case 1:  明天银行上班且今天的提现申请是下午5点5分之前  就是  明天到账
        if (self::days( time()+86400 ) === true ) {
            return '明天 '.date('n月j日', time()+86400);
        // case n:  取决于后天银行上不上班
        } else {
            // 因为放假最大天数是7天 //因为17年10月有8天假期，所以改为循环上限改为10
            for ($i=2; $i <= 10; $i++) {
                // 8天内哪天银行上班就是哪天进行提现操作
                if (self::days( time()+(86400*$i) ) === true) {
                    return self::getWeekDay( $i );
                    break;
                } else {
                    continue;
                }
            }

        }

    }


    /**
     * [getWithdrawVerifyDay 提现审核成功页获取提现审核时间]
     * @return [string] [ex: 2016.11.16 17:50]
     */
    public static function getWithdrawVerifyDay() {
        // case 1: 今天是工作日且提现申请是下午5点5分之前  就是  今天审核

        if (self::days( time() ) === true && ((int)date('Gi') < 1750 ? true : false)) {
            return date("Y.m.d ")."17:50";
            // case 2:  明天是工作日且提现申请是下午5点5分之后  就是  明天审核
        }elseif (self::days( time()+86400 ) === true && ((int)date('Gi') >= 1750 ? true : false)){
            return date("Y.m.d ",strtotime("+1 days"))."17:50";
        } else {
            // 因为放假最大天数是7天
            for ($i=1; $i <= 10; $i++) {
                // 8天内哪天银行上班就是哪天进行提现操作
                if (self::days( time()+(86400*$i) ) === true) {
                    return date("Y.m.d ",time()+86400*($i-1))." 17:50";
                    break;
                } else {
                    continue;
                }
            }

        }

    }

    /**
     * get the data array
     * @author Ju 
     * @return array
     */
    public function getDaysArr() {
        return self::$days_arr;
    }
    
    /**
     * [getCountInterestDay 获取开始计息时间]
     * @return [int] [秒]
     */
    public function getCountInterestDay($time) {
        if (!$time) {
            $time = strtotime('midnight');
        } else {
            $time = strtotime('midnight', $time);
        }
        if ( self::days( $time+86400 ) === true ) {
            return strtotime('midnight +1 days', $time);
        } else {
            // 因为放假最大天数是7天
            for ($i=2; $i <= 9; $i++) {
                // 8天内哪天上班就是哪天
                if (self::days( $time+(86400*$i) ) === true) {
                    return $time+(86400*$i);
                    break;
                } else {
                    continue;
                }
            }
        }
    }
    
    /**
     * [getRepaymentTime 获取还款时间]
     * @param  [int] $formal_time [秒]
     * @return [array]            []
     */
    public function getRepaymentTime($formal_time) {
        $returnMsg = array(
            'code'=>0,'info'=>'','data'=>''
        );
        if (!isset($formal_time) || empty($formal_time)) {
            $returnMsg['code'] = 100;
            $returnMsg['info'] = '参数缺失';
            return $returnMsg;
        }
        $formal_time = strtotime(date("Y-m-d 0:0:0",$formal_time)) ;
        $repaymentTime = $formal_time + 20*86400;
        //如果不是节假日
        if (self::days( $repaymentTime ) === true) {
            $returnMsg['code'] = 1;
            $returnMsg['info'] = '可还款';
            $returnMsg['data'] = date("Y-m-d",$repaymentTime);
            return $returnMsg;
        } else {
            // 因为放假最大天数是7天
            for ($i=1; $i <= 8; $i++) { 
                // 8天内哪天银行上班就是哪天进行提现操作
                if (self::days( $repaymentTime+(86400*$i) ) === true){
                    $returnMsg['code'] = 1;
                    $returnMsg['info'] = '节假日还款时间顺延';
                    $returnMsg['data'] = date("Y-m-d",$repaymentTime+(86400*$i));
                    return $returnMsg;
                    break;
                } else {
                    continue;
                }
            }

        }
        return $returnMsg;
    }

    /**
     * [getLastWorkingDay 获取上一个最近工作日的凌晨时间戳]
     * @param  int $time  日期  时间戳或者Ymd格式
     * @return [int]        
     */
    public function getLastWorkingDay($time) {
        $timestamp = is_numeric($time) ? $time : strtotime($time);
        while (true) {
            $timestamp -= 86400;
            if (Holiday::getInstance()->days($timestamp)) {
                return $timestamp;
            }
        }
        return false;
    }
}
