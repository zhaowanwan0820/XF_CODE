<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:导入券码信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author yanbingrong
 */
class RequestAddCouponNumber extends ProtoBufferBase
{
    /**
     * 券组ID
     *
     * @var int
     * @required
     */
    private $couponGroupId;

    /**
     * 券码
     *
     * @var array
     * @required
     */
    private $couponNumbers;

    /**
     * 券码源商标识
     *
     * @var string
     * @required
     */
    private $couponProvider;

    /**
     * @return int
     */
    public function getCouponGroupId()
    {
        return $this->couponGroupId;
    }

    /**
     * @param int $couponGroupId
     * @return RequestAddCouponNumber
     */
    public function setCouponGroupId($couponGroupId)
    {
        \Assert\Assertion::integer($couponGroupId);

        $this->couponGroupId = $couponGroupId;

        return $this;
    }
    /**
     * @return array
     */
    public function getCouponNumbers()
    {
        return $this->couponNumbers;
    }

    /**
     * @param array $couponNumbers
     * @return RequestAddCouponNumber
     */
    public function setCouponNumbers(array $couponNumbers)
    {
        $this->couponNumbers = $couponNumbers;

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
     * @return RequestAddCouponNumber
     */
    public function setCouponProvider($couponProvider)
    {
        \Assert\Assertion::string($couponProvider);

        $this->couponProvider = $couponProvider;

        return $this;
    }

}