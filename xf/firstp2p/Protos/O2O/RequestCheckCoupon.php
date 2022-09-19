<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:向合作方获取
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class RequestCheckCoupon extends ProtoBufferBase
{
    /**
     * 网信给合作方分配的唯一key
     *
     * @var string
     * @required
     */
    private $clientId;

    /**
     * 券码
     *
     * @var string
     * @required
     */
    private $couponNumber;

    /**
     * 优惠券Id
     *
     * @var int
     * @optional
     */
    private $couponId = '';

    /**
     * 用户编号
     *
     * @var string
     * @optional
     */
    private $userId = '';

    /**
     * 合作方商品编号
     *
     * @var string
     * @optional
     */
    private $productId = '';

    /**
     * 合作方其他信息
     *
     * @var string
     * @optional
     */
    private $extra = '';

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     * @return RequestCheckCoupon
     */
    public function setClientId($clientId)
    {
        \Assert\Assertion::string($clientId);

        $this->clientId = $clientId;

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
     * @return RequestCheckCoupon
     */
    public function setCouponNumber($couponNumber)
    {
        \Assert\Assertion::string($couponNumber);

        $this->couponNumber = $couponNumber;

        return $this;
    }
    /**
     * @return int
     */
    public function getCouponId()
    {
        return $this->couponId;
    }

    /**
     * @param int $couponId
     * @return RequestCheckCoupon
     */
    public function setCouponId($couponId = '')
    {
        $this->couponId = $couponId;

        return $this;
    }
    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestCheckCoupon
     */
    public function setUserId($userId = '')
    {
        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param string $productId
     * @return RequestCheckCoupon
     */
    public function setProductId($productId = '')
    {
        $this->productId = $productId;

        return $this;
    }
    /**
     * @return string
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param string $extra
     * @return RequestCheckCoupon
     */
    public function setExtra($extra = '')
    {
        $this->extra = $extra;

        return $this;
    }

}