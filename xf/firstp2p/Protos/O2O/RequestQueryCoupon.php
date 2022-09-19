<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:合作方查询券信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class RequestQueryCoupon extends ProtoBufferBase
{
    /**
     * 网信给合作方分配的唯一key
     *
     * @var string
     * @required
     */
    private $couponProvider;

    /**
     * 券码
     *
     * @var string
     * @required
     */
    private $couponNumber;

    /**
     * @return string
     */
    public function getCouponProvider()
    {
        return $this->couponProvider;
    }

    /**
     * @param string $couponProvider
     * @return RequestQueryCoupon
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
    public function getCouponNumber()
    {
        return $this->couponNumber;
    }

    /**
     * @param string $couponNumber
     * @return RequestQueryCoupon
     */
    public function setCouponNumber($couponNumber)
    {
        \Assert\Assertion::string($couponNumber);

        $this->couponNumber = $couponNumber;

        return $this;
    }

}