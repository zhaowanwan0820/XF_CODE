<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 获取劵收益信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangshijie
 */
class RequestDiscountExpectedEarningInfo extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 标的ID
     *
     * @var int
     * @required
     */
    private $dealId;

    /**
     * 劵ID
     *
     * @var int
     * @required
     */
    private $discountId;

    /**
     * 投资金额
     *
     * @var float
     * @required
     */
    private $money;

    /**
     * 分站ID
     *
     * @var int
     * @optional
     */
    private $siteId = '1';

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestDiscountExpectedEarningInfo
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
    public function getDealId()
    {
        return $this->dealId;
    }

    /**
     * @param int $dealId
     * @return RequestDiscountExpectedEarningInfo
     */
    public function setDealId($dealId)
    {
        \Assert\Assertion::integer($dealId);

        $this->dealId = $dealId;

        return $this;
    }
    /**
     * @return int
     */
    public function getDiscountId()
    {
        return $this->discountId;
    }

    /**
     * @param int $discountId
     * @return RequestDiscountExpectedEarningInfo
     */
    public function setDiscountId($discountId)
    {
        \Assert\Assertion::integer($discountId);

        $this->discountId = $discountId;

        return $this;
    }
    /**
     * @return float
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * @param float $money
     * @return RequestDiscountExpectedEarningInfo
     */
    public function setMoney($money)
    {
        \Assert\Assertion::float($money);

        $this->money = $money;

        return $this;
    }
    /**
     * @return int
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param int $siteId
     * @return RequestDiscountExpectedEarningInfo
     */
    public function setSiteId($siteId = '1')
    {
        $this->siteId = $siteId;

        return $this;
    }

}