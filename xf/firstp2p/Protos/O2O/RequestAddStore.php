<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 添加商品
 *
 * 由代码生成器生成, 不可人为修改
 * @author vincent
 */
class RequestAddStore extends AbstractRequestBase
{
    /**
     * 零售店名称
     *
     * @var string
     * @required
     */
    private $storeName;

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
    public function getStoreName()
    {
        return $this->storeName;
    }

    /**
     * @param string $storeName
     * @return RequestAddStore
     */
    public function setStoreName($storeName)
    {
        \Assert\Assertion::string($storeName);

        $this->storeName = $storeName;

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
     * @return RequestAddStore
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
     * @return RequestAddStore
     */
    public function setSupplierId($supplierId = 0)
    {
        $this->supplierId = $supplierId;

        return $this;
    }

}