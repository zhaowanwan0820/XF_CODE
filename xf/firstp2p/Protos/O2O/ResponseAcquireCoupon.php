<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;
use NCFGroup\Protos\O2O\ProtoCoupon;
use NCFGroup\Protos\O2O\ProtoProduct;

/**
 * 商品列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author Yu TAO <yutao@ucfgroup.com>
 */
class ResponseAcquireCoupon extends ResponseBase
{
    /**
     * 券码数据
     *
     * @var ProtoCoupon
     * @required
     */
    private $coupon;

    /**
     * 商品数据
     *
     * @var ProtoProduct
     * @required
     */
    private $product;

    /**
     * 券组数据
     *
     * @var ProtoCouponGroup
     * @optional
     */
    private $couponGroup = NULL;

    /**
     * @return ProtoCoupon
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * @param ProtoCoupon $coupon
     * @return ResponseAcquireCoupon
     */
    public function setCoupon(ProtoCoupon $coupon)
    {
        $this->coupon = $coupon;

        return $this;
    }
    /**
     * @return ProtoProduct
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param ProtoProduct $product
     * @return ResponseAcquireCoupon
     */
    public function setProduct(ProtoProduct $product)
    {
        $this->product = $product;

        return $this;
    }
    /**
     * @return ProtoCouponGroup
     */
    public function getCouponGroup()
    {
        return $this->couponGroup;
    }

    /**
     * @param ProtoCouponGroup $couponGroup
     * @return ResponseAcquireCoupon
     */
    public function setCouponGroup(ProtoCouponGroup $couponGroup = NULL)
    {
        $this->couponGroup = $couponGroup;

        return $this;
    }

}