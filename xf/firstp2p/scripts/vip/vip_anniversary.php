<?php
/**
 * 周年福利券
 * 网信会根据VIP用户的注册时间，在周年纪念日时发放感恩回馈礼；
 * 不同等级的VIP会员，享受不同的周年福利。
 */

ini_set("display_errors", 1);
error_reporting(E_ALL);

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once dirname(__FILE__).'/../../app/init.php';

use core\dao\vip\VipAccountModel;
use core\dao\vip\VipGiftLogModel;
use core\service\vip\VipService;
use libs\utils\Logger;
use libs\utils\PaymentApi;

class VipAnniversaryTask {
    /**
     * 用户ID
     * @var int
     */
    private $userId = 0;

    /**
     * 是否发送短信
     * @var int
     */
    private $withSms = false;

    /**
     * 是否推送
     * @var int
     */
    private $withPush = false;
    private $startDate = '';
    private $dateSpan = 0;

    /**
     * 是否邮件
     * @var int
     */
    private $withReport = false;

    public function __construct($userId, $withSms, $withPush, $withReport, $startDate = '', $dateSpan = 0) {
        $this->userId = $userId;
        $this->withSms = $withSms;
        $this->withPush = $withPush;
        $this->withReport = $withReport;
        $this->startDate = empty($startDate) ? strtotime(date('Ymd')) : strtotime($startDate);
        $this->dateSpan = empty($dateSpan) ? 1 : intval($dateSpan);
    }

    private function getMaxIdFromVipAccount() {
        $sql = "SELECT * FROM `firstp2p_vip_account` ORDER BY `id` DESC LIMIT 1";
        $result = VipAccountModel::instance()->findBySql($sql);
        return $result['id'];
    }

    public function getUserAccount($registerTimeBegin, $registerTimeEnd) {

        $sql = "SELECT * FROM `firstp2p_vip_account`
            WHERE register_time >= {$registerTimeBegin}
            AND register_time < {$registerTimeEnd} AND `service_grade` > 0
            ORDER BY `id` ASC ";
        PaymentApi::log('VipAnniversaryTask sql: '.$sql);
        return VipAccountModel::instance()->findAllBySql($sql);
    }

    private function getEachTimeSpan($eachDate, $initTimeStamp) {
        $result = [];
        $anniversaryNum = date("Y",strtotime($eachDate)) - date("Y",$initTimeStamp);
        for ($i=1; $i<=$anniversaryNum; $i++) {
            $registerTimeBegin = strtotime(date('Y-m-d',strtotime("-$i year", strtotime($eachDate))));
            $registerTimeEnd = $registerTimeBegin + 86400;
            $result[] =  array('start'=> $registerTimeBegin, 'end' => $registerTimeEnd, 'startDate' => date('Y-m-d',$registerTimeBegin), 'endDate' => date('Y-m-d', $registerTimeEnd));
        }
        return $result;
    }

    // 脚本执行
    public function run() {
        $params = array('userId'=>$this->userId,
            'withSms'=>$this->withSms, 'withPush'=>$this->withPush, 'withReport' => $this->withReport, 'startDate' => $this->startDate, 'dateSpan' => $this->dateSpan);

        echo 'VipAnniversaryTask begin, params: '.json_encode($params).PHP_EOL;
        PaymentApi::log('VipAnniversaryTask begin, params: '.json_encode($params));

        $count = 0;
        $vipService = new VipService();
        $year = date('Y');
        $month = date('M');
        $day = date('d');
        $vipLogId = $year * 10000 + $month * 100 + $day;
        $awardType = VipGiftLogModel::VIP_AWARD_TYPE_ANNIVERSARY;
        //根据startDate 和dateSpan合并需要查询的条件数组,循环执行周年返利
        $initTimeStamp = strtotime('2012-01-01');//注册时间的初始下限
        $timeSpanArray = array();
        for ($i=1; $i<=$this->dateSpan; $i++) {
            $eachDate = date('Ymd', $this->startDate + ($i-1)*86400);
            $timeSpanArray[$eachDate] = $this->getEachTimeSpan($eachDate, $initTimeStamp);
        }
        PaymentApi::log('VipAnniversaryTask begin, timeSpanArray: '.json_encode($timeSpanArray));
        if ($this->userId > 0) {
            $userAccount = VipAccountModel::instance()->getVipAccountByUserId($this->userId);
            if (empty($userAccount)) {
                return false;
            }

            $token = 'anniversary_'.$awardType.'_'.$this->userId.'_'.$year;
            $res = $vipService->addPeriodGiftLogAndSendGift($this->userId, $userAccount['service_grade'],
                $vipLogId, $token, $awardType);

            if ($res) {
                $count++;
            }
        } else {
            foreach ($timeSpanArray as $oneDay => $item) {
                foreach ($item as $span) {
                    $users = $this->getUserAccount($span['start'], $span['end']);
                    foreach ($users as $user) {
                        try {
                            $token = 'anniversary_'.$awardType.'_'.$user['user_id'].'_'.$year;
                            PaymentApi::log('VipAnniversaryTask process date|'.$oneDay.'| userId|'.$user['user_id'].'|token|'.$token);
                            $res = $vipService->addPeriodGiftLogAndSendGift($user['user_id'], $user['service_grade'],
                                $vipLogId, $token, $awardType);

                            if ($res) {
                                $count++;
                            }
                        } catch (\Exception $e) {
                            Logger::error(implode(' | ', [__CLASS__, json_encode($user), $e->getMessage()]));
                        }
                    }
                }
            }
        }

        if ($this->withReport) {
            $this->sendReport($count);
        }

        echo 'VipAnniversaryTask success, count: '.$count.PHP_EOL;
        PaymentApi::log('VipAnniversaryTask success, count: '.$count);
    }

    public function sendReport($count) {
        $currentDate = date('Y-m-d');
        $subject = $currentDate.'vip周年礼券发送统计';
        $content = "<h3>$subject</h3>";
        $content .= "<table border=1 style='text-align: center'>";
        $content .= "<tr><th>日期</th><th>VIP满周年用户数</th></tr>";
        $content .= "<tr><td> {$currentDate} </td><td>". $count. "</td></tr>";
        $content .= "</table>";
        $mail = new \NCFGroup\Common\Library\MailSendCloud();
        $mailAddress = ['liguizhi@ucfgroup.com'];
        $ret = $mail->send($subject, $content, $mailAddress);
    }
}

$shortopts = "";
$shortopts .= "u:"; // 用户ID

$longopts = array(
    "with-sms", // 是否发短信
    "with-push", // 是否发推送
    "with-report", // 是否发邮件
    "start-date::",//开始日期
    "date-span::", //发送天数
    "help",
);

// 获取参数
$opts = getopt($shortopts, $longopts);

if (isset($opts['help'])) {
    $str = <<<HELP
Usage: php vip_anniversary.php [args...]
    -u 用户ID
    --with-sms 发送短息
    --with-push 推送
    --with-report 邮件统计
    --start-date 开始日期
    --date-span 补发日期间隔
    --help 帮助
HELP;
    exit($str.PHP_EOL);
}

$userId     = isset($opts['u']) ? intval($opts['u']) : 0;
$withSms    = isset($opts['with-sms']) ? true : false;
$withPush   = isset($opts['with-push']) ? true : false;
$withReport   = isset($opts['with-report']) ? true : false;
$startDate = isset($opts['start-date']) ? $opts['start-date'] : '';
$dateSpan = isset($opts['date-span']) ? $opts['date-span'] : '';

try {
    $vip = new VipAnniversaryTask($userId, $withSms, $withPush, $withReport, $startDate, $dateSpan);
    $vip->run();
} catch (\Exception $ex) {
    $params = array('userId'=>$userId,
        'withSms'=>$withSms, 'withPush'=>$withPush, 'withReport' => $withReport);

    echo 'VipBirthdayTask: '.$ex->getMessage().', params: '.json_encode($params).PHP_EOL;
    PaymentApi::log('VipBirthdayTask: '.$ex->getMessage().', params: '.json_encode($params), Logger::ERR);
}
