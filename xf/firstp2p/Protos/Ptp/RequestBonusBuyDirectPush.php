<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 理财师用户买单个红包
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzhuyan
 */
class RequestBonusBuyDirectPush extends AbstractRequestBase
{
    /**
     * 订单ID
     *
     * @var string
     * @required
     */
    private $orderID;

    /**
     * 用户
     *
     * @var array
     * @required
     */
    private $uids;

    /**
     * 过期天数
     *
     * @var int
     * @required
     */
    private $expireDay;

    /**
     * 发送人uid
     *
     * @var int
     * @required
     */
    private $senderID;

    /**
     * 红包金额
     *
     * @var float
     * @required
     */
    private $money;

    /**
     * 红包类型
     *
     * @var int
     * @required
     */
    private $type;

    /**
     * @return string
     */
    public function getOrderID()
    {
        return $this->orderID;
    }

    /**
     * @param string $orderID
     * @return RequestBonusBuyDirectPush
     */
    public function setOrderID($orderID)
    {
        \Assert\Assertion::string($orderID);

        $this->orderID = $orderID;

        return $this;
    }
    /**
     * @return array
     */
    public function getUids()
    {
        return $this->uids;
    }

    /**
     * @param array $uids
     * @return RequestBonusBuyDirectPush
     */
    public function setUids(array $uids)
    {
        $this->uids = $uids;

        return $this;
    }
    /**
     * @return int
     */
    public function getExpireDay()
    {
        return $this->expireDay;
    }

    /**
     * @param int $expireDay
     * @return RequestBonusBuyDirectPush
     */
    public function setExpireDay($expireDay)
    {
        \Assert\Assertion::integer($expireDay);

        $this->expireDay = $expireDay;

        return $this;
    }
    /**
     * @return int
     */
    public function getSenderID()
    {
        return $this->senderID;
    }

    /**
     * @param int $senderID
     * @return RequestBonusBuyDirectPush
     */
    public function setSenderID($senderID)
    {
        \Assert\Assertion::integer($senderID);

        $this->senderID = $senderID;

        return $this;
    }
    /**
     * @return float
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * @param float $money
     * @return RequestBonusBuyDirectPush
     */
    public function setMoney($money)
    {
        \Assert\Assertion::float($money);

        $this->money = $money;

        return $this;
    }
    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RequestBonusBuyDirectPush
     */
    public function setType($type)
    {
        \Assert\Assertion::integer($type);

        $this->type = $type;

        return $this;
    }

}