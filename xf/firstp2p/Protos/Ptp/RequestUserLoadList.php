<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 资金纪录proto
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangfei5
 */
class RequestUserLoadList extends ProtoBufferBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 纪录偏移量
     *
     * @var int
     * @optional
     */
    private $offset = 0;

    /**
     * 纪录数量
     *
     * @var int
     * @optional
     */
    private $count = 20;

    /**
     * 状态，默认为0；0-全部 1-投资中 2-满标 4-还款中 5-已还清；status>0时，支持多个状态合并查询，status值以英文逗号隔开，如status=1,2 查询投资中和满标的列表
     *
     * @var int
     * @optional
     */
    private $status = '0';

    /**
     * 是否展示通知贷的标，默认不传为0，不展示，1展示
     *
     * @var int
     * @optional
     */
    private $compound = 0;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestUserLoadList
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
     * @return RequestUserLoadList
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
     * @return RequestUserLoadList
     */
    public function setCount($count = 20)
    {
        $this->count = $count;

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
     * @return RequestUserLoadList
     */
    public function setStatus($status = '0')
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
     * @return RequestUserLoadList
     */
    public function setCompound($compound = 0)
    {
        $this->compound = $compound;

        return $this;
    }

}