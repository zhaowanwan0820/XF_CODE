<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 优惠码
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhaohui3
 */
class RequestCoupon extends ProtoBufferBase
{
    /**
     * 优惠码
     *
     * @var string
     * @required
     */
    private $coupon;

    /**
     * 订单id
     *
     * @var string
     * @required
     */
    private $dealid;

    /**
     * 用户唯一id
     *
     * @var int
     * @optional
     */
    private $userId = 0;

    /**
     * 金额
     *
     * @var string
     * @optional
     */
    private $money = '0.00';

    /**
     * 数量（黄金克重）
     *
     * @var string
     * @optional
     */
    private $amount = '0.000';

    /**
     * 单价
     *
     * @var string
     * @optional
     */
    private $price = '0.00';

    /**
     * jijin 为基金,duotou为多投,其他为默认p2p
     *
     * @var string
     * @optional
     */
    private $type = '';

    /**
     * 投资id
     *
     * @var int
     * @optional
     */
    private $dealLoadId = 0;

    /**
     * 赎回到账时间
     *
     * @var int
     * @optional
     */
    private $dealRepayTime = 0;

    /**
     * 起息日
     *
     * @var int
     * @optional
     */
    private $repayStartTime = 0;

    /**
     * 分站Id
     *
     * @var int
     * @optional
     */
    private $siteId = 1;

    /**
     * @return string
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * @param string $coupon
     * @return RequestCoupon
     */
    public function setCoupon($coupon)
    {
        \Assert\Assertion::string($coupon);

        $this->coupon = $coupon;

        return $this;
    }
    /**
     * @return string
     */
    public function getDealid()
    {
        return $this->dealid;
    }

    /**
     * @param string $dealid
     * @return RequestCoupon
     */
    public function setDealid($dealid)
    {
        \Assert\Assertion::string($dealid);

        $this->dealid = $dealid;

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
     * @return RequestCoupon
     */
    public function setUserId($userId = 0)
    {
        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * @param string $money
     * @return RequestCoupon
     */
    public function setMoney($money = '0.00')
    {
        $this->money = $money;

        return $this;
    }
    /**
     * @return string
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param string $amount
     * @return RequestCoupon
     */
    public function setAmount($amount = '0.000')
    {
        $this->amount = $amount;

        return $this;
    }
    /**
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param string $price
     * @return RequestCoupon
     */
    public function setPrice($price = '0.00')
    {
        $this->price = $price;

        return $this;
    }
    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return RequestCoupon
     */
    public function setType($type = '')
    {
        $this->type = $type;

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
     * @return RequestCoupon
     */
    public function setDealLoadId($dealLoadId = 0)
    {
        $this->dealLoadId = $dealLoadId;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealRepayTime()
    {
        return $this->dealRepayTime;
    }

    /**
     * @param int $dealRepayTime
     * @return RequestCoupon
     */
    public function setDealRepayTime($dealRepayTime = 0)
    {
        $this->dealRepayTime = $dealRepayTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getRepayStartTime()
    {
        return $this->repayStartTime;
    }

    /**
     * @param int $repayStartTime
     * @return RequestCoupon
     */
    public function setRepayStartTime($repayStartTime = 0)
    {
        $this->repayStartTime = $repayStartTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param int $siteId
     * @return RequestCoupon
     */
    public function setSiteId($siteId = 1)
    {
        $this->siteId = $siteId;

        return $this;
    }

}
