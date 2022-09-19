<?php
/**
 * @desc 投资逻辑 投资不存在回滚
 * Date: 2017-02-23 17:13
 */

namespace core\tmevent\bid;

use core\service\P2pIdempotentService;
use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\DealBidService;
use core\service\UserService;
use core\dao\DealModel;
use core\dao\UserModel;
use core\data\DealData;

class P2pbidEvent extends GlobalTransactionEvent {

    public function __construct($orderId,$dealId,$userId,$money,$bidParams=array()){
        $this->orderId = $orderId;
        $this->dealId = $dealId;
        $this->userId = $userId;
        $this->money = $money;
        $this->bidParams = $bidParams;
    }

    /**
     * 库存扣减、标的状态更新、订单状态修改
     */
    public function execute(){
        $bidService = new DealBidService($this->orderId,$this->dealId,$this->userId,$this->money);
        try{
            $res = $bidService->bid($this->bidParams);

            /**
             * 因为存管投资存在同步和异步回调可能同步和异步两个进程同时发起了投资流程
             * 有几下几种情况
             *  1、同步投资在进行中，这时候异步回调到达。同步处理完成，那么异步处理会失败 异步失败会告知银行取消投资 导致两边数据不一致
             *  2、异步优先同步完成投资 这时候如果不做处理 那么同步投资失败，用户会看到投资失败 但实际上已经投资成功
             *  3、如果在订单处理的时候补通过affected_rows 控制那么同步和异步都处理成功了，导致很多其他逻辑重复执行 如：资金记录
             *
             * 处理方案
             *   在投资失败情况下在查询下订单，如果订单已经成功处理那么依然返回成功
             */
            if($res === false){
                $orderInfo = P2pIdempotentService::getInfoByOrderId($this->orderId);
                if(isset($orderInfo['result']) && $orderInfo['result'] == P2pIdempotentService::RESULT_SUCC){
                    \libs\utils\Logger::info("P2pbidEvent 同步或异步已经处理过 orderId:".$this->orderId." ,dealId:".$this->dealId.",userId:".$this->userId.",money:".$this->money);
                    $res = true;
                }
            }
        }catch (\Exception $ex){
            \libs\utils\Logger::error("投资失败 errMsg:".$ex->getMessage());
            $res  = false;
        }
        return $res;
    }
}