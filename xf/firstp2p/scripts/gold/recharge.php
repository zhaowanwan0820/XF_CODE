<?php


require_once(dirname(__FILE__) . '/../../app/init.php');

FP::import("libs.utils.logger");
FP::import("libs.common.dict");

set_time_limit(0);
ini_set('memory_limit', '256M');
error_reporting(E_ALL ^ E_NOTICE);
use NCFGroup\Common\Library\Idworker;


class recharge {
    
    static public $userList = array('6088');
    static public $buyAmount = '0.05';
    static public $wxUserId = '501332';
    public function run(){
        $result = (new core\service\GoldService())->getGoldPrice();
        $buyPrice = $result['data']['gold_price'];
        foreach (self::$userList as $userId){
            $this->doRecharge($userId,self::$buyAmount,$buyPrice,self::$wxUserId);
        }
    }

    private function doRecharge($userId,$buyAmount,$buyPrice,$wxUserId){
        $orderId = Idworker::instance()->getId();;
        $goldBidRechargeService = new core\service\GoldBidRechargeService($userId,$buyAmount, $buyPrice,'',$orderId,$wxUserId);
        $result  = $goldBidRechargeService->doBid();
    }

}

$recharge = new recharge();
$recharge->run();
