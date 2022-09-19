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
class ResponseUserLoadList extends ProtoBufferBase
{
    /**
     * 资金纪录列表
     *
     * @var array
     * @required
     */
    private $list;

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
     * 纪录数量
     *
     * @var int
     * @required
     */
    private $allCount;

    /**
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param array $list
     * @return ResponseUserLoadList
     */
    public function setList(array $list)
    {
        $this->list = $list;

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
     * @return ResponseUserLoadList
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
     * @return ResponseUserLoadList
     */
    public function setCount($count = 20)
    {
        $this->count = $count;

        return $this;
    }
    /**
     * @return int
     */
    public function getAllCount()
    {
        return $this->allCount;
    }

    /**
     * @param int $allCount
     * @return ResponseUserLoadList
     */
    public function setAllCount($allCount)
    {
        \Assert\Assertion::integer($allCount);

        $this->allCount = $allCount;

        return $this;
    }

}