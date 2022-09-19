<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 第三方更新券为已使用请求
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class RequestSetCouponUsedByApi extends AbstractRequestBase
{
    /**
     * 优惠券券码
     *
     * @var string
     * @required
     */
    private $couponNumber;

    /**
     * 第三方券码来源
     *
     * @var string
     * @required
     */
    private $couponSrc;

    /**
     * @return string
     */
    public function getCouponNumber()
    {
        return $this->couponNumber;
    }

    /**
     * @param string $couponNumber
     * @return RequestSetCouponUsedByApi
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
    public function getCouponSrc()
    {
        return $this->couponSrc;
    }

    /**
     * @param string $couponSrc
     * @return RequestSetCouponUsedByApi
     */
    public function setCouponSrc($couponSrc)
    {
        \Assert\Assertion::string($couponSrc);

        $this->couponSrc = $couponSrc;

        return $this;
    }

}