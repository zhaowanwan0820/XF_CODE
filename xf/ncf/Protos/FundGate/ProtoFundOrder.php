<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;

/**
 * 基金购买列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author Gu Weigang <guweigang@ucfgroup.com>
 */
class ProtoFundOrder extends ProtoBufferBase
{
    /**
     * 客户名称
     *
     * @var string
     * @required
     */
    private $custName;

    /**
     * 投资金额
     *
     * @var double
     * @required
     */
    private $amount;

    /**
     * 投资时间
     *
     * @var string
     * @required
     */
    private $time;

    /**
     * @return string
     */
    public function getCustName()
    {
        return $this->custName;
    }

    /**
     * @param string $custName
     * @return ProtoFundOrder
     */
    public function setCustName($custName)
    {
        \Assert\Assertion::string($custName);

        $this->custName = $custName;

        return $this;
    }
    /**
     * @return double
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param double $amount
     * @return ProtoFundOrder
     */
    public function setAmount($amount)
    {
        \Assert\Assertion::float($amount);

        $this->amount = $amount;

        return $this;
    }
    /**
     * @return string
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param string $time
     * @return ProtoFundOrder
     */
    public function setTime($time)
    {
        \Assert\Assertion::string($time);

        $this->time = $time;

        return $this;
    }

}