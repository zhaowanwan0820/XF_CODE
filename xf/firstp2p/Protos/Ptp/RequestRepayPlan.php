<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 用户回款计划Request
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class RequestRepayPlan extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 起始时间
     *
     * @var int
     * @optional
     */
    private $beginTime = 0;

    /**
     * 结束时间
     *
     * @var int
     * @optional
     */
    private $endTime = 0;

    /**
     * 还款类型
     *
     * @var int
     * @optional
     */
    private $type = 0;

    /**
     * 每页数量
     *
     * @var int
     * @optional
     */
    private $count = 10;

    /**
     * 起始位置
     *
     * @var int
     * @optional
     */
    private $offset = 0;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestRepayPlan
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
    public function getBeginTime()
    {
        return $this->beginTime;
    }

    /**
     * @param int $beginTime
     * @return RequestRepayPlan
     */
    public function setBeginTime($beginTime = 0)
    {
        $this->beginTime = $beginTime;

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
     * @return RequestRepayPlan
     */
    public function setEndTime($endTime = 0)
    {
        $this->endTime = $endTime;

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
     * @return RequestRepayPlan
     */
    public function setType($type = 0)
    {
        $this->type = $type;

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
     * @return RequestRepayPlan
     */
    public function setCount($count = 10)
    {
        $this->count = $count;

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
     * @return RequestRepayPlan
     */
    public function setOffset($offset = 0)
    {
        $this->offset = $offset;

        return $this;
    }

}