<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取待发货列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author Vincent <daiyuxin@ucfgroup.com>
 */
class RequestGetDeliveryList extends AbstractRequestBase
{
    /**
     * 分页类
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 开始日期
     *
     * @var string
     * @optional
     */
    private $startDay = '';

    /**
     * 结束日期
     *
     * @var string
     * @optional
     */
    private $endDay = '';

    /**
     * 供应商ID
     *
     * @var int
     * @optional
     */
    private $supplierId = '';

    /**
     * 邮件发送状态
     *
     * @var int
     * @optional
     */
    private $emailSendStatus = '';

    /**
     * 商品名称
     *
     * @var string
     * @optional
     */
    private $productName = '';

    /**
     * 商品ID
     *
     * @var int
     * @optional
     */
    private $productId = '';

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetDeliveryList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }
    /**
     * @return string
     */
    public function getStartDay()
    {
        return $this->startDay;
    }

    /**
     * @param string $startDay
     * @return RequestGetDeliveryList
     */
    public function setStartDay($startDay = '')
    {
        $this->startDay = $startDay;

        return $this;
    }
    /**
     * @return string
     */
    public function getEndDay()
    {
        return $this->endDay;
    }

    /**
     * @param string $endDay
     * @return RequestGetDeliveryList
     */
    public function setEndDay($endDay = '')
    {
        $this->endDay = $endDay;

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
     * @return RequestGetDeliveryList
     */
    public function setSupplierId($supplierId = '')
    {
        $this->supplierId = $supplierId;

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
     * @return RequestGetDeliveryList
     */
    public function setEmailSendStatus($emailSendStatus = '')
    {
        $this->emailSendStatus = $emailSendStatus;

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
     * @return RequestGetDeliveryList
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
     * @return RequestGetDeliveryList
     */
    public function setProductId($productId = '')
    {
        $this->productId = $productId;

        return $this;
    }

}