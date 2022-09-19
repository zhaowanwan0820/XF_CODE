<?php
/**
 * @desc 存管行投资逻辑
 * Date: 2017-02-23 17:13
 */
namespace core\tmevent\bid;

use core\service\P2pDealBidService;
use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\SupervisionDealService;

class BankbidEvent extends GlobalTransactionEvent {

    /**
     * 订单ID 唯一标识
     */
    private $orderId;

    /**
     * 标的ID
     */
    private $dealId;

    /**
     * 投资人ID
     */
    private $userId;

    /**
     * 投资金额
     */
    private $totalAmount;

    /**
     * 使用的红包总金额
     */
    private $bonusAmount;

    /**
     * 使用账户金额
     */
    private $accAmount;

    /**
     * 红包信息
     */
    private $bonusInfo;

    /**
     * 红包流向  01-返还到红包账户 02-留在投资人账户
     */
    private $rpDirect = '01';

    /**
     * @param $orderId
     * @param $dealId
     * @param $userId
     * @param $totalAmount
     * @param $accAmount
     * @param $rpOrderList
     * @param $rpDirect
     */
    public function __construct($orderId,$dealId,$userId,$totalAmount,$bonusInfo) {
        $bonusAmount = $bonusInfo['money'];
        $this->accAmount = bcsub($totalAmount,$bonusAmount,2);
        $this->orderId = $orderId;
        $this->dealId = $dealId;
        $this->userId = $userId;
        $this->totalAmount = $totalAmount;
        $this->bonusAmount = $bonusAmount;
        $this->bonusInfo = $bonusInfo;
    }

    public function execute(){
        $dealService = new P2pDealBidService();
        return $dealService->dealBidRequest($this->orderId,$this->dealId,$this->userId,$this->totalAmount,$this->accAmount,$this->bonusInfo);
    }

    public function rollback() {
        $dealService = new P2pDealBidService();
        return $dealService->dealBidCancelRequest($this->orderId);
    }
}