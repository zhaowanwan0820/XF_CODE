<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;
use NCFGroup\Common\Extensions\Base\Pageable;

/**
 * 投资记录列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzhuyan@
 */
class RequestDealLoanList extends AbstractRequestBase
{
    /**
     * 分页类
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 标项目ID
     *
     * @var int
     * @required
     */
    private $dealId;

    /**
     * forbidDealStatus
     *
     * @var array
     * @optional
     */
    private $forbidDealStatus = NULL;

    /**
     * 用户ID
     *
     * @var int
     * @optional
     */
    private $userId = '';

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestDealLoanList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

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
     * @return RequestDealLoanList
     */
    public function setDealId($dealId)
    {
        \Assert\Assertion::integer($dealId);

        $this->dealId = $dealId;

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
     * @return RequestDealLoanList
     */
    public function setForbidDealStatus(array $forbidDealStatus = NULL)
    {
        $this->forbidDealStatus = $forbidDealStatus;

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
     * @return RequestDealLoanList
     */
    public function setUserId($userId = '')
    {
        $this->userId = $userId;

        return $this;
    }

}