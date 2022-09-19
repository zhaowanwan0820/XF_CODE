<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 用户可用劵个数
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangshijie
 */
class RequestDiscountCount extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 标ID
     *
     * @var int
     * @required
     */
    private $dealId;

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
     * @return RequestDiscountCount
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
     * @return RequestDiscountCount
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
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param int $siteId
     * @return RequestDiscountCount
     */
    public function setSiteId($siteId = '1')
    {
        $this->siteId = $siteId;

        return $this;
    }

}