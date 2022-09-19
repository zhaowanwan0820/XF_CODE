<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 更新红包组过期时间
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzhuyan
 */
class RequestBonusUpdateExpire extends AbstractRequestBase
{
    /**
     * 订单ID
     *
     * @var string
     * @required
     */
    private $orderID;

    /**
     * 过期时间
     *
     * @var int
     * @optional
     */
    private $expireTime = 0;

    /**
     * @return string
     */
    public function getOrderID()
    {
        return $this->orderID;
    }

    /**
     * @param string $orderID
     * @return RequestBonusUpdateExpire
     */
    public function setOrderID($orderID)
    {
        \Assert\Assertion::string($orderID);

        $this->orderID = $orderID;

        return $this;
    }
    /**
     * @return int
     */
    public function getExpireTime()
    {
        return $this->expireTime;
    }

    /**
     * @param int $expireTime
     * @return RequestBonusUpdateExpire
     */
    public function setExpireTime($expireTime = 0)
    {
        $this->expireTime = $expireTime;

        return $this;
    }

}