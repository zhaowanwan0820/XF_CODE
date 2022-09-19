<?php
/**
 * 优金宝每日加经验值&加息
 */

ini_set("display_errors", 1);
error_reporting(E_ALL);

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once dirname(__FILE__).'/../../app/init.php';

use core\service\vip\VipService;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\service\GoldService;
use NCFGroup\Protos\Ptp\Enum\VipEnum;

class VipYjbTask {
    //任务类型:1-经验值, 2-加息
    const VIP_POINT = 1;
    const VIP_RAISE_RATES = 2;
    const PAGESIZE = 50;

    /**
     * 是否邮件
     * @var int
     */
    private $withReport = false;
    private $type = null;

    public function __construct($withReport, $type) {
        $this->withReport = $withReport;
        $this->type = $type;
    }

    private function getYjbUserCount() {
        $goldService = new GoldService();
        $result = $goldService->getUserList(self::PAGESIZE, 1);
        if (isset($result['data'])) {
            return $result['data']['totalPage'];
        } else {
            throw new \Exception('获取优金宝用户异常');
        }
    }

    public function getYjbUserList($pageNum) {
        $goldService = new GoldService();
        $result = $goldService->getUserList(self::PAGESIZE, $pageNum);
        if (isset($result['data'])) {
            return $result['data']['data'];
        } else {
            throw new \Exception('获取优金宝用户列表异常');
        }
    }

    public function addPoint($userId, $money) {
        // 增加vip经验埋点
        $sourceType = VipEnum::VIP_SOURCE_YJB;
        $sourceAmount = bcdiv($money , 360, 2);
        $token = $sourceType. '_'. $userId. '_'.date('Ymd');
        $info = '优金宝，'.$money.'元';
        $vipService = new VipService();
        return $vipService->updateVipPoint($userId, $sourceAmount, $sourceType, $token, $info);
    }

    public function addInterest($userId, $money) {
        $sourceType = VipEnum::VIP_SOURCE_YJB;
        $annualizedAmount = bcdiv($money , 360, 2);
        $token = $sourceType. '_'. $userId. '_'.date('Ymd');
        $couponGroupId = app_conf('COUPON_GROUP_ID_VIP_REBATE_YJB');
        $vipService = new VipService();
        return $vipService->vipRaiseInterest($userId, $money, $annualizedAmount, $token, $sourceType, $couponGroupId);
    }

    public function getGoldPrice() {
        $goldService = new GoldService();
        $result =  $goldService->getGoldPriceByDate();
        if (isset($result['price'])) {
            return $result['price'];
        } else {
            throw new \Exception('获取优金宝金价异常');
        }
    }


    // 脚本执行
    public function run() {
        $pageSize = self::PAGESIZE;
        $params = array('withReport' => $this->withReport);
        echo 'VipYjbTask begin, params: '.json_encode($params).PHP_EOL;
        PaymentApi::log('VipYjbTask begin, params: '.json_encode($params));

        $totalPage = $this->getYjbUserCount();
        if ($totalPage) {
            $goldPrice = $this->getGoldPrice();
            for ($i = 1; $i <= $totalPage; $i++) {
                $userList = $this->getYjbUserList($i);
                foreach ($userList as $item) {
                    $item['money'] = $item['gold'] * $goldPrice;
                    if ($item['money'] > 0) {
                        try{
                            if ($this->type == self::VIP_POINT) {
                                $this->addPoint($item['userId'], $item['money']);
                            } else {
                                $this->addInterest($item['userId'], $item['money']);
                            }
                        } catch (\Exception $e) {
                            PaymentApi::log('VipYjbTask err userId:'.$item['userId'].' msg:'.$e->getMessage());
                            continue;
                        }
                    } else {
                        paymentApi::log('VipYjbTask money 0 userId:'.$item['userId']);
                    }
                }
            }
        }
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

$longopts = array(
    "with-report", // 是否发邮件
    "type::",//任务类型
    "help",
);

// 获取参数
$opts = getopt($shortopts, $longopts);

if (isset($opts['help'])) {
    $str = <<<HELP
Usage: php vip_dt_point.php [args...]
    --with-report 邮件统计
    --type=1[1经验值,2加息]
    --help 帮助
HELP;
    exit($str.PHP_EOL);
}

$withReport   = isset($opts['with-report']) ? true : false;
$type = isset($opts['type']) ? $opts['type'] : 1;

try {
    $vip = new VipYjbTask($withReport, $type);
    $vip->run();
} catch (\Exception $ex) {
    $params = array('withReport' => $withReport, 'type' => $type);

    echo 'VipDtTask: '.$ex->getMessage().', params: '.json_encode($params).PHP_EOL;
    PaymentApi::log('VipDtTask: '.$ex->getMessage().', params: '.json_encode($params), Logger::ERR);
}
