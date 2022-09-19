<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 生成券码
 *
 * 由代码生成器生成, 不可人为修改
 * @author Yu Tao <yutao@ucfgroup.com>
 */
class RequestAcquireCoupon extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 券组ID
     *
     * @var int
     * @required
     */
    private $couponGroupId;

    /**
     * 后台补发备注
     *
     * @var string
     * @optional
     */
    private $remark = '';

    /**
     * 券码唯一token
     *
     * @var string
     * @optional
     */
    private $couponToken = '';

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
     * 触发时间
     *
     * @var int
     * @optional
     */
    private $triggerTime = 0;

    /**
     * 投资年化金额
     *
     * @var float
     * @optional
     */
    private $annualizedAmount = 0;

    /**
     * 投资金额
     *
     * @var float
     * @optional
     */
    private $bidAmount = 0;

    /**
     * 触发方式
     *
     * @var int
     * @optional
     */
    private $triggerMode = 0;

    /**
     * 交易id
     *
     * @var int
     * @optional
     */
    private $dealLoadId = 0;

    /**
     * 返利金额，覆盖券组的红包和投资券的金额配置
     *
     * @var float
     * @optional
     */
    private $rebateAmount = 0;

    /**
     * 返利期限，覆盖券组的红包或投资券的期限配置
     *
     * @var int
     * @optional
     */
    private $rebateLimit = 0;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestAcquireCoupon
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return int
     */
    public function getCouponGroupId()
    {
        return $this->couponGroupId;
    }

    /**
     * @param int $couponGroupId
     * @return RequestAcquireCoupon
     */
    public function setCouponGroupId($couponGroupId)
    {
        \Assert\Assertion::integer($couponGroupId);

        $this->couponGroupId = $couponGroupId;

        return $this;
    }
    /**
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * @param string $remark
     * @return RequestAcquireCoupon
     */
    public function setRemark($remark = '')
    {
        $this->remark = $remark;

        return $this;
    }
    /**
     * @return string
     */
    public function getCouponToken()
    {
        return $this->couponToken;
    }

    /**
     * @param string $couponToken
     * @return RequestAcquireCoupon
     */
    public function setCouponToken($couponToken = '')
    {
        $this->couponToken = $couponToken;

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
     * @return RequestAcquireCoupon
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
     * @return RequestAcquireCoupon
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
     * @return RequestAcquireCoupon
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
     * @return RequestAcquireCoupon
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
     * @return RequestAcquireCoupon
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
     * @return RequestAcquireCoupon
     */
    public function setReceiverExtra(array $receiverExtra = NULL)
    {
        $this->receiverExtra = $receiverExtra;

        return $this;
    }
    /**
     * @return int
     */
    public function getTriggerTime()
    {
        return $this->triggerTime;
    }

    /**
     * @param int $triggerTime
     * @return RequestAcquireCoupon
     */
    public function setTriggerTime($triggerTime = 0)
    {
        $this->triggerTime = $triggerTime;

        return $this;
    }
    /**
     * @return float
     */
    public function getAnnualizedAmount()
    {
        return $this->annualizedAmount;
    }

    /**
     * @param float $annualizedAmount
     * @return RequestAcquireCoupon
     */
    public function setAnnualizedAmount($annualizedAmount = 0)
    {
        $this->annualizedAmount = $annualizedAmount;

        return $this;
    }
    /**
     * @return float
     */
    public function getBidAmount()
    {
        return $this->bidAmount;
    }

    /**
     * @param float $bidAmount
     * @return RequestAcquireCoupon
     */
    public function setBidAmount($bidAmount = 0)
    {
        $this->bidAmount = $bidAmount;

        return $this;
    }
    /**
     * @return int
     */
    public function getTriggerMode()
    {
        return $this->triggerMode;
    }

    /**
     * @param int $triggerMode
     * @return RequestAcquireCoupon
     */
    public function setTriggerMode($triggerMode = 0)
    {
        $this->triggerMode = $triggerMode;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealLoadId()
    {
        return $this->dealLoadId;
    }

    /**
     * @param int $dealLoadId
     * @return RequestAcquireCoupon
     */
    public function setDealLoadId($dealLoadId = 0)
    {
        $this->dealLoadId = $dealLoadId;

        return $this;
    }
    /**
     * @return float
     */
    public function getRebateAmount()
    {
        return $this->rebateAmount;
    }

    /**
     * @param float $rebateAmount
     * @return RequestAcquireCoupon
     */
    public function setRebateAmount($rebateAmount = 0)
    {
        $this->rebateAmount = $rebateAmount;

        return $this;
    }
    /**
     * @return int
     */
    public function getRebateLimit()
    {
        return $this->rebateLimit;
    }

    /**
     * @param int $rebateLimit
     * @return RequestAcquireCoupon
     */
    public function setRebateLimit($rebateLimit = 0)
    {
        $this->rebateLimit = $rebateLimit;

        return $this;
    }

}