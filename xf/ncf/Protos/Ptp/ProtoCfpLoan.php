<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 热标与待上线标信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong
 */
class ProtoCfpLoan extends ProtoBufferBase
{
    /**
     * 标ID
     *
     * @var string
     * @required
     */
    private $dealId;

    /**
     * 标名字
     *
     * @var string
     * @required
     */
    private $dealName;

    /**
     * 到期日期
     *
     * @var string
     * @required
     */
    private $dueDay;

    /**
     * 标总额
     *
     * @var string
     * @required
     */
    private $total;

    /**
     * 投资标利率
     *
     * @var string
     * @required
     */
    private $dealRate;

    /**
     * 投标金额
     *
     * @var string
     * @required
     */
    private $loanAmount;

    /**
     * 还款方式
     *
     * @var string
     * @required
     */
    private $repayment;

    /**
     * @return string
     */
    public function getDealId()
    {
        return $this->dealId;
    }

    /**
     * @param string $dealId
     * @return ProtoCfpLoan
     */
    public function setDealId($dealId)
    {
        \Assert\Assertion::string($dealId);

        $this->dealId = $dealId;

        return $this;
    }
    /**
     * @return string
     */
    public function getDealName()
    {
        return $this->dealName;
    }

    /**
     * @param string $dealName
     * @return ProtoCfpLoan
     */
    public function setDealName($dealName)
    {
        \Assert\Assertion::string($dealName);

        $this->dealName = $dealName;

        return $this;
    }
    /**
     * @return string
     */
    public function getDueDay()
    {
        return $this->dueDay;
    }

    /**
     * @param string $dueDay
     * @return ProtoCfpLoan
     */
    public function setDueDay($dueDay)
    {
        \Assert\Assertion::string($dueDay);

        $this->dueDay = $dueDay;

        return $this;
    }
    /**
     * @return string
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param string $total
     * @return ProtoCfpLoan
     */
    public function setTotal($total)
    {
        \Assert\Assertion::string($total);

        $this->total = $total;

        return $this;
    }
    /**
     * @return string
     */
    public function getDealRate()
    {
        return $this->dealRate;
    }

    /**
     * @param string $dealRate
     * @return ProtoCfpLoan
     */
    public function setDealRate($dealRate)
    {
        \Assert\Assertion::string($dealRate);

        $this->dealRate = $dealRate;

        return $this;
    }
    /**
     * @return string
     */
    public function getLoanAmount()
    {
        return $this->loanAmount;
    }

    /**
     * @param string $loanAmount
     * @return ProtoCfpLoan
     */
    public function setLoanAmount($loanAmount)
    {
        \Assert\Assertion::string($loanAmount);

        $this->loanAmount = $loanAmount;

        return $this;
    }
    /**
     * @return string
     */
    public function getRepayment()
    {
        return $this->repayment;
    }

    /**
     * @param string $repayment
     * @return ProtoCfpLoan
     */
    public function setRepayment($repayment)
    {
        \Assert\Assertion::string($repayment);

        $this->repayment = $repayment;

        return $this;
    }

}