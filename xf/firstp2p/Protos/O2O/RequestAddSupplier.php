<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 添加商品
 *
 * 由代码生成器生成, 不可人为修改
 * @author vincent
 */
class RequestAddSupplier extends AbstractRequestBase
{
    /**
     * 供应商名称
     *
     * @var string
     * @required
     */
    private $supplierName;

    /**
     * 网信userId
     *
     * @var int
     * @optional
     */
    private $userId = 0;

    /**
     * 供应商ID
     *
     * @var int
     * @optional
     */
    private $supplierId = 0;

    /**
     * @return string
     */
    public function getSupplierName()
    {
        return $this->supplierName;
    }

    /**
     * @param string $supplierName
     * @return RequestAddSupplier
     */
    public function setSupplierName($supplierName)
    {
        \Assert\Assertion::string($supplierName);

        $this->supplierName = $supplierName;

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
     * @return RequestAddSupplier
     */
    public function setUserId($userId = 0)
    {
        $this->userId = $userId;

        return $this;
    }
    /**
     * @return int
     */
    public function getSupplierId()
    {
        return $this->supplierId;
    }

    /**
     * @param int $supplierId
     * @return RequestAddSupplier
     */
    public function setSupplierId($supplierId = 0)
    {
        $this->supplierId = $supplierId;

        return $this;
    }

}
