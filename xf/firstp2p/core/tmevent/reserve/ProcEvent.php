<?php
/**
 * @desc 随心约处理
 */
namespace core\tmevent\reserve;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\UserReservationService;
use libs\utils\Logger;

class ProcEvent extends GlobalTransactionEvent {

    private $userReservationService;

    //预约单号
    private $reserveId;

    //用户id
    private $userId;

    //标的id
    private $dealId;

    //投资金额
    private $investAmount;

    //订单id
    private $orderId;

    /**
     * @param $reserveId
     * @param $userId
     * @param $investAmount
     */
    public function __construct($reserveId, $userId, $investAmount, $dealId, $orderId) {
        $this->reserveId = $reserveId;
        $this->userId = $userId;
        $this->investAmount = $investAmount;
        $this->dealId = $dealId;
        $this->orderId = $orderId;
        $this->userReservationService = new UserReservationService();
    }

    /**
     * 锁定资源
     */
    public function execute(){
        return $this->userReservationService->processPrepare($this->reserveId, $this->userId, $this->investAmount, $this->orderId);
    }

    /**
     * 释放资源
     */
    public function rollback() {
        return $this->userReservationService->restoreProcStatus($this->reserveId, $this->userId, $this->orderId);
    }

    /**
     * 处理完成
     */
    public function commit(){
        return $this->userReservationService->processComplete($this->reserveId, $this->userId, $this->dealId, $this->investAmount, $this->orderId);
    }

}
