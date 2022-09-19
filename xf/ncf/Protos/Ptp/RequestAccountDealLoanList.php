<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;
use NCFGroup\Common\Extensions\Base\Pageable;

/**
 * 已投项目投资记录列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzhuyan@
 */
class RequestAccountDealLoanList extends AbstractRequestBase
{
    /**
     * 分页类
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 投资ID
     *
     * @var int
     * @required
     */
    private $loadId;

    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestAccountDealLoanList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }
    /**
     * @return int
     */
    public function getLoadId()
    {
        return $this->loadId;
    }

    /**
     * @param int $loadId
     * @return RequestAccountDealLoanList
     */
    public function setLoadId($loadId)
    {
        \Assert\Assertion::integer($loadId);

        $this->loadId = $loadId;

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
     * @return RequestAccountDealLoanList
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }

}