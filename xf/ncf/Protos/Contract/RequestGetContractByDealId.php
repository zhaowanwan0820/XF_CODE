<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 按照投资记录获取合同列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestGetContractByDealId extends ProtoBufferBase
{
    /**
     * 标的ID
     *
     * @var int
     * @required
     */
    private $dealId;

    /**
     * 标的ID
     *
     * @var int
     * @optional
     */
    private $pageNo = NULL;

    /**
     * 条件
     *
     * @var string
     * @optional
     */
    private $where = NULL;

    /**
     * 来源类型(0:P2P,1:通知贷,2:交易所,3:专享)
     *
     * @var int
     * @optional
     */
    private $sourceType = 0;

    /**
     * 每页显示的记录数
     *
     * @var int
     * @optional
     */
    private $pageSize = 0;

    /**
     * @return int
     */
    public function getDealId()
    {
        return $this->dealId;
    }

    /**
     * @param int $dealId
     * @return RequestGetContractByDealId
     */
    public function setDealId($dealId)
    {
        \Assert\Assertion::integer($dealId);

        $this->dealId = $dealId;

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
     * @return RequestGetContractByDealId
     */
    public function setPageNo($pageNo = NULL)
    {
        $this->pageNo = $pageNo;

        return $this;
    }
    /**
     * @return string
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * @param string $where
     * @return RequestGetContractByDealId
     */
    public function setWhere($where = NULL)
    {
        $this->where = $where;

        return $this;
    }
    /**
     * @return int
     */
    public function getSourceType()
    {
        return $this->sourceType;
    }

    /**
     * @param int $sourceType
     * @return RequestGetContractByDealId
     */
    public function setSourceType($sourceType = 0)
    {
        $this->sourceType = $sourceType;

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
     * @return RequestGetContractByDealId
     */
    public function setPageSize($pageSize = 0)
    {
        $this->pageSize = $pageSize;

        return $this;
    }

}