<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:供应商信息Proto
 *
 * 由代码生成器生成, 不可人为修改
 * @author vincent
 */
class ProtoSupplier extends ProtoBufferBase
{
    /**
     * 供应商ID
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * 网信用户id
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 供应商名称
     *
     * @var string
     * @optional
     */
    private $supplierName = '';

    /**
     * 创建时间
     *
     * @var int
     * @optional
     */
    private $createTime = '';

    /**
     * 最后修改时间
     *
     * @var int
     * @optional
     */
    private $updateTime = '';

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ProtoSupplier
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
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return ProtoSupplier
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getSupplierName()
    {
        return $this->supplierName;
    }

    /**
     * @param string $supplierName
     * @return ProtoSupplier
     */
    public function setSupplierName($supplierName = '')
    {
        $this->supplierName = $supplierName;

        return $this;
    }
    /**
     * @return int
     */
    public function getCreateTime()
    {
        return $this->createTime;
    }

    /**
     * @param int $createTime
     * @return ProtoSupplier
     */
    public function setCreateTime($createTime = '')
    {
        $this->createTime = $createTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getUpdateTime()
    {
        return $this->updateTime;
    }

    /**
     * @param int $updateTime
     * @return ProtoSupplier
     */
    public function setUpdateTime($updateTime = '')
    {
        $this->updateTime = $updateTime;

        return $this;
    }

}