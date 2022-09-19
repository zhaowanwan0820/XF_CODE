<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 标项目详情
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class RequestCfpSort extends ProtoBufferBase
{
    /**
     * 排序顺序
     *
     * @var int
     * @optional
     */
    private $sort = 0;

    /**
     * 理财师ID
     *
     * @var int
     * @required
     */
    private $cfpId;

    /**
     * 排序类型
     *
     * @var int
     * @optional
     */
    private $type = 0;

    /**
     * 数量
     *
     * @var int
     * @optional
     */
    private $count = 10;

    /**
     * 便宜量
     *
     * @var int
     * @optional
     */
    private $offset = 0;

    /**
     * @return int
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param int $sort
     * @return RequestCfpSort
     */
    public function setSort($sort = 0)
    {
        $this->sort = $sort;

        return $this;
    }
    /**
     * @return int
     */
    public function getCfpId()
    {
        return $this->cfpId;
    }

    /**
     * @param int $cfpId
     * @return RequestCfpSort
     */
    public function setCfpId($cfpId)
    {
        \Assert\Assertion::integer($cfpId);

        $this->cfpId = $cfpId;

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
     * @return RequestCfpSort
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
     * @return RequestCfpSort
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
     * @return RequestCfpSort
     */
    public function setOffset($offset = 0)
    {
        $this->offset = $offset;

        return $this;
    }

}