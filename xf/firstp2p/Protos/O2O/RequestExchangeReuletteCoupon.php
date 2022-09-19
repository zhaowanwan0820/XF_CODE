<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 用户在兑换某券的时候，领取指定的券组
 *
 * 由代码生成器生成, 不可人为修改
 * @author yanbingrong
 */
class RequestExchangeReuletteCoupon extends AbstractRequestBase
{
    /**
     * 需要兑换的优惠券id
     *
     * @var int
     * @required
     */
    private $couponId;

    /**
     * 需要领取的券组id
     *
     * @var int
     * @required
     */
    private $couponGroupId;

    /**
     * 用户token码，对userId的加密
     *
     * @var string
     * @required
     */
    private $userToken;

    /**
     * @return int
     */
    public function getCouponId()
    {
        return $this->couponId;
    }

    /**
     * @param int $couponId
     * @return RequestExchangeReuletteCoupon
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
    public function getCouponGroupId()
    {
        return $this->couponGroupId;
    }

    /**
     * @param int $couponGroupId
     * @return RequestExchangeReuletteCoupon
     */
    public function setCouponGroupId($couponGroupId)
    {
        \Assert\Assertion::integer($couponGroupId);

        $this->couponGroupId = $couponGroupId;

        return $this;
    }
    /**
     * @return string
     */
    public function getUserToken()
    {
        return $this->userToken;
    }

    /**
     * @param string $userToken
     * @return RequestExchangeReuletteCoupon
     */
    public function setUserToken($userToken)
    {
        \Assert\Assertion::string($userToken);

        $this->userToken = $userToken;

        return $this;
    }

}