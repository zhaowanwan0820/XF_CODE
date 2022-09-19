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
class ProtoCfpDealDetail extends ProtoBufferBase
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
     * 标的类型
     *
     * @var string
     * @required
     */
    private $dealTypeText;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return ProtoCfpDealDetail
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
     * @return ProtoCfpDealDetail
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
     * @return ProtoCfpDealDetail
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
     * @return ProtoCfpDealDetail
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
     * @return ProtoCfpDealDetail
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
     * @return ProtoCfpDealDetail
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
     * @return ProtoCfpDealDetail
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
     * @return ProtoCfpDealDetail
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
     * @return ProtoCfpDealDetail
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
    public function getDealTypeText()
    {
        return $this->dealTypeText;
    }

    /**
     * @param string $dealTypeText
     * @return ProtoCfpDealDetail
     */
    public function setDealTypeText($dealTypeText)
    {
        \Assert\Assertion::string($dealTypeText);

        $this->dealTypeText = $dealTypeText;

        return $this;
    }

}