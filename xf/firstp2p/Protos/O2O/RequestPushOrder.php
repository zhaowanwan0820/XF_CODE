<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:向合作方推送订单信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class RequestPushOrder extends ProtoBufferBase
{
    /**
     * 优惠券兑换订单
     *
     * @var string
     * @required
     */
    private $orderId;

    /**
     * 网信给合作方分配的唯一key
     *
     * @var string
     * @required
     */
    private $clientId;

    /**
     * 合作方商品编号
     *
     * @var string
     * @required
     */
    private $productId;

    /**
     * 收货人姓名
     *
     * @var string
     * @required
     */
    private $receiverName;

    /**
     * 收货人电话
     *
     * @var string
     * @required
     */
    private $receiverPhone;

    /**
     * 详细收货地址
     *
     * @var string
     * @required
     */
    private $receiverAddress;

    /**
     * 收货邮编
     *
     * @var string
     * @optional
     */
    private $receiverCode = '';

    /**
     * 收货省市区
     *
     * @var string
     * @optional
     */
    private $receiverArea = '';

    /**
     * 订单其他信息
     *
     * @var string
     * @optional
     */
    private $receiverExtra = '';

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     * @return RequestPushOrder
     */
    public function setOrderId($orderId)
    {
        \Assert\Assertion::string($orderId);

        $this->orderId = $orderId;

        return $this;
    }
    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     * @return RequestPushOrder
     */
    public function setClientId($clientId)
    {
        \Assert\Assertion::string($clientId);

        $this->clientId = $clientId;

        return $this;
    }
    /**
     * @return string
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param string $productId
     * @return RequestPushOrder
     */
    public function setProductId($productId)
    {
        \Assert\Assertion::string($productId);

        $this->productId = $productId;

        return $this;
    }
    /**
     * @return string
     */
    public function getReceiverName()
    {
        return $this->receiverName;
    }

    /**
     * @param string $receiverName
     * @return RequestPushOrder
     */
    public function setReceiverName($receiverName)
    {
        \Assert\Assertion::string($receiverName);

        $this->receiverName = $receiverName;

        return $this;
    }
    /**
     * @return string
     */
    public function getReceiverPhone()
    {
        return $this->receiverPhone;
    }

    /**
     * @param string $receiverPhone
     * @return RequestPushOrder
     */
    public function setReceiverPhone($receiverPhone)
    {
        \Assert\Assertion::string($receiverPhone);

        $this->receiverPhone = $receiverPhone;

        return $this;
    }
    /**
     * @return string
     */
    public function getReceiverAddress()
    {
        return $this->receiverAddress;
    }

    /**
     * @param string $receiverAddress
     * @return RequestPushOrder
     */
    public function setReceiverAddress($receiverAddress)
    {
        \Assert\Assertion::string($receiverAddress);

        $this->receiverAddress = $receiverAddress;

        return $this;
    }
    /**
     * @return string
     */
    public function getReceiverCode()
    {
        return $this->receiverCode;
    }

    /**
     * @param string $receiverCode
     * @return RequestPushOrder
     */
    public function setReceiverCode($receiverCode = '')
    {
        $this->receiverCode = $receiverCode;

        return $this;
    }
    /**
     * @return string
     */
    public function getReceiverArea()
    {
        return $this->receiverArea;
    }

    /**
     * @param string $receiverArea
     * @return RequestPushOrder
     */
    public function setReceiverArea($receiverArea = '')
    {
        $this->receiverArea = $receiverArea;

        return $this;
    }
    /**
     * @return string
     */
    public function getReceiverExtra()
    {
        return $this->receiverExtra;
    }

    /**
     * @param string $receiverExtra
     * @return RequestPushOrder
     */
    public function setReceiverExtra($receiverExtra = '')
    {
        $this->receiverExtra = $receiverExtra;

        return $this;
    }

}