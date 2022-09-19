<?php
/**
 * ----------------------------------------------------------------------------
 * 1.智多新邀请首投经验值补发:按照5000信力补
 *
 * ----------------------------------------------------------------------------
 */

set_time_limit(0);
ini_set('memory_limit','1024M');

require_once dirname(__FILE__)."/../../app/init.php";

use core\service\CouponService;
use core\service\CouponBindService;
use NCFGroup\Protos\Duotou\Enum\DealLoanEnum;
use libs\utils\PaymentApi;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use core\service\candy\CandyActivityService;

class Resend {
    private $couponService = null;
    private $couponBindService = null;
    private $referMap = null;
    private $count = 0;
    public $db;
    private $dbname = 'duotou';
    private $host = 'w-duotou.dbs.wxlc.org';
    private $username= 'dt_pro';
    private $password = '7ABF43cFB1Fd481';
    private $port = '3308';
    /*
    private $host ='test03.firstp2plocal.com';
    private $username = 'firstp2p';
    private $password = '1234@abcd';
    private $port = '3306';
     */


    public function __construct() {
        $this->db = new Pdo("mysql:dbname=$this->dbname;host=$this->host;port=$this->port", $this->username, $this->password, array(PDO::ATTR_PERSISTENT => true));
        $this->couponService = new CouponService();
        $this->couponBindService = new CouponBindService();
    }

    public function run() {
        PaymentApi::log("dt_resend_xinli, 智多新补发邀请信力开始:".date('Y-m-d'));
        $db = $this->db;
        $startTime = strtotime('20180710');
        $endTime = strtotime('20180716');
        $sql = 'SELECT id FROM duotou_deal_loan WHERE create_time>='.$startTime .' ORDER BY id ASC LIMIT 1';
        $startId = $db->query($sql)->fetchColumn();
        $sql = 'SELECT id FROM duotou_deal_loan WHERE create_time>='.$startTime .' AND create_time<'. $endTime .' ORDER BY id DESC LIMIT 1';
        $endId = $db->query($sql)->fetchColumn();
        $pageSize = 100;
        if (empty($startId) || empty($endId)){
            PaymentApi::log("dt_resend_xinli, 智多新延迟触发礼券结束,无投资记录:");
            return true;
        }
        do{
            $loopId = $startId + $pageSize;
            if ($loopId >= $endId) {
                $loopId = $endId +1;
            }
            $sql = 'SELECT id, project_id, user_id, money, status, create_time, lock_period, activity_id, activity_rate, site_id FROM duotou_deal_loan WHERE id>=' .$startId. ' AND id<' .$loopId;
            $loads = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            foreach($loads as $item) {
                if ($item['status'] == DealLoanEnum::DEAL_LOAN_REVOKE) {
                    continue;
                } else {
                    $this->checkAndTrigger($item);
                }
            }
            $startId = $loopId;
        } while ($startId < $endId);
        PaymentApi::log("dt_resend_xinli, 智多新邀请首投经验值补发结束,总共触发记录:".$this->count);
        return true;
    }

    private function checkAndTrigger($item) {
        $referUserId = 0;
        $coupon_bind_service = $this->couponBindService;
        $coupon_bind = $coupon_bind_service->getByUserId($item['user_id']);
        if (!empty($coupon_bind)) {
            $short_alias = $coupon_bind['short_alias'];
            if (isset($this->referMap[$short_alias])) {
                $referUserId = $this->referMap[$short_alias];
            } else {
                if (!empty($short_alias)) {
                    $couponService = $this->couponService;
                    $referUserId = $couponService->getReferUserId($short_alias);
                    $this->referMap[$short_alias] = $referUserId;
                }
            }
        }
        if ($referUserId > 0) {
            //智多新邀请首投返经验值
            $sourceType = VipEnum::VIP_SOURCE_INVITE;
            $token = $sourceType.'_'.$item['user_id'];//一个用户最多只有一次被邀请触发vip经验的机会
            $info = '邀请'.$item['user_id'].'首投智多新奖励补发';
            $activity = 5000;
            try{
                $candyActivityService = new CandyActivityService();
                $candyActivityService->activityCreateByToken($token, $referUserId, $activity, CandyActivityService::SOURCE_TYPE_INVITE, $info);
                $this->count++;
            } catch (\Exception $ex) {
                if ($ex->getCode() == 100001) {
                    echo $token."重复\n";
                    return;
                }
            }
        }
        return true;
    }
}

$task = new Resend();
$task->run();
