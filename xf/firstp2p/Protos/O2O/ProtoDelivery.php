<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:待发货信息Proto
 *
 * 由代码生成器生成, 不可人为修改
 * @author vincent
 */
class ProtoDelivery extends ProtoBufferBase
{
    /**
     * 待发货纪录ID
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * 交易日期
     *
     * @var string
     * @optional
     */
    private $day = '';

    /**
     * 供应商id
     *
     * @var int
     * @optional
     */
    private $supplierId = '';

    /**
     * 商品名称
     *
     * @var string
     * @optional
     */
    private $productName = '';

    /**
     * 商品id
     *
     * @var int
     * @optional
     */
    private $productId = '';

    /**
     * 发货数量
     *
     * @var int
     * @optional
     */
    private $productNum = '';

    /**
     * 邮件发送状态
     *
     * @var int
     * @optional
     */
    private $emailSendStatus = '';

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
     * @return ProtoDelivery
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
    public function getDay()
    {
        return $this->day;
    }

    /**
     * @param string $day
     * @return ProtoDelivery
     */
    public function setDay($day = '')
    {
        $this->day = $day;

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
     * @return ProtoDelivery
     */
    public function setSupplierId($supplierId = '')
    {
        $this->supplierId = $supplierId;

        return $this;
    }
    /**
     * @return string
     */
    public function getProductName()
    {
        return $this->productName;
    }

    /**
     * @param string $productName
     * @return ProtoDelivery
     */
    public function setProductName($productName = '')
    {
        $this->productName = $productName;

        return $this;
    }
    /**
     * @return int
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param int $productId
     * @return ProtoDelivery
     */
    public function setProductId($productId = '')
    {
        $this->productId = $productId;

        return $this;
    }
    /**
     * @return int
     */
    public function getProductNum()
    {
        return $this->productNum;
    }

    /**
     * @param int $productNum
     * @return ProtoDelivery
     */
    public function setProductNum($productNum = '')
    {
        $this->productNum = $productNum;

        return $this;
    }
    /**
     * @return int
     */
    public function getEmailSendStatus()
    {
        return $this->emailSendStatus;
    }

    /**
     * @param int $emailSendStatus
     * @return ProtoDelivery
     */
    public function setEmailSendStatus($emailSendStatus = '')
    {
        $this->emailSendStatus = $emailSendStatus;

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
     * @return ProtoDelivery
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
     * @return ProtoDelivery
     */
    public function setUpdateTime($updateTime = '')
    {
        $this->updateTime = $updateTime;

        return $this;
    }

}