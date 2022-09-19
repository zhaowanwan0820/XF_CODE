<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 获得资金记录
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class RequestGetUserFinancialRecord extends ProtoBufferBase
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
    private $count = 20;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestGetUserFinancialRecord
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
     * @return RequestGetUserFinancialRecord
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
     * @return RequestGetUserFinancialRecord
     */
    public function setCount($count = 20)
    {
        $this->count = $count;

        return $this;
    }

}