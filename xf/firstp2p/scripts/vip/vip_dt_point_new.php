<?php
/**
 * 智多新每日加经验值[新]
 * 1.上一日投资的锁定期内的资产，按锁定期一次加经验值
 * 2.解除锁定仍持有的资产，按天计算经验值
 *
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

class VipDtTask {
    private $date = '';
    private $dtDb = null;

    private $dbname = 'duotou';
    private $host ='';
    private $username = '';
    private $password = '';
    private $port = '3306';
    private $vipService = null;

    public function __construct($date) {
        $this->date = $date;
        $this->vipService = new VipService();
        $env = get_cfg_var("phalcon.env");
        if ($env == 'product') {
            $this->host = 'r-duotou.dbs.wxlc.org';
            $this->username = 'dt_pro';
            $this->password = '7ABF43cFB1Fd481';
            $this->port = '3308';
        } else {
            $this->host = 'test24.firstp2plocal.com';
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
        PaymentApi::log('VipDtTask sql: '.$sql);
        return $this->dtDb->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    private function computeAndAddPoint($dtLoad) {
        //锁定期内的，按锁定期一次加经验值
        //锁定期结束后，按天结算
        //money存的是分
        $executeTime = strtotime($this->date);
        if ($dtLoad['create_time'] >= $executeTime) {
            return true;
        }
        $executeLastDay = $executeTime - 86400;
        $lockPeriodEndTime = $dtLoad['create_time'] + $dtLoad['lock_period'] * 86400;
        $userId = $dtLoad['user_id'];
        $dealLoadId = $dtLoad['id'];
        $sourceType = VipEnum::VIP_SOURCE_DT;
        if ($dtLoad['lock_period'] <= 1) {
            //持有的灵活投，直接按日结算
            $sourceAmount = bcdiv($dtLoad['money'] , 36000, 2);
            $token = $sourceType. '_'. $userId. '_'. $dealLoadId.'_'.$this->date;
            $info = '智多新，'.$this->date. ';交易ID:'.$dealLoadId;
        } else if (($dtLoad['create_time'] >= $executeLastDay) && ($dtLoad['create_time'] < $executeTime)) {
            //上一日投资的带锁定期的资产
            $sourceAmount = bcdiv($dtLoad['money'] * $dtLoad['lock_period'] , 36000, 2);
            $token = $sourceType. '_'. $userId. '_'. $dealLoadId;
            $info = '智多新，'.$dealLoadId;
        } else if (($dtLoad['create_time'] < ($executeLastDay)) && ($lockPeriodEndTime >= $executeTime)){
            //之前投资的仍在锁定期的，按照剩余期限一次补齐
            $leftDay = ceil(($lockPeriodEndTime - $executeTime)/86400);
            $sourceAmount = bcdiv($dtLoad['money'] * $leftDay, 36000, 2);
            $token = $sourceType. '_'. $userId. '_'. $dealLoadId;
            $info = '智多新，'.$dealLoadId.',补偿天数:'.$leftDay;
        } else {
            //已经过锁定期仍持有的，按天计算经验值
            $sourceAmount = bcdiv($dtLoad['money'] , 36000, 2);
            $token = $sourceType. '_'. $userId. '_'. $dealLoadId.'_'.$this->date;
            $info = '智多新，'.$this->date. ';交易ID:'.$dealLoadId;
        }
        return $this->vipService->updateVipPoint($dtLoad['user_id'], $sourceAmount, $sourceType, $token, $info);

    }

    // 脚本执行
    public function run() {
        $confStartTime = app_conf('VIP_DT_NEW_POINT_START');
        if ($this->date < $confStartTime || strtotime($this->date) > time()) {
            $err = 'VipDtTask error, 执行日期错误: 需要介于'.$confStartTime.'~'.date('Ymd');
            echo $err.PHP_EOL;
            PaymentApi::log('VipDtTask error, '.$err);
            return;
        }

        echo 'VipDtTask begin, time: '.date('Y-m-d H:i:s').PHP_EOL;
        PaymentApi::log('VipDtTask begin, time: '.date('Y-m-d H:i:s'));

        $maxId = intval($this->getMaxLogId()) + 1;
        $minId = intval($this->getMinLogId());
        $step = 3000;
        do {
            $startId = $minId;
            $endId = $startId + $step;
            $loadList = $this->getDtLoadList($startId, $endId);
            foreach ($loadList as $dtLoad) {
                try{
                    $this->computeAndAddPoint($dtLoad);
                } catch (\Exception $e) {
                    PaymentApi::log('VipDtTask err userId:'.$dtLoad['user_id'].' msg:'.$e->getMessage());
                    continue;
                }
            }
            $minId = $endId;
        } while ($minId < $maxId);
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
Usage: php vip_dt_point_new.php [args...]
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
