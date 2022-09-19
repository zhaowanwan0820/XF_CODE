<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 资金操作
 *
 * 由代码生成器生成, 不可人为修改
 * @author luzhengshuai@ucfgroup.com
 */
class RequestChangeMoney extends ProtoBufferBase
{
    /**
     * 订单号
     *
     * @var int
     * @required
     */
    private $bizOrderId;

    /**
     * 业务类型
     *
     * @var int
     * @required
     */
    private $bizType;

    /**
     * 子业务类型
     *
     * @var int
     * @required
     */
    private $bizSubtype;

    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 操作金额
     *
     * @var int
     * @required
     */
    private $amount;

    /**
     * 资金记录类型信息, 如充值，担保费
     *
     * @var string
     * @required
     */
    private $logInfo;

    /**
     * 资金记录详情信息, 如会员xxx，提现xxx元
     *
     * @var string
     * @required
     */
    private $logNote;

    /**
     * 资金操作类型
     *
     * @var int
     * @required
     */
    private $moneyType;

    /**
     * 资金操作类型
     *
     * @var boolean
     * @required
     */
    private $async;

    /**
     * 资金记录交易类型
     *
     * @var int
     * @optional
     */
    private $changeMoneyDealType = 0;

    /**
     * @return int
     */
    public function getBizOrderId()
    {
        return $this->bizOrderId;
    }

    /**
     * @param int $bizOrderId
     * @return RequestChangeMoney
     */
    public function setBizOrderId($bizOrderId)
    {
        \Assert\Assertion::integer($bizOrderId);

        $this->bizOrderId = $bizOrderId;

        return $this;
    }
    /**
     * @return int
     */
    public function getBizType()
    {
        return $this->bizType;
    }

    /**
     * @param int $bizType
     * @return RequestChangeMoney
     */
    public function setBizType($bizType)
    {
        \Assert\Assertion::integer($bizType);

        $this->bizType = $bizType;

        return $this;
    }
    /**
     * @return int
     */
    public function getBizSubtype()
    {
        return $this->bizSubtype;
    }

    /**
     * @param int $bizSubtype
     * @return RequestChangeMoney
     */
    public function setBizSubtype($bizSubtype)
    {
        \Assert\Assertion::integer($bizSubtype);

        $this->bizSubtype = $bizSubtype;

        return $this;
    }
    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestChangeMoney
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     * @return RequestChangeMoney
     */
    public function setAmount($amount)
    {
        \Assert\Assertion::integer($amount);

        $this->amount = $amount;

        return $this;
    }
    /**
     * @return string
     */
    public function getLogInfo()
    {
        return $this->logInfo;
    }

    /**
     * @param string $logInfo
     * @return RequestChangeMoney
     */
    public function setLogInfo($logInfo)
    {
        \Assert\Assertion::string($logInfo);

        $this->logInfo = $logInfo;

        return $this;
    }
    /**
     * @return string
     */
    public function getLogNote()
    {
        return $this->logNote;
    }

    /**
     * @param string $logNote
     * @return RequestChangeMoney
     */
    public function setLogNote($logNote)
    {
        \Assert\Assertion::string($logNote);

        $this->logNote = $logNote;

        return $this;
    }
    /**
     * @return int
     */
    public function getMoneyType()
    {
        return $this->moneyType;
    }

    /**
     * @param int $moneyType
     * @return RequestChangeMoney
     */
    public function setMoneyType($moneyType)
    {
        \Assert\Assertion::integer($moneyType);

        $this->moneyType = $moneyType;

        return $this;
    }
    /**
     * @return boolean
     */
    public function getAsync()
    {
        return $this->async;
    }

    /**
     * @param boolean $async
     * @return RequestChangeMoney
     */
    public function setAsync($async)
    {
        \Assert\Assertion::boolean($async);

        $this->async = $async;

        return $this;
    }
    /**
     * @return int
     */
    public function getChangeMoneyDealType()
    {
        return $this->changeMoneyDealType;
    }

    /**
     * @param int $changeMoneyDealType
     * @return RequestChangeMoney
     */
    public function setChangeMoneyDealType($changeMoneyDealType = 0)
    {
        $this->changeMoneyDealType = $changeMoneyDealType;

        return $this;
    }

}