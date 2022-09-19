<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 提现接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author xiaoan
 */
class RequestPaymentCashOutV2 extends AbstractRequestBase
{
    /**
     *  用户id
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     *  提现金额
     *
     * @var string
     * @required
     */
    private $money;

    /**
     * 客户端2android,3ios
     *
     * @var int
     * @required
     */
    private $os;

    /**
     * 银行卡号
     *
     * @var string
     * @required
     */
    private $bankCardId;

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
     * @return RequestPaymentCashOutV2
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * @param string $money
     * @return RequestPaymentCashOutV2
     */
    public function setMoney($money)
    {
        \Assert\Assertion::string($money);

        $this->money = $money;

        return $this;
    }
    /**
     * @return int
     */
    public function getOs()
    {
        return $this->os;
    }

    /**
     * @param int $os
     * @return RequestPaymentCashOutV2
     */
    public function setOs($os)
    {
        \Assert\Assertion::integer($os);

        $this->os = $os;

        return $this;
    }
    /**
     * @return string
     */
    public function getBankCardId()
    {
        return $this->bankCardId;
    }

    /**
     * @param string $bankCardId
     * @return RequestPaymentCashOutV2
     */
    public function setBankCardId($bankCardId)
    {
        \Assert\Assertion::string($bankCardId);

        $this->bankCardId = $bankCardId;

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
     * @return RequestPaymentCashOutV2
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
     * @return RequestPaymentCashOutV2
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
     * @return RequestPaymentCashOutV2
     */
    public function setClientID($clientID)
    {
        \Assert\Assertion::string($clientID);

        $this->clientID = $clientID;

        return $this;
    }

}