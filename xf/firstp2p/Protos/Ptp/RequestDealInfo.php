<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 标项目详情
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class RequestDealInfo extends ProtoBufferBase
{
    /**
     * 标项目ID
     *
     * @var int
     * @required
     */
    private $dealId;

    /**
     * 用户ID
     *
     * @var int
     * @optional
     */
    private $userId = '';

    /**
     * forbidDealStatus
     *
     * @var array
     * @optional
     */
    private $forbidDealStatus = NULL;

    /**
     * 投资记录首页记录数
     *
     * @var int
     * @optional
     */
    private $dealLoanSize = 0;

    /**
     * @return int
     */
    public function getDealId()
    {
        return $this->dealId;
    }

    /**
     * @param int $dealId
     * @return RequestDealInfo
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
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestDealInfo
     */
    public function setUserId($userId = '')
    {
        $this->userId = $userId;

        return $this;
    }
    /**
     * @return array
     */
    public function getForbidDealStatus()
    {
        return $this->forbidDealStatus;
    }

    /**
     * @param array $forbidDealStatus
     * @return RequestDealInfo
     */
    public function setForbidDealStatus(array $forbidDealStatus = NULL)
    {
        $this->forbidDealStatus = $forbidDealStatus;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealLoanSize()
    {
        return $this->dealLoanSize;
    }

    /**
     * @param int $dealLoanSize
     * @return RequestDealInfo
     */
    public function setDealLoanSize($dealLoanSize = 0)
    {
        $this->dealLoanSize = $dealLoanSize;

        return $this;
    }

}