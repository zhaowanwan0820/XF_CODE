<?php
/**
 * 修复提前还款取消导致的用户回款日历变为负值的问题
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);
require dirname(__FILE__).'/../../app/init.php';

use \libs\Db\Db;
use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use core\service\DtBidService;
use core\dao\IdempotentModel;
use libs\utils\Logger;
use core\service\DtPaymenyService;
use core\service\DtDepositoryService;
use core\service\DtDealService;
use NCFGroup\Common\Library\Idworker;
use libs\utils\Rpc;






class Fix {
    // 重新发起红包消费


    /**
     红包格式 需要将下面数据encode 并替换相应值
    Array
    (
    'money' => 20.00,
    'bonuses' => Array(),
    'accountInfo' => Array
    (
    0 => Array(
    'rpUserId' => 8229594,
    'rpAmount' => 20.00,
    'rpSubOrderId' => 170933584192869175,
    )
    )
    )
     */
    function fixBonusRecord($params){
        $userId = $params[0];
        $bonusInfo = json_decode($params[1],true);
        $globalOrderId = $params[2];
        $dealName = $params[3];

        $gtm = new GlobalTransactionManager();
        $gtm->setName("bonusRepair");
        $gtm->addEvent(new \core\tmevent\bid\BonusConsumeEvent($userId,$bonusInfo,$globalOrderId,$dealName));
        $res = $gtm->execute();
        if($res){
            Logger::info(__CLASS__ . ",". __FUNCTION__ . " succ 重新发起红包消费成功");
        }else{
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " fail 重新发起红包消费失败");
        }
    }

    /**
     * 红包消费补充资金记录
     * 红包的信息可以通过存管接口日志查看
     * 示例: /apps/php/bin/php scripts/onetime/fix_bonus_record.php fixBounsMoneyLog  7402833 '{"money":"6103.92","bonuses":[],"accountInfo":[{"rpUserId":"8229699","rpAmount":"6103.92","rpSubOrderId":169017395665445149}]}' 169017393878668065 1004
     * @param $params
     * @throws Exception
     */
    function fixBounsMoneyLog($params){
        $userId = $params[0];
        $bonusInfo = json_decode($params[1],true);
        $globalOrderId = $params[2];
        $dealName = $params[3];
        $ss = new DtBidService();
        $res2 = $ss->bidBonusTransfer($globalOrderId,$bonusInfo, $userId, $dealName);
        if($res2){
            Logger::info(__CLASS__ . ",". __FUNCTION__ . " succ 红包资金记录补成功");
        }else{
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " fail 红包资金记录补失败");
        }
    }


    function zdxZhaiZhuanReverse($params){
        $oriOrderId = $params[0];

        $service = new DtDepositoryService();

        $sql = "SELECT * FROM `firstp2p_idempotent` WHERE source='duotou_depository_redeem' AND token='{$oriOrderId}'";

        $data = IdempotentModel::instance()->findBySqlViaSlave($sql);
        $data = json_decode($data['data'],true);

        $batchId = Idworker::instance()->getId();
        $subId = Idworker::instance()->getId();

        $creditOrderList = array(
            "bidId" => $data['dealId'],
            "subOrderId" => $subId,
            "assignorUserId" => $data['userId'],
            "assigneeUserId" => $data['redeemUserId'],
            "amount" => $data['money'],
            "dealAmount" => $data['money'],
            "freezeType" => "SI",
        );

        $requestData = array(
            "totalAmount" =>  $data['money'],
            "dealTotalAmount" => $data['money'],
            "totalNum" => 1,
            "creditOrderList" => array($creditOrderList),
        );

        $tableIndex =  $data['tableIndex'];
        $date = $data['date'];
        try{
            $res = $service->sendDtTransBondRequest($batchId,$requestData,$tableIndex,$date);
            Logger::info(__CLASS__ . ",". __FUNCTION__ . " succ 智多鑫债转数据成功");
        }catch(\Exception $ex){
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " fail ".$ex->getMessage());
        }
    }

    function zdxRedoZhaiZhuan($params){
        $oriOrderId = $params[0];

        $service = new DtDepositoryService();

        $sql = "SELECT * FROM `firstp2p_idempotent` WHERE source='duotou_depository_redeem' AND token='{$oriOrderId}'";

        $data = IdempotentModel::instance()->findBySqlViaSlave($sql);

        $data = json_decode($data['data'],true);

        $batchId = Idworker::instance()->getId();
        $subId = Idworker::instance()->getId();

        $creditOrderList = array(
            "bidId" => $data['dealId'],
            "subOrderId" => $subId,
            "assignorUserId" => $data['redeemUserId'],
            "assigneeUserId" => $data['userId'],
            "amount" => $data['money'],
            "dealAmount" => $data['money'],
            "freezeType" => "SI",
        );

        $requestData = array(
            "totalAmount" =>  $data['money'],
            "dealTotalAmount" => $data['money'],
            "totalNum" => 1,
            "creditOrderList" => array($creditOrderList),
        );

        $tableIndex =  $data['tableIndex'];
        $date = $data['date'];
        try{
            $res = $service->sendDtTransBondRequest($batchId,$requestData,$tableIndex,$date);
            // 成功后作废老订单
            $sql = "update  `firstp2p_idempotent`set status = 2  WHERE source='duotou_depository_redeem' AND token='{$oriOrderId}'";
            $res =  IdempotentModel::instance()->updateRows($sql);
            Logger::info(__CLASS__ . ",". __FUNCTION__ . " succ 智多鑫债转数据成功");
        }catch(\Exception $ex){
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " fail ".$ex->getMessage());
        }
    }

    /**
     * 补充智多鑫债转资金记录
     * 示例:/apps/php/bin/php fix_bonus_record.php zdxZhaiZhuanMoneyLog  170117616541638792 11236355 4218865 217.05 4322343
     * /apps/php/bin/php fix_bonus_record.php zdxZhaiZhuanMoneyLog      170420603851907135 11236355 2123030  113.79 4322343
     * $orderId:firstp2p_idempotent 中的token
     * $userId : firstp2p_idempotent 中的 data userId
     * $redeemUserId : firstp2p_idempotent 中的 data redeemUserId
     * $money : firstp2p_idempotent 中的 data money
     * $p2pDealId : firstp2p_idempotent 中的 data dealId
     */
    function zdxZhaiZhuanMoneyLog($params){
        $orderId    = $params[0];
        $userId     = $params[1];
        $redeemUserId = $params[2];
        $money      = $params[3];
        $p2pDealId  = $params[4];

        try{
            $dtDealService = new DtDealService();
            $res = $dtDealService->dealRedeemMoneyLog($orderId,$userId,$redeemUserId,$money,$p2pDealId);
        }catch (\Exception $ex){
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " fail 智多鑫债转资金记录补偿失败 errMsg:".$ex->getMessage());
            return false;
        }
        Logger::info(__CLASS__ . ",". __FUNCTION__ . " succ 智多鑫债转资金记录补成功");
        return true;
    }

    function zdxDelToken($params){
        $token = $params[0];
        $userId = $params[1];
        if(!$token || !$userId){
            echo "Params error";exit;
        }

        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $request->setVars(array(
                        'token' => $token,
                        'userId' => $userId,
                    ));
        $rpc = new Rpc('duotouRpc');
        $cancResponse = $rpc->go('NCFGroup\Duotou\Services\DealLoan', 'rollbackDealLoan', $request, 2, 3);
        if(!$cancResponse){
            echo "系统繁忙，请稍后再试";exit;
        }

        if(isset($cancResponse['data']) && $cancResponse['data'] === false){
            if($cancResponse['errMsg']){
                echo "智多鑫投资取消失败";exit;
            }
        }
        echo "SUCC";
    }

    public function changeDealStatus($params){
        $dealId = $params[0];
        if(!$dealId){
            echo "Miss dealId";exit;
        }
        $deal = \core\dao\DealModel::instance()->find($dealId);
        $deal->is_during_repay = 0;
        $res = $deal->save();
        echo $res ? "SUCC" : "FAIL";
    }

}


$method = isset($argv[1]) ? trim($argv[1]) : "";
if(empty($method)){
    die("Please input method name!");
}
$params = array_slice($argv,2);


// 7402833 用户 投资orderId:169017393878668065
//$bonusInfo = array
//(
//    'money' => "6103.92",
//    'bonuses' => Array(),
//    'accountInfo' => Array
//    (
//        0 => Array(
//            'rpUserId' => "8229699",
//            'rpAmount' => "6103.92",
//            'rpSubOrderId' => 169017395665445149,
//        )
//    )
//);

// /apps/php/bin/php scripts/onetime/fix_bonus_record.php fixBonusRecord 11325975 '{"money":"88.00","bonuses":[],"accountInfo":[{"rpUserId":"8229594","rpAmount":"88.00","rpSubOrderId":169767164478231031}]}' 169767162427216459 1004



//用户 投资orderId: 11325975 169767162427216459
//$bonusInfo = array
//(
//    'money' => "88.00",
//    'bonuses' => Array(),
//    'accountInfo' => Array
//    (
//        0 => Array(
//            'rpUserId' => "8229594",
//            'rpAmount' => "88.00",
//            'rpSubOrderId' => 169767164478231031,
//        )
//    )
//);
//

$class = new Fix();
$res= $class->$method($params);
