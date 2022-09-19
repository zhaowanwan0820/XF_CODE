<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 添加供应商零售店关系
 *
 * 由代码生成器生成, 不可人为修改
 * @author <yanbingrong@ucfgroup.com>
 */
class RequestAddSupplierStoreRelation extends AbstractRequestBase
{
    /**
     * 零售店Id
     *
     * @var int
     * @required
     */
    private $storeId;

    /**
     * 供应商Id
     *
     * @var int
     * @required
     */
    private $supplierId;

    /**
     * 身份证号
     *
     * @var string
     * @required
     */
    private $idno;

    /**
     * 分类1
     *
     * @var string
     * @optional
     */
    private $channelLevel1 = '';

    /**
     * 分类2
     *
     * @var string
     * @optional
     */
    private $channelLevel2 = '';

    /**
     * 分类3
     *
     * @var string
     * @optional
     */
    private $channelLevel3 = '';

    /**
     * 分类4
     *
     * @var string
     * @optional
     */
    private $channelLevel4 = '';

    /**
     * 是否在职
     *
     * @var int
     * @optional
     */
    private $isActive = 0;

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param int $storeId
     * @return RequestAddSupplierStoreRelation
     */
    public function setStoreId($storeId)
    {
        \Assert\Assertion::integer($storeId);

        $this->storeId = $storeId;

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
     * @return RequestAddSupplierStoreRelation
     */
    public function setSupplierId($supplierId)
    {
        \Assert\Assertion::integer($supplierId);

        $this->supplierId = $supplierId;

        return $this;
    }
    /**
     * @return string
     */
    public function getIdno()
    {
        return $this->idno;
    }

    /**
     * @param string $idno
     * @return RequestAddSupplierStoreRelation
     */
    public function setIdno($idno)
    {
        \Assert\Assertion::string($idno);

        $this->idno = $idno;

        return $this;
    }
    /**
     * @return string
     */
    public function getChannelLevel1()
    {
        return $this->channelLevel1;
    }

    /**
     * @param string $channelLevel1
     * @return RequestAddSupplierStoreRelation
     */
    public function setChannelLevel1($channelLevel1 = '')
    {
        $this->channelLevel1 = $channelLevel1;

        return $this;
    }
    /**
     * @return string
     */
    public function getChannelLevel2()
    {
        return $this->channelLevel2;
    }

    /**
     * @param string $channelLevel2
     * @return RequestAddSupplierStoreRelation
     */
    public function setChannelLevel2($channelLevel2 = '')
    {
        $this->channelLevel2 = $channelLevel2;

        return $this;
    }
    /**
     * @return string
     */
    public function getChannelLevel3()
    {
        return $this->channelLevel3;
    }

    /**
     * @param string $channelLevel3
     * @return RequestAddSupplierStoreRelation
     */
    public function setChannelLevel3($channelLevel3 = '')
    {
        $this->channelLevel3 = $channelLevel3;

        return $this;
    }
    /**
     * @return string
     */
    public function getChannelLevel4()
    {
        return $this->channelLevel4;
    }

    /**
     * @param string $channelLevel4
     * @return RequestAddSupplierStoreRelation
     */
    public function setChannelLevel4($channelLevel4 = '')
    {
        $this->channelLevel4 = $channelLevel4;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @param int $isActive
     * @return RequestAddSupplierStoreRelation
     */
    public function setIsActive($isActive = 0)
    {
        $this->isActive = $isActive;

        return $this;
    }

}