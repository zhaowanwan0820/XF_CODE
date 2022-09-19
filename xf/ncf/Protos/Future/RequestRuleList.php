<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Protos\Future\Enum\OrderType;

/**
 * 获取配资方案列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author guweigang
 */
class RequestRuleList extends AbstractRequestBase
{
    /**
     * 配资类型: 天/月
     *
     * @var OrderType
     * @required
     */
    private $orderType;

    /**
     * 保证金额（分）
     *
     * @var int
     * @optional
     */
    private $amount = 0;

    /**
     * 配资周期
     *
     * @var int
     * @optional
     */
    private $month = 1;

    /**
     * @return OrderType
     */
    public function getOrderType()
    {
        return $this->orderType;
    }

    /**
     * @param OrderType $orderType
     * @return RequestRuleList
     */
    public function setOrderType(OrderType $orderType)
    {
        $this->orderType = $orderType;

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
     * @return RequestRuleList
     */
    public function setAmount($amount = 0)
    {
        $this->amount = $amount;

        return $this;
    }
    /**
     * @return int
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * @param int $month
     * @return RequestRuleList
     */
    public function setMonth($month = 1)
    {
        $this->month = $month;

        return $this;
    }

}