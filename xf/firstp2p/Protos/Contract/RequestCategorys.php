<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 获取分类列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestCategorys extends ProtoBufferBase
{
    /**
     * 类型 0:p2p,1:DT
     *
     * @var int
     * @optional
     */
    private $type = '0';

    /**
     * 是否删除
     *
     * @var int
     * @optional
     */
    private $isDelete = '0';

    /**
     * 使用状态
     *
     * @var int
     * @optional
     */
    private $useStatus = NULL;

    /**
     * 分类名称
     *
     * @var string
     * @optional
     */
    private $typeName = NULL;

    /**
     * 个人/公司借款
     *
     * @var int
     * @optional
     */
    private $contractType = NULL;

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RequestCategorys
     */
    public function setType($type = '0')
    {
        $this->type = $type;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsDelete()
    {
        return $this->isDelete;
    }

    /**
     * @param int $isDelete
     * @return RequestCategorys
     */
    public function setIsDelete($isDelete = '0')
    {
        $this->isDelete = $isDelete;

        return $this;
    }
    /**
     * @return int
     */
    public function getUseStatus()
    {
        return $this->useStatus;
    }

    /**
     * @param int $useStatus
     * @return RequestCategorys
     */
    public function setUseStatus($useStatus = NULL)
    {
        $this->useStatus = $useStatus;

        return $this;
    }
    /**
     * @return string
     */
    public function getTypeName()
    {
        return $this->typeName;
    }

    /**
     * @param string $typeName
     * @return RequestCategorys
     */
    public function setTypeName($typeName = NULL)
    {
        $this->typeName = $typeName;

        return $this;
    }
    /**
     * @return int
     */
    public function getContractType()
    {
        return $this->contractType;
    }

    /**
     * @param int $contractType
     * @return RequestCategorys
     */
    public function setContractType($contractType = NULL)
    {
        $this->contractType = $contractType;

        return $this;
    }

}