<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 用户列表proto
 *
 * 由代码生成器生成, 不可人为修改
 * @author longbo
 */
class ResponseUserList extends ProtoBufferBase
{
    /**
     * User列表
     *
     * @var array
     * @required
     */
    private $list;

    /**
     * Total
     *
     * @var int
     * @optional
     */
    private $total = 0;

    /**
     * Page Number
     *
     * @var int
     * @optional
     */
    private $pageNo = 0;

    /**
     * Page Size
     *
     * @var int
     * @optional
     */
    private $pageSize = 0;

    /**
     * 偏移量
     *
     * @var int
     * @optional
     */
    private $offset = 0;

    /**
     * 数量
     *
     * @var int
     * @optional
     */
    private $count = 10;

    /**
     * 分站ID
     *
     * @var int
     * @required
     */
    private $siteId;

    /**
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param array $list
     * @return ResponseUserList
     */
    public function setList(array $list)
    {
        $this->list = $list;

        return $this;
    }
    /**
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param int $total
     * @return ResponseUserList
     */
    public function setTotal($total = 0)
    {
        $this->total = $total;

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
     * @return ResponseUserList
     */
    public function setPageNo($pageNo = 0)
    {
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
     * @return ResponseUserList
     */
    public function setPageSize($pageSize = 0)
    {
        $this->pageSize = $pageSize;

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
     * @return ResponseUserList
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
     * @return ResponseUserList
     */
    public function setCount($count = 10)
    {
        $this->count = $count;

        return $this;
    }
    /**
     * @return int
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param int $siteId
     * @return ResponseUserList
     */
    public function setSiteId($siteId)
    {
        \Assert\Assertion::integer($siteId);

        $this->siteId = $siteId;

        return $this;
    }

}