<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 根据订单号获取红包组使用情况
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzhuyan
 */
class RequestBonusGetByOrder extends ProtoBufferBase
{
    /**
     * orders
     *
     * @var array
     * @required
     */
    private $orders;

    /**
     * @return array
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * @param array $orders
     * @return RequestBonusGetByOrder
     */
    public function setOrders(array $orders)
    {
        $this->orders = $orders;

        return $this;
    }

}