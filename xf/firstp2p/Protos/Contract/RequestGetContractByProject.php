<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 按照dealId,cid获取合同
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestGetContractByProject extends ProtoBufferBase
{
    /**
     * 项目ID
     *
     * @var int
     * @required
     */
    private $projectId;

    /**
     * 合同编号
     *
     * @var int
     * @required
     */
    private $id;

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
     * @return RequestGetContractByProject
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return RequestGetContractByProject
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

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
     * @return RequestGetContractByProject
     */
    public function setSourceType($sourceType = 0)
    {
        $this->sourceType = $sourceType;

        return $this;
    }

}