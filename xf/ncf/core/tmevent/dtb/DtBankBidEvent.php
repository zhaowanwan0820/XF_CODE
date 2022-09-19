<?php
/**
 * @desc 智多鑫 投资逻辑
 * Date: 2017-6-10 09:12:42
 */
namespace core\tmevent\dtb;

use core\service\DtDepositoryService;
use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\duotou\DtP2pDealBidService;
use NCFGroup\Common\Library\Idworker;


class DtBankBidEvent extends GlobalTransactionEvent {

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
     * 冻结类型 -- 01-预约投资
     */
    private $freezeType;

    /**
     * 预约冻结总金额
     */
    private $freezeSumAmount;

    /**
     * 预约投资冻结使用账户金额
     */
    private $freezeAccountAmount;

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
     * @param $freezeSumAmount 预约冻结总金额，单位（分）
     * @param $bonusInfo
     */
    public function __construct($orderId,$dealId,$userId,$freezeSumAmount,$bonusInfo) {
        $bonusAmount = $bonusInfo['money'];
        $this->freezeSumAmount = $freezeSumAmount;
        $this->freezeAccountAmount = bcsub($freezeSumAmount,$bonusAmount,2); // 预约投资冻结使用账户金额，单位（分）
        $this->orderId = $orderId;
        $this->dealId = $dealId;
        $this->userId = $userId;
        $this->bonusInfo = $bonusInfo;
    }

    public function execute(){
        $dealService = new DtP2pDealBidService();
        return $dealService->dealBidRequest($this->orderId,$this->dealId,$this->userId,$this->freezeSumAmount,$this->freezeAccountAmount,$this->bonusInfo);
    }

    public function rollback() {
        $dealService = new DtP2pDealBidService();
        return $dealService->dealBidCancelRequest($this->orderId);
    }
}