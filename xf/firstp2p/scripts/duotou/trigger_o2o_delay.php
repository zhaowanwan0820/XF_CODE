<?php
/**
 * ----------------------------------------------------------------------------
 * 1.延迟触发智多新礼券,每天晚10点后执行
 * 2.延迟返智多新邀请首投经验值
 *
 * ----------------------------------------------------------------------------
 */

set_time_limit(0);
ini_set('memory_limit','1024M');

require_once dirname(__FILE__)."/../../app/init.php";
require(APP_ROOT_PATH.'libs/utils/PhalconRPCInject.php');

use core\service\O2OService;
use core\service\UserService;
use core\service\CouponService;
use core\service\CouponBindService;
use core\service\BwlistService;
use NCFGroup\Protos\Duotou\Enum\DealLoanEnum;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\event\O2OExchangeDiscountEvent;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use core\service\vip\VipService;
use core\dao\DiscountModel;

\libs\utils\PhalconRpcInject::init();

class TriggerO2ODelay {
    private $couponService = null;
    private $couponBindService = null;
    private $referMap = null;
    private $count = 0;
    private $date = '';
    public $db;
    private $dbname = 'duotou';
    private $host = 'w-duotou.dbs.wxlc.org';
    private $username= 'dt_pro';
    private $password = '7ABF43cFB1Fd481';
    private $port = '3308';
    /* test
    private $host ='test03.firstp2plocal.com';
    private $username = 'firstp2p';
    private $password = '1234@abcd';
    private $port = '3306';
     */


    public function __construct($date) {
        $this->date = $date;
        $this->db = new Pdo("mysql:dbname=$this->dbname;host=$this->host;port=$this->port", $this->username, $this->password, array(PDO::ATTR_PERSISTENT => true));
        $this->couponService = new CouponService();
        $this->couponBindService = new CouponBindService();
    }

    public function run() {
        PaymentApi::log("trigger_o2o_delay, 智多新延迟触发礼券开始:".date('Y-m-d').", --date=".$this->date);
        $db = $this->db;
        $endTime = strtotime($this->date);
        $startTime = $endTime - 86400;
        $sql = 'SELECT id FROM duotou_deal_loan WHERE create_time>='.$startTime .' ORDER BY id ASC LIMIT 1';
        $startId = 0;
        $stmt = $db->query($sql);
        if ($stmt) {
            $startId = $stmt->fetchColumn();
        }

        $sql = 'SELECT id FROM duotou_deal_loan WHERE create_time>='.$startTime .' AND create_time<'. $endTime .' ORDER BY id DESC LIMIT 1';
        $stmt = $db->query($sql);
        $endId = 0;
        if ($stmt) {
            $endId = $stmt->fetchColumn();
        }

        $pageSize = 100;
        if (empty($startId) || empty($endId)){
            PaymentApi::log("trigger_o2o_delay, 智多新延迟触发礼券结束,无投资记录:");
            return true;
        }
        do{
            $loopId = $startId + $pageSize;
            if ($loopId >= $endId) {
                $loopId = $endId +1;
            }
            $sql = 'SELECT id, project_id, user_id, money, status, create_time, lock_period, activity_id, activity_rate, site_id FROM duotou_deal_loan WHERE id>=' .$startId. ' AND id<' .$loopId;
            $loads = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $discountSql = 'consume_id=":dealLoadId" AND consume_type='.CouponGroupEnum::CONSUME_TYPE_DUOTOU;
            foreach($loads as $item) {
                if ($item['status'] == DealLoanEnum::DEAL_LOAN_REVOKE) {
                    PaymentApi::log("trigger_o2o_delay ignore, id:".$item['id']);
                    continue;
                } else {
                    PaymentApi::log("trigger_o2o_delay check, id:".$item['id']);
                    try{
                        // 需要捕获异常，保证后续数据还能处理
                        $this->checkAndTrigger($item);
                    } catch (\Exception $e) {
                        PaymentApi::log("trigger_o2o_delay, err:".$e->getMessage());
                    }

                    try {
                        // 查询该笔交易是否用了优惠券
                        $record = DiscountModel::instance()->findBy($discountSql, 'discount_id,status', array(
                            ':dealLoadId' => $item['id']
                        ));

                        if ($record && $record['status'] != 1) {
                            $o2oExchangeDiscountEvent = new O2OExchangeDiscountEvent(
                                $item['user_id'],           // 用户id
                                $record['discount_id'],     // 优惠券id
                                $item['id'],                // 交易id
                                'duotou',                   // 交易名称
                                0,                          // 优惠码
                                0,                          // 金价
                                0,                          // 黄金订单id
                                CouponGroupEnum::CONSUME_TYPE_DUOTOU,// 交易类型
                                0,                          // 年化额
                                true                        // 是否延迟返利
                            );
                            // 兑换优惠券
                            $o2oExchangeDiscountEvent->execute();
                        }
                    } catch (\Exception $e) {
                        PaymentApi::log("o2o_exchange_discount, err:".$e->getMessage());
                    }
                }
            }
            $startId = $loopId;
        } while ($startId < $endId);

        PaymentApi::log("trigger_o2o_delay, 智多新延迟触发礼券结束,总共触发记录:".$this->count);
        return true;
    }

    private function checkAndTrigger($item) {
        $vipService = new VipService();
        $referUserId = $vipService->getReferUserId($item['user_id']);
        if (($referUserId > 0) && ($item['activity_id'] >0) && ($item['lock_period']>=30)) {
            //智多新邀请首投返经验值
            $sourceAmount=1;
            $sourceType = VipEnum::VIP_SOURCE_INVITE;
            $token = $sourceType.'_'.$item['user_id'];//一个用户最多只有一次被邀请触发vip经验的机会
            $info = '邀请'.$item['user_id'].'首投智多新奖励';
            $annualizedAmount = round(($item['money']/100) * $item['lock_period'] / 360, 2);
            $vipService->updateVipPoint($referUserId, $sourceAmount, $sourceType, $token, $info, $item['id'], 0, $annualizedAmount, $item['money']/100);
        }
        if ($referUserId && BwlistService::inList('DT_INVITER_BLACKLIST', $referUserId)) {
            //triggerO2O
            // 触发投资券、礼券
            $annualizedAmount = round(($item['money']/100) * $item['lock_period'] / 360, 2);
            $extra = array(
                'duotou_activity_id' => $item['activity_id'],
                'duotou_activity_rate' => $item['activity_rate'],
                'duotou_lock_period' => $item['lock_period'],
                'dealBidDays' => $item['lock_period'],
                'dealTag' => array('DEAL_DUOTOU')
            );
            $action = CouponGroupEnum::TRIGGER_DUOTOU_REPEAT_DOBID;

            $sql = 'SELECT id FROM duotou_deal_loan WHERE user_id=' .$item['user_id']. ' AND project_id='.$item['project_id']. ' AND id<'.$item['id'];
            $hasBid = $this->db->query($sql)->fetchColumn();
            if (!$hasBid) {
                $action = CouponGroupEnum::TRIGGER_DUOTOU_FIRST_DOBID;
            }
            PaymentApi::log("trigger_o2o_delay, 智多新延迟触发信息:".json_encode($item,JSON_UNESCAPED_UNICODE));
            O2OService::triggerO2OOrder(
                $item['user_id'],
                $action,
                $item['id'],
                0,
                $item['money']/100,
                $annualizedAmount,
                CouponGroupEnum::CONSUME_TYPE_DUOTOU,
                CouponGroupEnum::TRIGGER_TYPE_P2P,
                $extra
            );
            $this->count++;
        }
        return true;
    }
}
$shortopts = "";
$longopts = array(
    "date::",   // 指定运行的日期
    "help",     // 帮助
);

// 获取参数
$opts = getopt($shortopts, $longopts);
if (isset($opts['help'])) {
    $str = <<<HELP
Usage: php trigger_o2o_delay.php [args...]
    --date=2018-06-21 指定运行的日期,例如2018-06-21
    --help 帮助
HELP;
    exit($str.PHP_EOL);
}

// 默认取当前时间
$date   = isset($opts['date']) ? $opts['date'] : date('Y-m-d');

try {
    $task = new TriggerO2ODelay($date);
    $task->run();
} catch (\Exception $ex) {
    $params = array('date'=>$date);
    echo 'trigger_o2o_delay: '.$ex->getMessage().', params: '.json_encode($params).PHP_EOL;
    PaymentApi::log('trigger_o2o_delay: '.$ex->getMessage().', params: '.json_encode($params), Logger::ERR);
}
