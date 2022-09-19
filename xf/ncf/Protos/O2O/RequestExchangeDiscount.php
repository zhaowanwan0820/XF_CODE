<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 兑换优惠券
 *
 * 由代码生成器生成, 不可人为修改
 * @author yanbingrong
 */
class RequestExchangeDiscount extends AbstractRequestBase
{
    /**
     * 优惠券id
     *
     * @var int
     * @required
     */
    private $discountId;

    /**
     * 用户id
     *
     * @var int
     * @required
     */
    private $ownerUserId;

    /**
     * 交易id
     *
     * @var int
     * @optional
     */
    private $dealLoadId = 0;

    /**
     * 触发时间
     *
     * @var int
     * @optional
     */
    private $triggerTime = 0;

    /**
     * @return int
     */
    public function getDiscountId()
    {
        return $this->discountId;
    }

    /**
     * @param int $discountId
     * @return RequestExchangeDiscount
     */
    public function setDiscountId($discountId)
    {
        \Assert\Assertion::integer($discountId);

        $this->discountId = $discountId;

        return $this;
    }
    /**
     * @return int
     */
    public function getOwnerUserId()
    {
        return $this->ownerUserId;
    }

    /**
     * @param int $ownerUserId
     * @return RequestExchangeDiscount
     */
    public function setOwnerUserId($ownerUserId)
    {
        \Assert\Assertion::integer($ownerUserId);

        $this->ownerUserId = $ownerUserId;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealLoadId()
    {
        return $this->dealLoadId;
    }

    /**
     * @param int $dealLoadId
     * @return RequestExchangeDiscount
     */
    public function setDealLoadId($dealLoadId = 0)
    {
        $this->dealLoadId = $dealLoadId;

        return $this;
    }
    /**
     * @return int
     */
    public function getTriggerTime()
    {
        return $this->triggerTime;
    }

    /**
     * @param int $triggerTime
     * @return RequestExchangeDiscount
     */
    public function setTriggerTime($triggerTime = 0)
    {
        $this->triggerTime = $triggerTime;

        return $this;
    }

}