<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 领取特定规则id下的投资券
 *
 * 由代码生成器生成, 不可人为修改
 * @author yanbingrong<yanbingrong@ucfgroup.com>
 */
class RequestAcquireRuleDiscount extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 投资券规则id
     *
     * @var int
     * @required
     */
    private $discountRuleId;

    /**
     * 券码唯一token
     *
     * @var string
     * @required
     */
    private $token;

    /**
     * 起投金额
     *
     * @var float
     * @optional
     */
    private $bidAmount = 0;

    /**
     * 起投期限
     *
     * @var int
     * @optional
     */
    private $bidDayLimit = 0;

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
     * @return RequestAcquireRuleDiscount
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
    public function getDiscountRuleId()
    {
        return $this->discountRuleId;
    }

    /**
     * @param int $discountRuleId
     * @return RequestAcquireRuleDiscount
     */
    public function setDiscountRuleId($discountRuleId)
    {
        \Assert\Assertion::integer($discountRuleId);

        $this->discountRuleId = $discountRuleId;

        return $this;
    }
    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return RequestAcquireRuleDiscount
     */
    public function setToken($token)
    {
        \Assert\Assertion::string($token);

        $this->token = $token;

        return $this;
    }
    /**
     * @return float
     */
    public function getBidAmount()
    {
        return $this->bidAmount;
    }

    /**
     * @param float $bidAmount
     * @return RequestAcquireRuleDiscount
     */
    public function setBidAmount($bidAmount = 0)
    {
        $this->bidAmount = $bidAmount;

        return $this;
    }
    /**
     * @return int
     */
    public function getBidDayLimit()
    {
        return $this->bidDayLimit;
    }

    /**
     * @param int $bidDayLimit
     * @return RequestAcquireRuleDiscount
     */
    public function setBidDayLimit($bidDayLimit = 0)
    {
        $this->bidDayLimit = $bidDayLimit;

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
     * @return RequestAcquireRuleDiscount
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
     * @return RequestAcquireRuleDiscount
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
     * @return RequestAcquireRuleDiscount
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
     * @return RequestAcquireRuleDiscount
     */
    public function setRebateLimit($rebateLimit = 0)
    {
        $this->rebateLimit = $rebateLimit;

        return $this;
    }

}