<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 生成投资券码
 *
 * 由代码生成器生成, 不可人为修改
 * @author yanbingrong<yanbingrong@ucfgroup.com>
 */
class RequestAcquireDiscount extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 券组ID
     *
     * @var int
     * @required
     */
    private $discountGroupId;

    /**
     * 券码唯一token
     *
     * @var string
     * @required
     */
    private $couponToken;

    /**
     * 领取备注
     *
     * @var string
     * @optional
     */
    private $remark = '';

    /**
     * 交易id
     *
     * @var int
     * @optional
     */
    private $dealLoadId = 0;

    /**
     * 返利金额，覆盖投资券的金额配置
     *
     * @var float
     * @optional
     */
    private $rebateAmount = 0;

    /**
     * 返利期限，覆盖投资券的期限配置
     *
     * @var int
     * @optional
     */
    private $rebateLimit = 0;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestAcquireDiscount
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return int
     */
    public function getDiscountGroupId()
    {
        return $this->discountGroupId;
    }

    /**
     * @param int $discountGroupId
     * @return RequestAcquireDiscount
     */
    public function setDiscountGroupId($discountGroupId)
    {
        \Assert\Assertion::integer($discountGroupId);

        $this->discountGroupId = $discountGroupId;

        return $this;
    }
    /**
     * @return string
     */
    public function getCouponToken()
    {
        return $this->couponToken;
    }

    /**
     * @param string $couponToken
     * @return RequestAcquireDiscount
     */
    public function setCouponToken($couponToken)
    {
        \Assert\Assertion::string($couponToken);

        $this->couponToken = $couponToken;

        return $this;
    }
    /**
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * @param string $remark
     * @return RequestAcquireDiscount
     */
    public function setRemark($remark = '')
    {
        $this->remark = $remark;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealLoadId()
    {
        return $this->dealLoadId;
    }

    /**
     * @param int $dealLoadId
     * @return RequestAcquireDiscount
     */
    public function setDealLoadId($dealLoadId = 0)
    {
        $this->dealLoadId = $dealLoadId;

        return $this;
    }
    /**
     * @return float
     */
    public function getRebateAmount()
    {
        return $this->rebateAmount;
    }

    /**
     * @param float $rebateAmount
     * @return RequestAcquireDiscount
     */
    public function setRebateAmount($rebateAmount = 0)
    {
        $this->rebateAmount = $rebateAmount;

        return $this;
    }
    /**
     * @return int
     */
    public function getRebateLimit()
    {
        return $this->rebateLimit;
    }

    /**
     * @param int $rebateLimit
     * @return RequestAcquireDiscount
     */
    public function setRebateLimit($rebateLimit = 0)
    {
        $this->rebateLimit = $rebateLimit;

        return $this;
    }

}