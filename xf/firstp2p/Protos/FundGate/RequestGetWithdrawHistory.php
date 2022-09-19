<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 查询撤单记录
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class RequestGetWithdrawHistory extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 分页类
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestGetWithdrawHistory
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetWithdrawHistory
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }

}