<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取用户领取券码列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class RequestGetUserCouponList extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 券码状态
     *
     * @var int
     * @required
     */
    private $status;

    /**
     * 页码
     *
     * @var int
     * @optional
     */
    private $page = 1;

    /**
     * 每页显示
     *
     * @var int
     * @optional
     */
    private $pageSize = 10;

    /**
     * 是否包含记录总数
     *
     * @var int
     * @optional
     */
    private $hasTotalCount = 1;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestGetUserCouponList
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
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return RequestGetUserCouponList
     */
    public function setStatus($status)
    {
        \Assert\Assertion::integer($status);

        $this->status = $status;

        return $this;
    }
    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param int $page
     * @return RequestGetUserCouponList
     */
    public function setPage($page = 1)
    {
        $this->page = $page;

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
     * @return RequestGetUserCouponList
     */
    public function setPageSize($pageSize = 10)
    {
        $this->pageSize = $pageSize;

        return $this;
    }
    /**
     * @return int
     */
    public function getHasTotalCount()
    {
        return $this->hasTotalCount;
    }

    /**
     * @param int $hasTotalCount
     * @return RequestGetUserCouponList
     */
    public function setHasTotalCount($hasTotalCount = 1)
    {
        $this->hasTotalCount = $hasTotalCount;

        return $this;
    }

}