<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 提取利润
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class RequestExtractProfit extends AbstractRequestBase
{
    /**
     * 订单
     *
     * @var string
     * @required
     */
    private $orderNo;

    /**
     * 提取金额单位分
     *
     * @var int
     * @required
     */
    private $amount;

    /**
     * @return string
     */
    public function getOrderNo()
    {
        return $this->orderNo;
    }

    /**
     * @param string $orderNo
     * @return RequestExtractProfit
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
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     * @return RequestExtractProfit
     */
    public function setAmount($amount)
    {
        \Assert\Assertion::integer($amount);

        $this->amount = $amount;

        return $this;
    }

}