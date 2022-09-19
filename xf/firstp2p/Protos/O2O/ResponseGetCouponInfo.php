<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 获取优惠券信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author quanhengzhuang
 */
class ResponseGetCouponInfo extends ResponseBase
{
    /**
     * 优惠券
     *
     * @var array
     * @required
     */
    private $coupon;

    /**
     * 产品
     *
     * @var array
     * @required
     */
    private $product;

    /**
     * 券组
     *
     * @var array
     * @required
     */
    private $couponGroup;

    /**
     * 商店列表
     *
     * @var array
     * @required
     */
    private $storeList;

    /**
     * 已使用商店
     *
     * @var array
     * @required
     */
    private $storeUsed;

    /**
     * 券订单信息
     *
     * @var array
     * @required
     */
    private $couponOrder;

    /**
     * @return array
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * @param array $coupon
     * @return ResponseGetCouponInfo
     */
    public function setCoupon(array $coupon)
    {
        $this->coupon = $coupon;

        return $this;
    }
    /**
     * @return array
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param array $product
     * @return ResponseGetCouponInfo
     */
    public function setProduct(array $product)
    {
        $this->product = $product;

        return $this;
    }
    /**
     * @return array
     */
    public function getCouponGroup()
    {
        return $this->couponGroup;
    }

    /**
     * @param array $couponGroup
     * @return ResponseGetCouponInfo
     */
    public function setCouponGroup(array $couponGroup)
    {
        $this->couponGroup = $couponGroup;

        return $this;
    }
    /**
     * @return array
     */
    public function getStoreList()
    {
        return $this->storeList;
    }

    /**
     * @param array $storeList
     * @return ResponseGetCouponInfo
     */
    public function setStoreList(array $storeList)
    {
        $this->storeList = $storeList;

        return $this;
    }
    /**
     * @return array
     */
    public function getStoreUsed()
    {
        return $this->storeUsed;
    }

    /**
     * @param array $storeUsed
     * @return ResponseGetCouponInfo
     */
    public function setStoreUsed(array $storeUsed)
    {
        $this->storeUsed = $storeUsed;

        return $this;
    }
    /**
     * @return array
     */
    public function getCouponOrder()
    {
        return $this->couponOrder;
    }

    /**
     * @param array $couponOrder
     * @return ResponseGetCouponInfo
     */
    public function setCouponOrder(array $couponOrder)
    {
        $this->couponOrder = $couponOrder;

        return $this;
    }

}