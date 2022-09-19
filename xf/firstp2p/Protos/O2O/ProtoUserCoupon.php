<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;
use NCFGroup\Protos\O2O\ProtoCouponGroup;
use NCFGroup\Protos\O2O\ProtoProduct;
use NCFGroup\Protos\O2O\ProtoCoupon;

/**
 * 商品列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author Yu TAO <yutao@ucfgroup.com>
 */
class ProtoUserCoupon extends ResponseBase
{
    /**
     * 券组数据
     *
     * @var ProtoCouponGroup
     * @required
     */
    private $couponGroup;

    /**
     * 商品数据
     *
     * @var ProtoProduct
     * @required
     */
    private $product;

    /**
     * 券码列表
     *
     * @var ProtoCoupon
     * @required
     */
    private $coupon;

    /**
     * @return ProtoCouponGroup
     */
    public function getCouponGroup()
    {
        return $this->couponGroup;
    }

    /**
     * @param ProtoCouponGroup $couponGroup
     * @return ProtoUserCoupon
     */
    public function setCouponGroup(ProtoCouponGroup $couponGroup)
    {
        $this->couponGroup = $couponGroup;

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
     * @return ProtoUserCoupon
     */
    public function setProduct(ProtoProduct $product)
    {
        $this->product = $product;

        return $this;
    }
    /**
     * @return ProtoCoupon
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * @param ProtoCoupon $coupon
     * @return ProtoUserCoupon
     */
    public function setCoupon(ProtoCoupon $coupon)
    {
        $this->coupon = $coupon;

        return $this;
    }

}