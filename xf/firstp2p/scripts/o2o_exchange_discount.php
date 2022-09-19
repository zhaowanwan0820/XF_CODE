<?php

set_time_limit(0);
ini_set('memory_limit', '512M');
require_once dirname(__FILE__).'/../app/init.php';

use core\dao\DealLoadModel;
use core\dao\DealModel;
use core\dao\DiscountModel;
use core\dao\DiscountRateModel;
use core\service\DiscountService;
use core\service\GoldService;
use libs\utils\Logger;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class O2OExchangeDiscount
{
    private $timeScope;
    private $endTime;

    private $tryTime = 3;
    private $pagesize = 1000;

    private $discountService;

    public function __construct($endTime, $timeScope)
    {
        $this->timeScope = $timeScope ?: 3600 * 2;
        $this->endTime = $endTime ?: time();
        $this->discountService = new DiscountService();
    }

    public function run()
    {
        try {

            $startTime = date('Y-m-d H:i:s', $this->endTime - $this->timeScope);
            $endTime = date('Y-m-d H:i:s', $this->endTime);
//            $conditionBase = '`create_time` BETWEEN ":start" AND ":end" AND `status` = 0 ';
            $conditionBase = '`create_time` BETWEEN ":start" AND ":end"';

            $params = [':start' => $startTime, ':end' => $endTime];

            $count = DiscountModel::instance()->countViaSlave($conditionBase, $params);
            Logger::info(implode(' | ', [__CLASS__, __FUNCTION__, 'START', $startTime, $endTime, $count]));
            $page = intval(ceil($count / $this->pagesize));

            $goldService = new GoldService();
            $response = $goldService->getGoldPrice(true);
            if($response['errCode'] != '0' || empty($response['data']['gold_price'])){
                throw new \Exception('当前非交易时段');
            }

            $currentGoldPrice = $response['data']['gold_price'];

            while ($page--) {
                $start = $page * $this->pagesize;
                $condition = $conditionBase ."ORDER BY id LIMIT {$start}, {$this->pagesize}";

                $discounts = DiscountModel::instance()->findAllViaSlave($condition, true, '`user_id`, `discount_id`, `consume_type`, `consume_id`, `status`, `extra_info`', $params);
                foreach ($discounts as $discount) {
                    $userId = $discount['user_id'];
                    $discountId = $discount['discount_id'];
                    $dealLoadId = $discount['consume_id'];
                    $discountRateLog = DiscountRateModel::instance()->findBy("token='{$discountId}'", 'id');
                    $extraInfo = isset($discount['extra_info']) ? json_decode($discount['extra_info']) : array();
                    $annualizedAmount = isset($extraInfo['annualizedAmount']) ? $extraInfo['annualizedAmount'] : 0;
                    if ($discount['status']==1 && $discountRateLog) {
                        Logger::info(implode(' | ', [__CLASS__, __FUNCTION__, 'HANDLED', $userId, $discountId, $dealLoadId]));
                        continue;
                    }

                    // 对于黄金相关类型，不需要处理
                    if ($discount['consume_type'] == CouponGroupEnum::CONSUME_TYPE_GOLD_ORDER) {
                        Logger::info(implode(' | ', [__CLASS__, __FUNCTION__, 'DEPENDING', $userId, $discountId, $dealLoadId]));
                        continue;
                    }

                    if ($discount['consume_type'] == CouponGroupEnum::CONSUME_TYPE_P2P) {
                        $dealId = DealLoadModel::instance()->findViaSlave($dealLoadId, '`deal_id`')->deal_id;
                        $deal = SiteApp::init()->dataCache->call(DealModel::instance(), 'findViaSlave', [$dealId, '`name`'], 600);
                        $dealName = $deal->name;
                    } else {
                        $dealName = '';
                    }

                    // $dealName = DealModel::instance()->findViaSlave($dealId, '`name`')->name;
                    Logger::info(implode(' | ', [__CLASS__, __FUNCTION__, 'FIXED', $userId, $discountId, $dealLoadId, $dealName]));
                    $this->discountService->consumeEvent($userId, $discountId, $dealLoadId, $dealName, 0,
                        $currentGoldPrice, $discount['consume_type'], $annualizedAmount);
                }
            }
        } catch (\Exception $e) {
            $this->tryTime--;
            Logger::info($e->getMessage());

            if ($this->tryTime == 0) {// 失败3次，记录日志跳出
                Logger::info(implode(" | ", [__CLASS__, __FUNCTION__, 'try 3 times still error', date('Y-m-d H:i', $this->endTime), $this->timeScope]));
                exit();
            }
            sleep(3);
            $this->run();// 重试
        }
    }

}

// 参数注册
$shortopts = "";
$shortopts .= "t:"; // 时间范围
$shortopts .= "s:"; // 开始时间

$longopts = ["help"];

// 获取参数
$opts = getopt($shortopts, $longopts);

if (isset($opts['help'])) {
    $str = <<<HELP
Usage: php o2o_exchange_discount.php [option]
对设定时间点（默认为当前时间）之前某时间范围内的O2O数据进行恢复
    -s 时间范围，默认120分钟，单位分钟
    -t 设定的时间点，默认当前时间，如果设定时间比当前时间晚，则以默认值为准，格式Y/m/d H:i

HELP;
    exit($str);
}

// 初始化
$timeScope = 3600 * 2;
if (isset($opts['s'])) {
    if (!filter_var($opts['s'], FILTER_VALIDATE_INT)) exit('-s 时间范围必须是整数' . PHP_EOL);
    if ($timeScope = intval($opts['s']) <= 0) exit('-s 时间范围不能比0小' . PHP_EOL);
    $timeScope = intval($opts['s']) * 60;
}

$endTime = time();
if (isset($opts['t'])) {
    $endTime = strtotime($opts['t']);
    if (!$endTime) exit('-t 开始时间格式应为"2015/10/29 11:11"' . PHP_EOL);
    $endTime = min($endTime, time());
}

$restore = new O2OExchangeDiscount($endTime, $timeScope);
$restore->run();
