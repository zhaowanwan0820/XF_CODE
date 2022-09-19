<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:零售店tag配置Proto
 *
 * 由代码生成器生成, 不可人为修改
 * @author vincent
 */
class ProtoStoreTagConf extends ProtoBufferBase
{
    /**
     * 零售店tag配置ID
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * tag Id
     *
     * @var int
     * @optional
     */
    private $tagId = '';

    /**
     * 零售店地址
     *
     * @var string
     * @optional
     */
    private $storeAddr = '';

    /**
     * 所属供应商ids
     *
     * @var string
     * @optional
     */
    private $supplierIds = '';

    /**
     * tag配置描述
     *
     * @var string
     * @optional
     */
    private $desc = '';

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
     * @return ProtoStoreTagConf
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
    public function getTagId()
    {
        return $this->tagId;
    }

    /**
     * @param int $tagId
     * @return ProtoStoreTagConf
     */
    public function setTagId($tagId = '')
    {
        $this->tagId = $tagId;

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
     * @return ProtoStoreTagConf
     */
    public function setStoreAddr($storeAddr = '')
    {
        $this->storeAddr = $storeAddr;

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
     * @return ProtoStoreTagConf
     */
    public function setSupplierIds($supplierIds = '')
    {
        $this->supplierIds = $supplierIds;

        return $this;
    }
    /**
     * @return string
     */
    public function getDesc()
    {
        return $this->desc;
    }

    /**
     * @param string $desc
     * @return ProtoStoreTagConf
     */
    public function setDesc($desc = '')
    {
        $this->desc = $desc;

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
     * @return ProtoStoreTagConf
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
     * @return ProtoStoreTagConf
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
     * @return ProtoStoreTagConf
     */
    public function setUpdateTime($updateTime = '')
    {
        $this->updateTime = $updateTime;

        return $this;
    }

}