<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 获取项目描述信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestGetProjectDescription extends ProtoBufferBase
{
    /**
     * 项目ID
     *
     * @var int
     * @required
     */
    private $projectId;

    /**
     * 类型(0:描述;1:委托投资说明)
     *
     * @var int
     * @optional
     */
    private $type = 1;

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
     * @return RequestGetProjectDescription
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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RequestGetProjectDescription
     */
    public function setType($type = 1)
    {
        $this->type = $type;

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
     * @return RequestGetProjectDescription
     */
    public function setSourceType($sourceType = 0)
    {
        $this->sourceType = $sourceType;

        return $this;
    }

}