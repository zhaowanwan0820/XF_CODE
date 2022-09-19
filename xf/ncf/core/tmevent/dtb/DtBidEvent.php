<?php
/**
 * @desc 多投投资逻辑
 * Date: 2017-6-10 10:57:23
 */

namespace core\tmevent\dtb;

use core\service\duotou\DtBidService;
use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;


class DtBidEvent extends GlobalTransactionEvent {

    public function __construct($orderId,$dealId,$dealName,$userId,$money,$bidParams){
        $this->orderId = $orderId;
        $this->dealId = $dealId;
        $this->dealName = $dealName;
        $this->userId = $userId;
        $this->money = $money;
        $this->bidParams = $bidParams;
    }

    // 智多鑫投资
    public function execute(){
        try{
            $bidService = new DtBidService();
            return $bidService->dtBid($this->orderId,$this->userId,$this->dealId,$this->dealName,$this->money,$this->bidParams);
        }catch (\Exception $ex){
            if($ex->getCode() == -1){
                throw $ex;
            }else{
                $this->setError($ex->getMessage());
                return false;
            }
        }
    }

    public function rollback()
    {
        $bidService = new DtBidService();
        return $bidService->bidFail($this->orderId,$this->userId);
    }
}