<?php

namespace core\tmevent\discount;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use libs\utils\Logger;
use core\service\o2o\DiscountService;

/**
 * 投资券兑换逻辑
 */
class DiscountConsumeEvent extends GlobalTransactionEvent {
    // 投资券所属用户ID
    protected $ownerUserId;

    // 投资券ID
    protected $discountId;

    // 交易ID
    protected $dealLoadId;

    // 投资券类型
    protected $discountType;

    // 用券时间
    protected $triggerTime;

    // 交易类型:p2p交易
    protected $consumeType = 1;

    // 投资券投资备注信息
    protected $extraInfo;

    /**
     * 构造函数
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-06-06
     * @param mixed $userId
     * @param mixed $discountId
     * @param mixed $dealLoadId
     * @param mixed $triggerTime
     */
    public function __construct($ownerUserId, $discountId, $dealLoadId, $discountType, $triggerTime = false,
                                $consumeType ='', $extraInfo = array()) {
        // 接收参数
        $this->ownerUserId = $ownerUserId;
        $this->discountId = $discountId;
        $this->dealLoadId = $dealLoadId;
        $this->discountType = $discountType;
        $this->triggerTime = empty($triggerTime) ? time() : $triggerTime;
        $this->consumeType = $consumeType;
        $this->extraInfo = $extraInfo;

        $extraInfo = isset($extraInfo) ? json_encode($extraInfo) : '';

        Logger::info(implode('|', [__METHOD__, $ownerUserId, $discountId, $dealLoadId, $triggerTime, $this->consumeType, $extraInfo]));
    }

    public function execute()
    {
        //处理投资逻辑，成功返回true，失败返回false，其他结果一律会重试
        //没有使用投资券直接返回
        if (empty($this->discountId)) return true;

        return DiscountService::consumeDiscount($this->ownerUserId, $this->discountId,
            $this->dealLoadId, $this->discountType, $this->triggerTime, $this->consumeType, $this->extraInfo);
    }

    public function rollback()
    {
        //处理逻辑，成功返回true，失败返回false，其他结果一律会重试
        return DiscountService::cancelConsumeDiscount($this->ownerUserId, $this->discountId);
    }
}
