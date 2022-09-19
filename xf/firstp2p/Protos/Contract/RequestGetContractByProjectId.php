<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 按照项目ID查找项目合同
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestGetContractByProjectId extends ProtoBufferBase
{
    /**
     * 项目ID
     *
     * @var int
     * @required
     */
    private $projectId;

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
     * @return int
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * @param int $projectId
     * @return RequestGetContractByProjectId
     */
    public function setProjectId($projectId)
    {
        \Assert\Assertion::integer($projectId);

        $this->projectId = $projectId;

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
     * @return RequestGetContractByProjectId
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
     * @return RequestGetContractByProjectId
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
     * @return RequestGetContractByProjectId
     */
    public function setSourceType($sourceType = 0)
    {
        $this->sourceType = $sourceType;

        return $this;
    }

}