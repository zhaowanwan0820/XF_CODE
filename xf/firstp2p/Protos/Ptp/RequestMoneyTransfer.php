<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 转账
 *
 * 由代码生成器生成, 不可人为修改
 * @author luzhengshuai@ucfgroup.com
 */
class RequestMoneyTransfer extends ProtoBufferBase
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
     * 付款方ID
     *
     * @var int
     * @required
     */
    private $payerId;

    /**
     * 收款方ID
     *
     * @var int
     * @required
     */
    private $receiverId;

    /**
     * 转账金额
     *
     * @var int
     * @required
     */
    private $amount;

    /**
     * 转账业务类型
     *
     * @var int
     * @required
     */
    private $transferBizType;

    /**
     * 付款方资金记录类型
     *
     * @var string
     * @required
     */
    private $payerMessage;

    /**
     * 付款方资金记录信息
     *
     * @var string
     * @required
     */
    private $payerNote;

    /**
     * 收款方资金记录类型
     *
     * @var string
     * @required
     */
    private $receiverMessage;

    /**
     * 收款方资金记录信息
     *
     * @var string
     * @required
     */
    private $receiverNote;

    /**
     * 资金记录交易类型
     *
     * @var int
     * @optional
     */
    private $changeMoneyDealType = 0;

    /**
     * 出资方资金扣减类型. 默认为余额，冻结传2
     *
     * @var int
     * @optional
     */
    private $payerMoneyType = 0;

    /**
     * 收款方资金操作是否异步
     *
     * @var int
     * @optional
     */
    private $receiverChangeMoneyAsync = false;

    /**
     * @return int
     */
    public function getBizOrderId()
    {
        return $this->bizOrderId;
    }

    /**
     * @param int $bizOrderId
     * @return RequestMoneyTransfer
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
     * @return RequestMoneyTransfer
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
     * @return RequestMoneyTransfer
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
    public function getPayerId()
    {
        return $this->payerId;
    }

    /**
     * @param int $payerId
     * @return RequestMoneyTransfer
     */
    public function setPayerId($payerId)
    {
        \Assert\Assertion::integer($payerId);

        $this->payerId = $payerId;

        return $this;
    }
    /**
     * @return int
     */
    public function getReceiverId()
    {
        return $this->receiverId;
    }

    /**
     * @param int $receiverId
     * @return RequestMoneyTransfer
     */
    public function setReceiverId($receiverId)
    {
        \Assert\Assertion::integer($receiverId);

        $this->receiverId = $receiverId;

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
     * @return RequestMoneyTransfer
     */
    public function setAmount($amount)
    {
        \Assert\Assertion::integer($amount);

        $this->amount = $amount;

        return $this;
    }
    /**
     * @return int
     */
    public function getTransferBizType()
    {
        return $this->transferBizType;
    }

    /**
     * @param int $transferBizType
     * @return RequestMoneyTransfer
     */
    public function setTransferBizType($transferBizType)
    {
        \Assert\Assertion::integer($transferBizType);

        $this->transferBizType = $transferBizType;

        return $this;
    }
    /**
     * @return string
     */
    public function getPayerMessage()
    {
        return $this->payerMessage;
    }

    /**
     * @param string $payerMessage
     * @return RequestMoneyTransfer
     */
    public function setPayerMessage($payerMessage)
    {
        \Assert\Assertion::string($payerMessage);

        $this->payerMessage = $payerMessage;

        return $this;
    }
    /**
     * @return string
     */
    public function getPayerNote()
    {
        return $this->payerNote;
    }

    /**
     * @param string $payerNote
     * @return RequestMoneyTransfer
     */
    public function setPayerNote($payerNote)
    {
        \Assert\Assertion::string($payerNote);

        $this->payerNote = $payerNote;

        return $this;
    }
    /**
     * @return string
     */
    public function getReceiverMessage()
    {
        return $this->receiverMessage;
    }

    /**
     * @param string $receiverMessage
     * @return RequestMoneyTransfer
     */
    public function setReceiverMessage($receiverMessage)
    {
        \Assert\Assertion::string($receiverMessage);

        $this->receiverMessage = $receiverMessage;

        return $this;
    }
    /**
     * @return string
     */
    public function getReceiverNote()
    {
        return $this->receiverNote;
    }

    /**
     * @param string $receiverNote
     * @return RequestMoneyTransfer
     */
    public function setReceiverNote($receiverNote)
    {
        \Assert\Assertion::string($receiverNote);

        $this->receiverNote = $receiverNote;

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
     * @return RequestMoneyTransfer
     */
    public function setChangeMoneyDealType($changeMoneyDealType = 0)
    {
        $this->changeMoneyDealType = $changeMoneyDealType;

        return $this;
    }
    /**
     * @return int
     */
    public function getPayerMoneyType()
    {
        return $this->payerMoneyType;
    }

    /**
     * @param int $payerMoneyType
     * @return RequestMoneyTransfer
     */
    public function setPayerMoneyType($payerMoneyType = 0)
    {
        $this->payerMoneyType = $payerMoneyType;

        return $this;
    }
    /**
     * @return int
     */
    public function getReceiverChangeMoneyAsync()
    {
        return $this->receiverChangeMoneyAsync;
    }

    /**
     * @param int $receiverChangeMoneyAsync
     * @return RequestMoneyTransfer
     */
    public function setReceiverChangeMoneyAsync($receiverChangeMoneyAsync = false)
    {
        $this->receiverChangeMoneyAsync = $receiverChangeMoneyAsync;

        return $this;
    }

}