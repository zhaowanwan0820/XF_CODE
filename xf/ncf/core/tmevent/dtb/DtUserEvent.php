<?php
/**
 * @desc 多投用户扣钱逻辑
 */

namespace core\tmevent\dtb;

use core\service\duotou\DtBidService;
use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;


class DtUserEvent extends GlobalTransactionEvent {

    public function __construct($orderId,$dealId,$dealName,$userId,$money,$bidParams){
        $this->orderId = $orderId;
        $this->dealId = $dealId;
        $this->dealName = $dealName;
        $this->userId = $userId;
        $this->money = $money;
        $this->bidParams = $bidParams;
        $this->service = new DtBidService();
    }

    // 智多鑫投资
    public function execute(){
        try{
            return $this->service->dtUser($this->orderId,$this->userId,$this->dealId,$this->dealName,$this->money,$this->bidParams);
        }catch (\Exception $ex){
            if($ex->getCode() == -1){
                throw $ex;
            }else{
                $this->setError($ex->getMessage());
                return false;
            }
        }
    }
}
