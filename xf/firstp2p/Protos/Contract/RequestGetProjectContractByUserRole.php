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
class RequestGetProjectContractByUserRole extends ProtoBufferBase
{
    /**
     * 角色类型(1:借款人,2:投资人,3:担保方,4:咨询方,5:委托方)
     *
     * @var int
     * @required
     */
    private $role;

    /**
     * ID
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * 标的ID
     *
     * @var int
     * @optional
     */
    private $pageNo = 1;

    /**
     * 每页显示的记录数
     *
     * @var int
     * @optional
     */
    private $pageSize = 10;

    /**
     * 是否按项目id分租
     *
     * @var boolean
     * @optional
     */
    private $groupByPid = false;

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
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param int $role
     * @return RequestGetProjectContractByUserRole
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
     * @return RequestGetProjectContractByUserRole
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
    public function getPageNo()
    {
        return $this->pageNo;
    }

    /**
     * @param int $pageNo
     * @return RequestGetProjectContractByUserRole
     */
    public function setPageNo($pageNo = 1)
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
     * @return RequestGetProjectContractByUserRole
     */
    public function setPageSize($pageSize = 10)
    {
        $this->pageSize = $pageSize;

        return $this;
    }
    /**
     * @return boolean
     */
    public function getGroupByPid()
    {
        return $this->groupByPid;
    }

    /**
     * @param boolean $groupByPid
     * @return RequestGetProjectContractByUserRole
     */
    public function setGroupByPid($groupByPid = false)
    {
        $this->groupByPid = $groupByPid;

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
     * @return RequestGetProjectContractByUserRole
     */
    public function setSourceType($sourceType = 0)
    {
        $this->sourceType = $sourceType;

        return $this;
    }

}