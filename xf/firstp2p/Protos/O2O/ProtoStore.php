<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:零售店信息Proto
 *
 * 由代码生成器生成, 不可人为修改
 * @author vincent
 */
class ProtoStore extends ProtoBufferBase
{
    /**
     * 零售店ID
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * 零售店名称
     *
     * @var string
     * @optional
     */
    private $storeName = '';

    /**
     * 供应商ids
     *
     * @var string
     * @optional
     */
    private $supplierIds = '';

    /**
     * 所属供应商名称
     *
     * @var string
     * @optional
     */
    private $supplierNames = '';

    /**
     * 零售店地址
     *
     * @var string
     * @optional
     */
    private $storeAddr = '';

    /**
     * 状态
     *
     * @var int
     * @optional
     */
    private $status = '';

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
     * @return ProtoStore
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

        return $this;
    }
    /**
     * @return string
     */
    public function getStoreName()
    {
        return $this->storeName;
    }

    /**
     * @param string $storeName
     * @return ProtoStore
     */
    public function setStoreName($storeName = '')
    {
        $this->storeName = $storeName;

        return $this;
    }
    /**
     * @return string
     */
    public function getSupplierIds()
    {
        return $this->supplierIds;
    }

    /**
     * @param string $supplierIds
     * @return ProtoStore
     */
    public function setSupplierIds($supplierIds = '')
    {
        $this->supplierIds = $supplierIds;

        return $this;
    }
    /**
     * @return string
     */
    public function getSupplierNames()
    {
        return $this->supplierNames;
    }

    /**
     * @param string $supplierNames
     * @return ProtoStore
     */
    public function setSupplierNames($supplierNames = '')
    {
        $this->supplierNames = $supplierNames;

        return $this;
    }
    /**
     * @return string
     */
    public function getStoreAddr()
    {
        return $this->storeAddr;
    }

    /**
     * @param string $storeAddr
     * @return ProtoStore
     */
    public function setStoreAddr($storeAddr = '')
    {
        $this->storeAddr = $storeAddr;

        return $this;
    }
    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return ProtoStore
     */
    public function setStatus($status = '')
    {
        $this->status = $status;

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
     * @return ProtoStore
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
     * @return ProtoStore
     */
    public function setUpdateTime($updateTime = '')
    {
        $this->updateTime = $updateTime;

        return $this;
    }

}