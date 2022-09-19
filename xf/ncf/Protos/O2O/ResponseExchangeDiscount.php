<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 兑换优惠券返回信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author yanbingrong
 */
class ResponseExchangeDiscount extends ProtoBufferBase
{
    /**
     * 券码ID
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * 券组ID
     *
     * @var int
     * @optional
     */
    private $discountGroupId = '';

    /**
     * 领取人ID
     *
     * @var int
     * @optional
     */
    private $ownerUserId = '';

    /**
     * 使用状态
     *
     * @var int
     * @optional
     */
    private $status = '';

    /**
     * 使用开始时间
     *
     * @var int
     * @optional
     */
    private $useStartTime = '';

    /**
     * 使用结束时间
     *
     * @var int
     * @optional
     */
    private $useEndTime = '';

    /**
     * 领取时间
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
     * 返利类型
     *
     * @var int
     * @optional
     */
    private $goodsType = 0;

    /**
     * 返利金额
     *
     * @var float
     * @optional
     */
    private $goodsPrice = 0;

    /**
     * 返利期限
     *
     * @var int
     * @optional
     */
    private $goodsLimit = 0;

    /**
     * 出资方id
     *
     * @var int
     * @optional
     */
    private $wxUserId = 0;

    /**
     * 券码名称
     *
     * @var string
     * @optional
     */
    private $productName = '';

    /**
     * 最大返利金额
     *
     * @var float
     * @optional
     */
    private $goodsMaxPrice = 0;

    /**
     * 加息券返利方式，1为随息发放，2为一次发放
     *
     * @var int
     * @optional
     */
    private $goodsGiveType = 0;

    /**
     * 投资券类型，1为返现券，2为加息券
     *
     * @var int
     * @optional
     */
    private $type = 1;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ResponseExchangeDiscount
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

        return $this;
    }
    /**
     * @return int
     */
    public function getDiscountGroupId()
    {
        return $this->discountGroupId;
    }

    /**
     * @param int $discountGroupId
     * @return ResponseExchangeDiscount
     */
    public function setDiscountGroupId($discountGroupId = '')
    {
        $this->discountGroupId = $discountGroupId;

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
     * @return ResponseExchangeDiscount
     */
    public function setOwnerUserId($ownerUserId = '')
    {
        $this->ownerUserId = $ownerUserId;

        return $this;
    }
    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return ResponseExchangeDiscount
     */
    public function setStatus($status = '')
    {
        $this->status = $status;

        return $this;
    }
    /**
     * @return int
     */
    public function getUseStartTime()
    {
        return $this->useStartTime;
    }

    /**
     * @param int $useStartTime
     * @return ResponseExchangeDiscount
     */
    public function setUseStartTime($useStartTime = '')
    {
        $this->useStartTime = $useStartTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getUseEndTime()
    {
        return $this->useEndTime;
    }

    /**
     * @param int $useEndTime
     * @return ResponseExchangeDiscount
     */
    public function setUseEndTime($useEndTime = '')
    {
        $this->useEndTime = $useEndTime;

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
     * @return ResponseExchangeDiscount
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
     * @return ResponseExchangeDiscount
     */
    public function setUpdateTime($updateTime = '')
    {
        $this->updateTime = $updateTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getGoodsType()
    {
        return $this->goodsType;
    }

    /**
     * @param int $goodsType
     * @return ResponseExchangeDiscount
     */
    public function setGoodsType($goodsType = 0)
    {
        $this->goodsType = $goodsType;

        return $this;
    }
    /**
     * @return float
     */
    public function getGoodsPrice()
    {
        return $this->goodsPrice;
    }

    /**
     * @param float $goodsPrice
     * @return ResponseExchangeDiscount
     */
    public function setGoodsPrice($goodsPrice = 0)
    {
        $this->goodsPrice = $goodsPrice;

        return $this;
    }
    /**
     * @return int
     */
    public function getGoodsLimit()
    {
        return $this->goodsLimit;
    }

    /**
     * @param int $goodsLimit
     * @return ResponseExchangeDiscount
     */
    public function setGoodsLimit($goodsLimit = 0)
    {
        $this->goodsLimit = $goodsLimit;

        return $this;
    }
    /**
     * @return int
     */
    public function getWxUserId()
    {
        return $this->wxUserId;
    }

    /**
     * @param int $wxUserId
     * @return ResponseExchangeDiscount
     */
    public function setWxUserId($wxUserId = 0)
    {
        $this->wxUserId = $wxUserId;

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
     * @return ResponseExchangeDiscount
     */
    public function setProductName($productName = '')
    {
        $this->productName = $productName;

        return $this;
    }
    /**
     * @return float
     */
    public function getGoodsMaxPrice()
    {
        return $this->goodsMaxPrice;
    }

    /**
     * @param float $goodsMaxPrice
     * @return ResponseExchangeDiscount
     */
    public function setGoodsMaxPrice($goodsMaxPrice = 0)
    {
        $this->goodsMaxPrice = $goodsMaxPrice;

        return $this;
    }
    /**
     * @return int
     */
    public function getGoodsGiveType()
    {
        return $this->goodsGiveType;
    }

    /**
     * @param int $goodsGiveType
     * @return ResponseExchangeDiscount
     */
    public function setGoodsGiveType($goodsGiveType = 0)
    {
        $this->goodsGiveType = $goodsGiveType;

        return $this;
    }
    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return ResponseExchangeDiscount
     */
    public function setType($type = 1)
    {
        $this->type = $type;

        return $this;
    }

}