<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:合作方查询订单信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class RequestQueryOrder extends ProtoBufferBase
{
    /**
     * 网信给合作方分配的唯一key
     *
     * @var string
     * @required
     */
    private $couponProvider;

    /**
     * 订单编号
     *
     * @var string
     * @required
     */
    private $orderId;

    /**
     * @return string
     */
    public function getCouponProvider()
    {
        return $this->couponProvider;
    }

    /**
     * @param string $couponProvider
     * @return RequestQueryOrder
     */
    public function setCouponProvider($couponProvider)
    {
        \Assert\Assertion::string($couponProvider);

        $this->couponProvider = $couponProvider;

        return $this;
    }
    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     * @return RequestQueryOrder
     */
    public function setOrderId($orderId)
    {
        \Assert\Assertion::string($orderId);

        $this->orderId = $orderId;

        return $this;
    }

}