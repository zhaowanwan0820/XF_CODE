<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 理财师用户买红包
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzhuyan
 */
class RequestBonusRefund extends AbstractRequestBase
{
    /**
     * 订单ID
     *
     * @var string
     * @required
     */
    private $orderID;

    /**
     * @return string
     */
    public function getOrderID()
    {
        return $this->orderID;
    }

    /**
     * @param string $orderID
     * @return RequestBonusRefund
     */
    public function setOrderID($orderID)
    {
        \Assert\Assertion::string($orderID);

        $this->orderID = $orderID;

        return $this;
    }

}