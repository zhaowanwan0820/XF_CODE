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
class ProtoCfpDeal extends ProtoBufferBase
{
    /**
     * 标ID
     *
     * @var string
     * @required
     */
    private $id;

    /**
     * 标名字
     *
     * @var string
     * @required
     */
    private $name;

    /**
     * 标签名字
     *
     * @var string
     * @required
     */
    private $tagName;

    /**
     * 期限
     *
     * @var string
     * @required
     */
    private $timeLimit;

    /**
     * 标总额
     *
     * @var string
     * @required
     */
    private $total;

    /**
     * 最小起投金额
     *
     * @var string
     * @required
     */
    private $minLoan;

    /**
     * 还款方式
     *
     * @var string
     * @required
     */
    private $repayment;

    /**
     * 收益率
     *
     * @var string
     * @required
     */
    private $rate;

    /**
     * 剩余可投金额
     *
     * @var string
     * @required
     */
    private $canLoan;

    /**
     * 项目总金额
     *
     * @var string
     * @required
     */
    private $projectAmount;

    /**
     * 项目未上线金额
     *
     * @var string
     * @required
     */
    private $projectLoan;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return ProtoCfpDeal
     */
    public function setId($id)
    {
        \Assert\Assertion::string($id);

        $this->id = $id;

        return $this;
    }
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ProtoCfpDeal
     */
    public function setName($name)
    {
        \Assert\Assertion::string($name);

        $this->name = $name;

        return $this;
    }
    /**
     * @return string
     */
    public function getTagName()
    {
        return $this->tagName;
    }

    /**
     * @param string $tagName
     * @return ProtoCfpDeal
     */
    public function setTagName($tagName)
    {
        \Assert\Assertion::string($tagName);

        $this->tagName = $tagName;

        return $this;
    }
    /**
     * @return string
     */
    public function getTimeLimit()
    {
        return $this->timeLimit;
    }

    /**
     * @param string $timeLimit
     * @return ProtoCfpDeal
     */
    public function setTimeLimit($timeLimit)
    {
        \Assert\Assertion::string($timeLimit);

        $this->timeLimit = $timeLimit;

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
     * @return ProtoCfpDeal
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
    public function getMinLoan()
    {
        return $this->minLoan;
    }

    /**
     * @param string $minLoan
     * @return ProtoCfpDeal
     */
    public function setMinLoan($minLoan)
    {
        \Assert\Assertion::string($minLoan);

        $this->minLoan = $minLoan;

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
     * @return ProtoCfpDeal
     */
    public function setRepayment($repayment)
    {
        \Assert\Assertion::string($repayment);

        $this->repayment = $repayment;

        return $this;
    }
    /**
     * @return string
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @param string $rate
     * @return ProtoCfpDeal
     */
    public function setRate($rate)
    {
        \Assert\Assertion::string($rate);

        $this->rate = $rate;

        return $this;
    }
    /**
     * @return string
     */
    public function getCanLoan()
    {
        return $this->canLoan;
    }

    /**
     * @param string $canLoan
     * @return ProtoCfpDeal
     */
    public function setCanLoan($canLoan)
    {
        \Assert\Assertion::string($canLoan);

        $this->canLoan = $canLoan;

        return $this;
    }
    /**
     * @return string
     */
    public function getProjectAmount()
    {
        return $this->projectAmount;
    }

    /**
     * @param string $projectAmount
     * @return ProtoCfpDeal
     */
    public function setProjectAmount($projectAmount)
    {
        \Assert\Assertion::string($projectAmount);

        $this->projectAmount = $projectAmount;

        return $this;
    }
    /**
     * @return string
     */
    public function getProjectLoan()
    {
        return $this->projectLoan;
    }

    /**
     * @param string $projectLoan
     * @return ProtoCfpDeal
     */
    public function setProjectLoan($projectLoan)
    {
        \Assert\Assertion::string($projectLoan);

        $this->projectLoan = $projectLoan;

        return $this;
    }

}