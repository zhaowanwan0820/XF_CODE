<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 理财师用户买红包
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzhuyan
 */
class RequestBonusBuy extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 总金额
     *
     * @var float
     * @required
     */
    private $totalPrice;

    /**
     * 红包个数
     *
     * @var int
     * @required
     */
    private $count;

    /**
     * 是否随机
     *
     * @var boolean
     * @required
     */
    private $isRandom;

    /**
     * 领取模式(0:无限制，1:检查所属关系)
     *
     * @var int
     * @optional
     */
    private $receiveMode = 0;

    /**
     * 是否显示理财师姓名
     *
     * @var int
     * @optional
     */
    private $showLcs = 0;

    /**
     * 订单号
     *
     * @var string
     * @required
     */
    private $orderID;

    /**
     * 签名
     *
     * @var string
     * @required
     */
    private $sign;

    /**
     * 时间戳
     *
     * @var string
     * @required
     */
    private $timestamp;

    /**
     * 客户端ID
     *
     * @var string
     * @required
     */
    private $clientID;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestBonusBuy
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return float
     */
    public function getTotalPrice()
    {
        return $this->totalPrice;
    }

    /**
     * @param float $totalPrice
     * @return RequestBonusBuy
     */
    public function setTotalPrice($totalPrice)
    {
        \Assert\Assertion::float($totalPrice);

        $this->totalPrice = $totalPrice;

        return $this;
    }
    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $count
     * @return RequestBonusBuy
     */
    public function setCount($count)
    {
        \Assert\Assertion::integer($count);

        $this->count = $count;

        return $this;
    }
    /**
     * @return boolean
     */
    public function getIsRandom()
    {
        return $this->isRandom;
    }

    /**
     * @param boolean $isRandom
     * @return RequestBonusBuy
     */
    public function setIsRandom($isRandom)
    {
        \Assert\Assertion::boolean($isRandom);

        $this->isRandom = $isRandom;

        return $this;
    }
    /**
     * @return int
     */
    public function getReceiveMode()
    {
        return $this->receiveMode;
    }

    /**
     * @param int $receiveMode
     * @return RequestBonusBuy
     */
    public function setReceiveMode($receiveMode = 0)
    {
        $this->receiveMode = $receiveMode;

        return $this;
    }
    /**
     * @return int
     */
    public function getShowLcs()
    {
        return $this->showLcs;
    }

    /**
     * @param int $showLcs
     * @return RequestBonusBuy
     */
    public function setShowLcs($showLcs = 0)
    {
        $this->showLcs = $showLcs;

        return $this;
    }
    /**
     * @return string
     */
    public function getOrderID()
    {
        return $this->orderID;
    }

    /**
     * @param string $orderID
     * @return RequestBonusBuy
     */
    public function setOrderID($orderID)
    {
        \Assert\Assertion::string($orderID);

        $this->orderID = $orderID;

        return $this;
    }
    /**
     * @return string
     */
    public function getSign()
    {
        return $this->sign;
    }

    /**
     * @param string $sign
     * @return RequestBonusBuy
     */
    public function setSign($sign)
    {
        \Assert\Assertion::string($sign);

        $this->sign = $sign;

        return $this;
    }
    /**
     * @return string
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param string $timestamp
     * @return RequestBonusBuy
     */
    public function setTimestamp($timestamp)
    {
        \Assert\Assertion::string($timestamp);

        $this->timestamp = $timestamp;

        return $this;
    }
    /**
     * @return string
     */
    public function getClientID()
    {
        return $this->clientID;
    }

    /**
     * @param string $clientID
     * @return RequestBonusBuy
     */
    public function setClientID($clientID)
    {
        \Assert\Assertion::string($clientID);

        $this->clientID = $clientID;

        return $this;
    }

}