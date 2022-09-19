<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 提现列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangfei
 */
class RequestWithdraw extends ProtoBufferBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 页码
     *
     * @var int
     * @optional
     */
    private $pageNum = 0;

    /**
     * 一页大小
     *
     * @var int
     * @optional
     */
    private $pageSize = 0;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestWithdraw
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
    public function getPageNum()
    {
        return $this->pageNum;
    }

    /**
     * @param int $pageNum
     * @return RequestWithdraw
     */
    public function setPageNum($pageNum = 0)
    {
        $this->pageNum = $pageNum;

        return $this;
    }
    /**
     * @return int
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * @param int $pageSize
     * @return RequestWithdraw
     */
    public function setPageSize($pageSize = 0)
    {
        $this->pageSize = $pageSize;

        return $this;
    }

}