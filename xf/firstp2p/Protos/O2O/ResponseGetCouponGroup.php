<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;
use NCFGroup\Protos\O2O\ProtoCouponGroup;
use NCFGroup\Protos\O2O\ProtoProduct;
use NCFGroup\Protos\O2O\ProtoStore;

/**
 * 商品列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author Yu TAO <yutao@ucfgroup.com>
 */
class ResponseGetCouponGroup extends ResponseBase
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
     * 零售店列表
     *
     * @var array<ProtoStore>
     * @required
     */
    private $storeList;

    /**
     * @return ProtoCouponGroup
     */
    public function getCouponGroup()
    {
        return $this->couponGroup;
    }

    /**
     * @param ProtoCouponGroup $couponGroup
     * @return ResponseGetCouponGroup
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
     * @return ResponseGetCouponGroup
     */
    public function setProduct(ProtoProduct $product)
    {
        $this->product = $product;

        return $this;
    }
    /**
     * @return array<ProtoStore>
     */
    public function getStoreList()
    {
        return $this->storeList;
    }

    /**
     * @param array<ProtoStore> $storeList
     * @return ResponseGetCouponGroup
     */
    public function setStoreList(array $storeList)
    {
        $this->storeList = $storeList;

        return $this;
    }

}