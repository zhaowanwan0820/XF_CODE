<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 用户拒绝兑换券请求
 *
 * 由代码生成器生成, 不可人为修改
 * @author quanhengzhuang
 */
class RequestSetCouponUnused extends AbstractRequestBase
{
    /**
     * 优惠券id
     *
     * @var int
     * @required
     */
    private $couponId;

    /**
     * 商店用户id
     *
     * @var int
     * @required
     */
    private $storeId;

    /**
     * 用户id
     *
     * @var int
     * @required
     */
    private $ownerUserId;

    /**
     * @return int
     */
    public function getCouponId()
    {
        return $this->couponId;
    }

    /**
     * @param int $couponId
     * @return RequestSetCouponUnused
     */
    public function setCouponId($couponId)
    {
        \Assert\Assertion::integer($couponId);

        $this->couponId = $couponId;

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
     * @return RequestSetCouponUnused
     */
    public function setStoreId($storeId)
    {
        \Assert\Assertion::integer($storeId);

        $this->storeId = $storeId;

        return $this;
    }
    /**
     * @return int
     */
    public function getOwnerUserId()
    {
        return $this->ownerUserId;
    }

    /**
     * @param int $ownerUserId
     * @return RequestSetCouponUnused
     */
    public function setOwnerUserId($ownerUserId)
    {
        \Assert\Assertion::integer($ownerUserId);

        $this->ownerUserId = $ownerUserId;

        return $this;
    }

}