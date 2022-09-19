<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 用户发起兑换
 *
 * 由代码生成器生成, 不可人为修改
 * @author yanbingrong
 */
class RequestCouponExchange extends AbstractRequestBase
{
    /**
     * 优惠券id
     *
     * @var int
     * @required
     */
    private $couponId;

    /**
     * 商店用户id
     *
     * @var int
     * @optional
     */
    private $storeId = 0;

    /**
     * 用户id
     *
     * @var int
     * @required
     */
    private $ownerUserId;

    /**
     * 收货人姓名
     *
     * @var string
     * @optional
     */
    private $receiverName = '';

    /**
     * 收货人电话
     *
     * @var string
     * @optional
     */
    private $receiverPhone = '';

    /**
     * 邮政编码
     *
     * @var string
     * @optional
     */
    private $receiverCode = '';

    /**
     * 省市区
     *
     * @var string
     * @optional
     */
    private $receiverArea = '';

    /**
     * 详细地址
     *
     * @var string
     * @optional
     */
    private $receiverAddress = '';

    /**
     * 其他信息
     *
     * @var array
     * @optional
     */
    private $receiverExtra = NULL;

    /**
     * @return int
     */
    public function getCouponId()
    {
        return $this->couponId;
    }

    /**
     * @param int $couponId
     * @return RequestCouponExchange
     */
    public function setCouponId($couponId)
    {
        \Assert\Assertion::integer($couponId);

        $this->couponId = $couponId;

        return $this;
    }
    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param int $storeId
     * @return RequestCouponExchange
     */
    public function setStoreId($storeId = 0)
    {
        $this->storeId = $storeId;

        return $this;
    }
    /**
     * @return int
     */
    public function getOwnerUserId()
    {
        return $this->ownerUserId;
    }

    /**
     * @param int $ownerUserId
     * @return RequestCouponExchange
     */
    public function setOwnerUserId($ownerUserId)
    {
        \Assert\Assertion::integer($ownerUserId);

        $this->ownerUserId = $ownerUserId;

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
     * @return RequestCouponExchange
     */
    public function setReceiverName($receiverName = '')
    {
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
     * @return RequestCouponExchange
     */
    public function setReceiverPhone($receiverPhone = '')
    {
        $this->receiverPhone = $receiverPhone;

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
     * @return RequestCouponExchange
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
     * @return RequestCouponExchange
     */
    public function setReceiverArea($receiverArea = '')
    {
        $this->receiverArea = $receiverArea;

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
     * @return RequestCouponExchange
     */
    public function setReceiverAddress($receiverAddress = '')
    {
        $this->receiverAddress = $receiverAddress;

        return $this;
    }
    /**
     * @return array
     */
    public function getReceiverExtra()
    {
        return $this->receiverExtra;
    }

    /**
     * @param array $receiverExtra
     * @return RequestCouponExchange
     */
    public function setReceiverExtra(array $receiverExtra = NULL)
    {
        $this->receiverExtra = $receiverExtra;

        return $this;
    }

}