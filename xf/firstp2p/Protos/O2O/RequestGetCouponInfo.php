<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取券详情
 *
 * 由代码生成器生成, 不可人为修改
 * @author quanhengzhuang
 */
class RequestGetCouponInfo extends AbstractRequestBase
{
    /**
     * 优惠券id
     *
     * @var int
     * @optional
     */
    private $couponId = 0;

    /**
     * 优惠码
     *
     * @var string
     * @optional
     */
    private $couponNumber = '';

    /**
     * 店铺ID
     *
     * @var int
     * @optional
     */
    private $storeId = 0;

    /**
     * @return int
     */
    public function getCouponId()
    {
        return $this->couponId;
    }

    /**
     * @param int $couponId
     * @return RequestGetCouponInfo
     */
    public function setCouponId($couponId = 0)
    {
        $this->couponId = $couponId;

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
     * @return RequestGetCouponInfo
     */
    public function setCouponNumber($couponNumber = '')
    {
        $this->couponNumber = $couponNumber;

        return $this;
    }
    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param int $storeId
     * @return RequestGetCouponInfo
     */
    public function setStoreId($storeId = 0)
    {
        $this->storeId = $storeId;

        return $this;
    }

}