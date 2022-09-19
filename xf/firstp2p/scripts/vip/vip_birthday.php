<?php
/**
 * 生日福利券
 * 网信会为生日的VIP用户发放生日专属福利；
 * 不同等级的VIP会员，享受不同的生日福利。
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

class VipBirthdayTask {
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

    /**
     * 是否邮件
     * @var int
     */
    private $withReport = false;

    /**
     * 生日日期，默认去当天的值
     */
    private $birthday = 0;
    private $byear = 0;
    private $bmonth = 0;
    private $bday = 0;

    public function __construct($userId, $birthday, $withSms, $withPush, $withReport) {
        $this->userId = $userId;

        $this->birthday = $birthday;
        $items = explode('-', $this->birthday);
        if (count($items) != 3 || !is_numeric($items[0]) || !is_numeric($items[1]) || !is_numeric($items[2])) {
            throw new \Exception('生日格式错误，样例格式2017-07-31');
        }

        $this->byear = intval($items[0]);
        $this->bmonth = intval($items[1]);
        $this->bday = intval($items[2]);
        $this->withSms = $withSms;
        $this->withPush = $withPush;
        $this->withReport = $withReport;
    }

    public function getUserAccount() {
        $sql = "SELECT * FROM `firstp2p_vip_account`
            WHERE `bmonth`={$this->bmonth} AND `bday`={$this->bday} AND `service_grade` > 0 
            ORDER BY `id` ASC";
        PaymentApi::log('VipBirthdayTask sql: '.$sql);

        return VipAccountModel::instance()->findAllBySql($sql);
    }

    // 脚本执行
    public function run() {
        $params = array('userId'=>$this->userId, 'birthday'=>$this->birthday,
            'withSms'=>$this->withSms, 'withPush'=>$this->withPush, 'withReport' => $this->withReport);

        echo 'VipBirthdayTask begin, params: '.json_encode($params).PHP_EOL;
        PaymentApi::log('VipBirthdayTask begin, params: '.json_encode($params));

        $count = 0;
        $vipService = new VipService();
        $vipLogId = $this->byear * 10000 + $this->bmonth * 100 + $this->bday;
        $awardType = VipGiftLogModel::VIP_AWARD_TYPE_BIRTHDAY;
        if ($this->userId > 0) {
            $userAccount = VipAccountModel::instance()->getVipAccountByUserId($this->userId);
            if (empty($userAccount)) {
                return false;
            }

            if ($userAccount['bmonth'] != $this->bmonth
                || $userAccount['bday'] != $this->bday) {
                return false;
            }

            $token = 'birthday_'.$awardType.'_'.$this->userId.'_'.$this->byear;
            $res = $vipService->addPeriodGiftLogAndSendGift($this->userId, $userAccount['service_grade'],
                $vipLogId, $token, $awardType);

            if ($res) {
                $count++;
            }
        } else {
            $users = $this->getUserAccount();
            foreach ($users as $user) {
                try {
                    $token = 'birthday_'.$awardType.'_'.$user['user_id'].'_'.$this->byear;
                    PaymentApi::log('VipBirthdayTask process userId|'.$user['user_id'].'|token|'.$token);
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
        if ($this->withReport) {
            $this->sendReport($count);
        }

        echo 'VipBirthdayTask success, count: '.$count.PHP_EOL;
        PaymentApi::log('VipBirthdayTask success, count: '.$count);
    }

    public function sendReport($count) {
        $currentDate = date('Y-m-d');
        $subject = $currentDate.'vip生日礼券发送统计';
        $content = "<h3>$subject</h3>";
        $content .= "<table border=1 style='text-align: center'>";
        $content .= "<tr><th>日期</th><th>VIP生日用户数</th></tr>";
        $content .= "<tr><td> {$currentDate} </td><td>". $count. "</td></tr>";
        $content .= "</table>";
        $mail = new \NCFGroup\Common\Library\MailSendCloud();
        $mailAddress = ['liguizhi@ucfgroup.com'];
        $ret = $mail->send($subject, $content, $mailAddress);
    }
}

$shortopts = "";
$shortopts .= "u::"; // 用户ID

$longopts = array(
    "with-sms",     // 是否短信
    "with-push",    // 是否推送
    "with-report",  // 是否邮件
    "birthday::",   // 指定运行的生日日期
    "help",         // 帮助
);

// 获取参数
$opts = getopt($shortopts, $longopts);

if (isset($opts['help'])) {
    $str = <<<HELP
Usage: php vip_birthday [args...]
    -u 用户ID
    --with-sms 发送短息
    --with-push 推送
    --with-report 邮件
    --birthday=2017-07-31 指定运行的生日日期,例如2017-07-31
    --help 帮助
HELP;
    exit($str.PHP_EOL);
}

$userId     = isset($opts['u']) ? intval($opts['u']) : 0;
// 默认取当前时间
$birthday   = isset($opts['birthday']) ? $opts['birthday'] : date('Y-m-d', strtotime("+1 days"));
$withSms    = isset($opts['with-sms']) ? true : false;
$withPush   = isset($opts['with-push']) ? true : false;
$withReport   = isset($opts['with-report']) ? true : false;

try {
    $vip = new VipBirthdayTask($userId, $birthday, $withSms, $withPush, $withReport);
    $vip->run();
} catch (\Exception $ex) {
    $params = array('userId'=>$userId, 'birthday'=>$birthday,
        'withSms'=>$withSms, 'withPush'=>$withPush, 'withReport' => $withReport);

    echo 'VipBirthdayTask: '.$ex->getMessage().', params: '.json_encode($params).PHP_EOL;
    PaymentApi::log('VipBirthdayTask: '.$ex->getMessage().', params: '.json_encode($params), Logger::ERR);
}
