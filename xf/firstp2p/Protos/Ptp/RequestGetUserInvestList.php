<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 获得投资记录
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class RequestGetUserInvestList extends ProtoBufferBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * offset
     *
     * @var int
     * @optional
     */
    private $offset = 0;

    /**
     * count
     *
     * @var int
     * @optional
     */
    private $count = 10;

    /**
     * status
     *
     * @var string
     * @optional
     */
    private $status = '';

    /**
     * compound
     *
     * @var int
     * @optional
     */
    private $compound = 0;

    /**
     * 开始时间
     *
     * @var int
     * @optional
     */
    private $beginTime = 0;

    /**
     * filterLoantype
     *
     * @var int
     * @optional
     */
    private $filterLoantype = 0;

    /**
     * 结束时间
     *
     * @var int
     * @optional
     */
    private $endTime = 0;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestGetUserInvestList
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
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     * @return RequestGetUserInvestList
     */
    public function setOffset($offset = 0)
    {
        $this->offset = $offset;

        return $this;
    }
    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $count
     * @return RequestGetUserInvestList
     */
    public function setCount($count = 10)
    {
        $this->count = $count;

        return $this;
    }
    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return RequestGetUserInvestList
     */
    public function setStatus($status = '')
    {
        $this->status = $status;

        return $this;
    }
    /**
     * @return int
     */
    public function getCompound()
    {
        return $this->compound;
    }

    /**
     * @param int $compound
     * @return RequestGetUserInvestList
     */
    public function setCompound($compound = 0)
    {
        $this->compound = $compound;

        return $this;
    }
    /**
     * @return int
     */
    public function getBeginTime()
    {
        return $this->beginTime;
    }

    /**
     * @param int $beginTime
     * @return RequestGetUserInvestList
     */
    public function setBeginTime($beginTime = 0)
    {
        $this->beginTime = $beginTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getFilterLoantype()
    {
        return $this->filterLoantype;
    }

    /**
     * @param int $filterLoantype
     * @return RequestGetUserInvestList
     */
    public function setFilterLoantype($filterLoantype = 0)
    {
        $this->filterLoantype = $filterLoantype;

        return $this;
    }
    /**
     * @return int
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param int $endTime
     * @return RequestGetUserInvestList
     */
    public function setEndTime($endTime = 0)
    {
        $this->endTime = $endTime;

        return $this;
    }

}