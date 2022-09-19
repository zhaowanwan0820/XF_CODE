<?php
/**
 * 智多新加息[新]
 * 1.上一日投资的锁定期内的资产，按锁定期一次加息
 * 2.对当天转让成功的进行加息
 */

ini_set("display_errors", 1);
error_reporting(E_ALL);

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once dirname(__FILE__).'/../../app/init.php';

use core\service\vip\VipService;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use core\dao\vip\VipRateLogModel;

class VipDtTask {
    private $date = '';
    private $dtDb = null;

    private $dbname = 'duotou';
    private $host ='';
    private $username = '';
    private $password = '';
    private $port = '3306';
    private $vipService = null;
    private $couponGroupId = null;
    private $lockKey = '_0';//锁定期补息的token后缀
    private $dateStartTime = '';
    private $dateEndTime = '';
    private $pageSize = 100;

    public function __construct($date) {
        $this->couponGroupId = app_conf('COUPON_GROUP_ID_VIP_REBATE_DT');
        $this->date = $date;
        $this->dateStartTime = strtotime($date);
        $this->dateEndTime = $this->dateStartTime + 86400;
        $this->vipService = new VipService();
        $env = get_cfg_var("phalcon.env");
        if ($env == 'product') {
            $this->host = 'r-duotou.dbs.wxlc.org';
            $this->username = 'dt_pro';
            $this->password = '7ABF43cFB1Fd481';
            $this->port = '3308';
        } else {
            $this->host = 'test03.firstp2plocal.com';
            $this->username = 'firstp2p';
            $this->password = '1234@abcd';
        }
        $this->dtDb = new Pdo("mysql:dbname=$this->dbname;host=$this->host;port=$this->port", $this->username, $this->password, array(PDO::ATTR_PERSISTENT => true));
    }

    private function getMaxLogId() {
        $sql = "SELECT id FROM `duotou_deal_loan` WHERE status <= 3 ORDER BY id DESC limit 1";
        return $this->dtDb->query($sql)->fetchColumn();
    }

    private function getMinLogId() {
        $sql = "SELECT id FROM `duotou_deal_loan` WHERE status <= 3 ORDER BY id ASC limit 1";
        return $this->dtDb->query($sql)->fetchColumn();
    }

    /**
     * getDtLoadList 获取智多新投资记录(1-投资成功 2-匹配成功 3-赎回申请中 4-赎回成功 5-已结清)
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-11-12
     * @param mixed $startId
     * @param mixed $endId
     * @access private
     * @return void
     */
    private function getDtLoadList($startId, $endId) {
        $sql = "SELECT id, user_id, money, status, create_time, lock_period FROM `duotou_deal_loan`
            WHERE id>= $startId AND id < $endId AND status <=3";
        return $this->dtDb->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * getDtLoad
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2019-03-14
     * @param mixed $dealLoadId
     * @access private
     * @return void
     */
    private function getDtLoad($dealLoadId) {
        $sql = "SELECT * FROM `duotou_deal_loan` WHERE id=".$dealLoadId;
        return $this->dtDb->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * getRedemCount 获取当天转让成功的智多新总数
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2019-03-14
     * @access private
     * @return void
     */
    private function getRedemCount() {
        $sql = "SELECT count(*) FROM `duotou_redemption_apply` where status = 4 and finish_time >= {$this->dateStartTime} and finish_time < {$this->dateEndTime}";
        return $this->dtDb->query($sql)->fetchColumn();
    }

    /**
     * getRedemList 获取当天转让成功的智多新记录
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2019-03-14
     * @param mixed $offset
     * @param mixed $pageSize
     * @access private
     * @return void
     */
    private function getRedemList($offset, $pageSize) {
        $sql = "SELECT loan_id FROM `duotou_redemption_apply` where status = 4 and finish_time >= {$this->dateStartTime} and finish_time < {$this->dateEndTime} limit $offset,$pageSize";
        return $this->dtDb->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * checkResendRaiseRate 锁定期补息
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2019-03-14
     * @param mixed $dtLoad
     * @access private
     * @return void
     */
    private function checkResendRaiseRate($dtLoad) {
        //检查是否需要补息
        //1.持有的还在锁定期的，按锁定期一次补息
        //2.其他的不处理
        $executeTime = strtotime($this->date);
        if ($dtLoad['create_time'] >= $executeTime) {
            return true;
        }

        $lockPeriodEndTime = $dtLoad['create_time'] + $dtLoad['lock_period'] * 86400;
        if (($dtLoad['lock_period'] > 1) && ($lockPeriodEndTime >= $executeTime)){
            //1.持有的还在锁定期的，按锁定期一次补息
            $bidDays = $dtLoad['lock_period'];
        } else {
            //2.其他情况不补息
            return true;
        }

        $userId = $dtLoad['user_id'];
        $dealLoadId = $dtLoad['id'];
        $sourceType = VipEnum::VIP_SOURCE_DT;
        $money = bcdiv($dtLoad['money'] , 100, 2);//分转成元
        //money存的是分
        $divNum = 360 * 100;//年化除数，金额是分，360是天数
        $annualizedAmount = bcdiv($dtLoad['money'] * $bidDays, $divNum, 2);
        $token = $sourceType. '_'. $userId. '_'. $dealLoadId.$this->lockKey;

        return $this->vipService->vipRaiseInterest($userId, $money, $annualizedAmount, $token, $sourceType, $this->couponGroupId);
    }

    /**
     * computeAndRaiseRate转让成功后的加息
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2019-03-14
     * @param mixed $dealLoadId
     * @access private
     * @return void
     */
    private function computeAndRaiseRate($dealLoadId) {
        //转让成功后的加息
        //1.检查是否有锁定期内的加息
        //2.有锁定期加息的，按(持有天数-锁定期)加息
        //3.没有锁定期加息的，按持有天数加息
        $dealLoad = $this->getDtLoad($dealLoadId);
        if (!$dealLoad) {
            return;
        }

        $lockPeriod = $dealLoad['lock_period'];
        $bidDays = floor(($this->dateStartTime - $dealLoad['create_time'] )/86400);
        $sourceType = VipEnum::VIP_SOURCE_DT;
        $userId = $dealLoad['user_id'];
        $token = $sourceType. '_'. $userId. '_'. $dealLoadId;
        if ($lockPeriod > 1) {
            $lockToken = $token. $this->lockKey;
            $rateLog = VipRateLogModel::instance()->getVipRateLogByToken($lockToken);
            if ($rateLog) {
                //锁定期利息已补，需要扣减锁定期
                $bidDays = $bidDays - $lockPeriod;
            }
        }
        if ($bidDays <= 0) {
            return;
        }
        $money = bcdiv($dealLoad['money'] , 100, 2);//分转成元
        $divNum = 360 * 100;//年化除数，金额是分，360是天数
        $annualizedAmount = bcdiv($dealLoad['money'] * $bidDays, $divNum, 2);
        return $this->vipService->vipRaiseInterest($userId, $money, $annualizedAmount, $token, $sourceType, $this->couponGroupId);
    }

    // 脚本执行
    public function run() {
        echo 'VipDtTask begin, time: '.date('Y-m-d H:i:s').PHP_EOL;
        PaymentApi::log('VipDtTask begin, time: '.date('Y-m-d H:i:s'));
        if (empty($this->couponGroupId)) {
            PaymentApi::log('VipDtTask 未配置加息券组');
            return;
        }

        //对仍持有的带锁定期的进行补息
        $maxId = intval($this->getMaxLogId()) + 1;
        $minId = intval($this->getMinLogId());
        $step = 3000;
        do {
            $startId = $minId;
            $endId = $startId + $step;
            $loadList = $this->getDtLoadList($startId, $endId);
            foreach ($loadList as $dtLoad) {
                try{
                    $this->checkResendRaiseRate($dtLoad);
                } catch (\Exception $e) {
                    PaymentApi::log('VipDtTask err userId:'.$dtLoad['user_id'].' msg:'.$e->getMessage());
                    continue;
                }
            }
            $minId = $endId;
        } while ($minId < $maxId);

        //对当天转让成功的进行加息
        $total = $this->getRedemCount();
        $totalPage = ceil($total/$this->pageSize);
        for($i = 1; $i <= $totalPage; $i++) {
            $offset = ($i-1) * $this->pageSize;
            $redemList = $this->getRedemList($offset, $this->pageSize);
            if ($redemList) {
                foreach($redemList as $item) {
                    $dealLoadId = $item['loan_id'];
                    try{
                        $this->computeAndRaiseRate($dealLoadId);
                    } catch (\Exception $e) {
                        PaymentApi::log('VipDtTask err userId:'.$dtLoad['user_id'].' msg:'.$e->getMessage());
                        continue;
                    }
                }
            }
        }
        
        echo 'VipDtTask end, time: '.date('Y-m-d H:i:s').PHP_EOL;
        PaymentApi::log('VipDtTask end, time: '.date('Y-m-d H:i:s'));
    }
}

$shortopts = "";

$longopts = array(
    "date::",//执行日期
    "help",
);

// 获取参数
$opts = getopt($shortopts, $longopts);

if (isset($opts['help'])) {
    $str = <<<HELP
Usage: php vip_dt_raise_rate.php [args...]
    --date=20181106[执行该天的任务]
    --help 帮助
HELP;
    exit($str.PHP_EOL);
}

$date = isset($opts['date']) ? $opts['date'] : date('Ymd');

try {
    $vip = new VipDtTask($date);
    $vip->run();
} catch (\Exception $ex) {
    $params = array('date' => $date);

    echo 'VipDtTask: '.$ex->getMessage().', params: '.json_encode($params).PHP_EOL;
    PaymentApi::log('VipDtTask: '.$ex->getMessage().', params: '.json_encode($params), Logger::ERR);
}

