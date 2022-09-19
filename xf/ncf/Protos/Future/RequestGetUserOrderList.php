<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获取用户订单列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class RequestGetUserOrderList extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * 页码
     *
     * @var int
     * @required
     */
    private $pageNo;

    /**
     * 每页数量
     *
     * @var int
     * @required
     */
    private $pageSize;

    /**
     * 排序方式
     *
     * @var int
     * @required
     */
    private $sort;

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestGetUserOrderList
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::string($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return int
     */
    public function getPageNo()
    {
        return $this->pageNo;
    }

    /**
     * @param int $pageNo
     * @return RequestGetUserOrderList
     */
    public function setPageNo($pageNo)
    {
        \Assert\Assertion::integer($pageNo);

        $this->pageNo = $pageNo;

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
     * @return RequestGetUserOrderList
     */
    public function setPageSize($pageSize)
    {
        \Assert\Assertion::integer($pageSize);

        $this->pageSize = $pageSize;

        return $this;
    }
    /**
     * @return int
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param int $sort
     * @return RequestGetUserOrderList
     */
    public function setSort($sort)
    {
        \Assert\Assertion::integer($sort);

        $this->sort = $sort;

        return $this;
    }

}