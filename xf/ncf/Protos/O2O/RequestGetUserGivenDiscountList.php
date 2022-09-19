<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取用户可赠送的投资券列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class RequestGetUserGivenDiscountList extends AbstractRequestBase
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
     * 投资券类型，1为返现券，2为加息券，3为黄金抵价券，0为返现券和加息券
     *
     * @var int
     * @optional
     */
    private $type = 0;

    /**
     * 交易类型，1为p2p，2为duotou，3为gold
     *
     * @var int
     * @optional
     */
    private $consumeType = 1;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestGetUserGivenDiscountList
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
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param int $page
     * @return RequestGetUserGivenDiscountList
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
     * @return RequestGetUserGivenDiscountList
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
     * @return RequestGetUserGivenDiscountList
     */
    public function setHasTotalCount($hasTotalCount = 1)
    {
        $this->hasTotalCount = $hasTotalCount;

        return $this;
    }
    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RequestGetUserGivenDiscountList
     */
    public function setType($type = 0)
    {
        $this->type = $type;

        return $this;
    }
    /**
     * @return int
     */
    public function getConsumeType()
    {
        return $this->consumeType;
    }

    /**
     * @param int $consumeType
     * @return RequestGetUserGivenDiscountList
     */
    public function setConsumeType($consumeType = 1)
    {
        $this->consumeType = $consumeType;

        return $this;
    }

}