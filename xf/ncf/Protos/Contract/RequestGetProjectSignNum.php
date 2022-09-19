<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 获取项目合同签署数量
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestGetProjectSignNum extends ProtoBufferBase
{
    /**
     * 标的ID
     *
     * @var int
     * @required
     */
    private $projectId;

    /**
     * 1:借款人,2:担保,3:资产管理,4:委托方,0:全部
     *
     * @var int
     * @required
     */
    private $role;

    /**
     * roleID
     *
     * @var int
     * @optional
     */
    private $id = 0;

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
     * @return RequestGetProjectSignNum
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
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param int $role
     * @return RequestGetProjectSignNum
     */
    public function setRole($role)
    {
        \Assert\Assertion::integer($role);

        $this->role = $role;

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
     * @return RequestGetProjectSignNum
     */
    public function setId($id = 0)
    {
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
     * @return RequestGetProjectSignNum
     */
    public function setSourceType($sourceType = 0)
    {
        $this->sourceType = $sourceType;

        return $this;
    }

}