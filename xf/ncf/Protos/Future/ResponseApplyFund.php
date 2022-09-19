<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 申请返回结果
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class ResponseApplyFund extends ResponseBase
{
    /**
     * order id
     *
     * @var string
     * @required
     */
    private $orderNo;

    /**
     * 总资金
     *
     * @var int
     * @required
     */
    private $totalAmount;

    /**
     * 警戒线资金
     *
     * @var int
     * @required
     */
    private $warningAmount;

    /**
     * 平仓线资金
     *
     * @var int
     * @required
     */
    private $closeAmount;

    /**
     * 保证金
     *
     * @var int
     * @required
     */
    private $depositAmount;

    /**
     * 开始类型
     *
     * @var int
     * @required
     */
    private $startType;

    /**
     * 利息
     *
     * @var int
     * @required
     */
    private $interestAmount;

    /**
     * @return string
     */
    public function getOrderNo()
    {
        return $this->orderNo;
    }

    /**
     * @param string $orderNo
     * @return ResponseApplyFund
     */
    public function setOrderNo($orderNo)
    {
        \Assert\Assertion::string($orderNo);

        $this->orderNo = $orderNo;

        return $this;
    }
    /**
     * @return int
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * @param int $totalAmount
     * @return ResponseApplyFund
     */
    public function setTotalAmount($totalAmount)
    {
        \Assert\Assertion::integer($totalAmount);

        $this->totalAmount = $totalAmount;

        return $this;
    }
    /**
     * @return int
     */
    public function getWarningAmount()
    {
        return $this->warningAmount;
    }

    /**
     * @param int $warningAmount
     * @return ResponseApplyFund
     */
    public function setWarningAmount($warningAmount)
    {
        \Assert\Assertion::integer($warningAmount);

        $this->warningAmount = $warningAmount;

        return $this;
    }
    /**
     * @return int
     */
    public function getCloseAmount()
    {
        return $this->closeAmount;
    }

    /**
     * @param int $closeAmount
     * @return ResponseApplyFund
     */
    public function setCloseAmount($closeAmount)
    {
        \Assert\Assertion::integer($closeAmount);

        $this->closeAmount = $closeAmount;

        return $this;
    }
    /**
     * @return int
     */
    public function getDepositAmount()
    {
        return $this->depositAmount;
    }

    /**
     * @param int $depositAmount
     * @return ResponseApplyFund
     */
    public function setDepositAmount($depositAmount)
    {
        \Assert\Assertion::integer($depositAmount);

        $this->depositAmount = $depositAmount;

        return $this;
    }
    /**
     * @return int
     */
    public function getStartType()
    {
        return $this->startType;
    }

    /**
     * @param int $startType
     * @return ResponseApplyFund
     */
    public function setStartType($startType)
    {
        \Assert\Assertion::integer($startType);

        $this->startType = $startType;

        return $this;
    }
    /**
     * @return int
     */
    public function getInterestAmount()
    {
        return $this->interestAmount;
    }

    /**
     * @param int $interestAmount
     * @return ResponseApplyFund
     */
    public function setInterestAmount($interestAmount)
    {
        \Assert\Assertion::integer($interestAmount);

        $this->interestAmount = $interestAmount;

        return $this;
    }

}