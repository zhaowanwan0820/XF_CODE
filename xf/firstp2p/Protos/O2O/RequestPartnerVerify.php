<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:合作方核销
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class RequestPartnerVerify extends ProtoBufferBase
{
    /**
     * 券码
     *
     * @var string
     * @required
     */
    private $couponNumber;

    /**
     * 券码源商标识
     *
     * @var string
     * @required
     */
    private $couponProvider;

    /**
     * @return string
     */
    public function getCouponNumber()
    {
        return $this->couponNumber;
    }

    /**
     * @param string $couponNumber
     * @return RequestPartnerVerify
     */
    public function setCouponNumber($couponNumber)
    {
        \Assert\Assertion::string($couponNumber);

        $this->couponNumber = $couponNumber;

        return $this;
    }
    /**
     * @return string
     */
    public function getCouponProvider()
    {
        return $this->couponProvider;
    }

    /**
     * @param string $couponProvider
     * @return RequestPartnerVerify
     */
    public function setCouponProvider($couponProvider)
    {
        \Assert\Assertion::string($couponProvider);

        $this->couponProvider = $couponProvider;

        return $this;
    }

}