<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 添加零售店tag配置
 *
 * 由代码生成器生成, 不可人为修改
 * @author vincent
 */
class RequestAddStoreTagConf extends AbstractRequestBase
{
    /**
     * 零售店名称
     *
     * @var string
     * @required
     */
    private $storeAddr;

    /**
     * 网信tagId
     *
     * @var int
     * @optional
     */
    private $tagId = 0;

    /**
     * 供应商IDs
     *
     * @var string
     * @optional
     */
    private $supplierIds = 0;

    /**
     * tag描述
     *
     * @var string
     * @optional
     */
    private $desc = 0;

    /**
     * @return string
     */
    public function getStoreAddr()
    {
        return $this->storeAddr;
    }

    /**
     * @param string $storeAddr
     * @return RequestAddStoreTagConf
     */
    public function setStoreAddr($storeAddr)
    {
        \Assert\Assertion::string($storeAddr);

        $this->storeAddr = $storeAddr;

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
     * @return RequestAddStoreTagConf
     */
    public function setTagId($tagId = 0)
    {
        $this->tagId = $tagId;

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
     * @return RequestAddStoreTagConf
     */
    public function setSupplierIds($supplierIds = 0)
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
     * @return RequestAddStoreTagConf
     */
    public function setDesc($desc = 0)
    {
        $this->desc = $desc;

        return $this;
    }

}