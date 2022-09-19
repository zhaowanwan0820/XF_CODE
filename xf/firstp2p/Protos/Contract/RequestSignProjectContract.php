<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 项目签署合同
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestSignProjectContract extends ProtoBufferBase
{
    /**
     * 项目ID
     *
     * @var int
     * @required
     */
    private $projectId;

    /**
     * 1:借款人,2:担保,3:资产管理,5:委托机构,4:全部
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
     * 是否自动签署
     *
     * @var boolean
     * @optional
     */
    private $autoSign = false;

    /**
     * @return int
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * @param int $projectId
     * @return RequestSignProjectContract
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
     * @return RequestSignProjectContract
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
     * @return RequestSignProjectContract
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
     * @return RequestSignProjectContract
     */
    public function setSourceType($sourceType = 0)
    {
        $this->sourceType = $sourceType;

        return $this;
    }
    /**
     * @return boolean
     */
    public function getAutoSign()
    {
        return $this->autoSign;
    }

    /**
     * @param boolean $autoSign
     * @return RequestSignProjectContract
     */
    public function setAutoSign($autoSign = false)
    {
        $this->autoSign = $autoSign;

        return $this;
    }

}