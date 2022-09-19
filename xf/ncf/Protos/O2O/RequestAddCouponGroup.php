<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:添加券组信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class RequestAddCouponGroup extends ProtoBufferBase
{
    /**
     * 商品ID
     *
     * @var int
     * @required
     */
    private $productId;

    /**
     * 券码数量
     *
     * @var int
     * @required
     */
    private $couponCount;

    /**
     * 商品价格
     *
     * @var string
     * @optional
     */
    private $goodPrice = 0;

    /**
     * 使用期限类型
     *
     * @var int
     * @required
     */
    private $useTimeType;

    /**
     * 使用期限
     *
     * @var int
     * @required
     */
    private $useDayLimit;

    /**
     * 券组生成来源
     *
     * @var int
     * @required
     */
    private $couponSource;

    /**
     * 触发方式
     *
     * @var int
     * @required
     */
    private $triggerMode;

    /**
     * 使用规则
     *
     * @var int
     * @required
     */
    private $useRules;

    /**
     * 券组状态
     *
     * @var int
     * @required
     */
    private $couponGroupStatus;

    /**
     * 券组描述
     *
     * @var string
     * @optional
     */
    private $couponDesc = '';

    /**
     * 投资触发下限
     *
     * @var float
     * @optional
     */
    private $downBidAmount = 0;

    /**
     * 投资触发下限
     *
     * @var float
     * @optional
     */
    private $upBidAmount = 0;

    /**
     * 供应商网信ID
     *
     * @var int
     * @optional
     */
    private $supplierUserId = 0;

    /**
     * 店铺ID
     *
     * @var int
     * @optional
     */
    private $storeId = 0;

    /**
     * 网信ID
     *
     * @var int
     * @optional
     */
    private $wxUserId = 0;

    /**
     * 渠道ID
     *
     * @var int
     * @optional
     */
    private $channelId = 0;

    /**
     * 网信补贴
     *
     * @var float
     * @optional
     */
    private $wxAllowanceSup = 0;

    /**
     * 网信补贴
     *
     * @var float
     * @optional
     */
    private $wxAllowanceStore = 0;

    /**
     * 网信补贴
     *
     * @var float
     * @optional
     */
    private $wxAllowanceChannel = 0;

    /**
     * 网信补贴
     *
     * @var float
     * @optional
     */
    private $wxAllowanceInviter = 0;

    /**
     * 供应商补贴
     *
     * @var float
     * @optional
     */
    private $supAllowanceStore = 0;

    /**
     * 渠道补贴
     *
     * @var float
     * @optional
     */
    private $channelAllowanceStore = 0;

    /**
     * 网信补贴
     *
     * @var int
     * @optional
     */
    private $wxAllowanceSupType = 0;

    /**
     * 网信补贴
     *
     * @var int
     * @optional
     */
    private $wxAllowanceStoreType = 0;

    /**
     * 网信补贴
     *
     * @var int
     * @optional
     */
    private $wxAllowanceChannelType = 0;

    /**
     * 网信补贴
     *
     * @var int
     * @optional
     */
    private $wxAllowanceInviterType = 0;

    /**
     * 供应商补贴
     *
     * @var int
     * @optional
     */
    private $supAllowanceStoreType = 0;

    /**
     * 渠道补贴
     *
     * @var int
     * @optional
     */
    private $channelAllowanceStoreType = 0;

    /**
     * 网信补贴
     *
     * @var int
     * @optional
     */
    private $wxAllowanceSupLimit = 0;

    /**
     * 网信补贴
     *
     * @var int
     * @optional
     */
    private $wxAllowanceStoreLimit = 0;

    /**
     * 网信补贴
     *
     * @var int
     * @optional
     */
    private $wxAllowanceChannelLimit = 0;

    /**
     * 网信补贴
     *
     * @var int
     * @optional
     */
    private $wxAllowanceInviterLimit = 0;

    /**
     * 供应商补贴
     *
     * @var int
     * @optional
     */
    private $supAllowanceStoreLimit = 0;

    /**
     * 渠道补贴
     *
     * @var int
     * @optional
     */
    private $channelAllowanceStoreLimit = 0;

    /**
     * 领取期限
     *
     * @var int
     * @optional
     */
    private $acquireDayLimit = 0;

    /**
     * 使用开始时间
     *
     * @var int
     * @optional
     */
    private $useStartTime = 0;

    /**
     * 使用结束时间
     *
     * @var int
     * @optional
     */
    private $useEndTime = 0;

    /**
     * 触发投资类型
     *
     * @var int
     * @optional
     */
    private $triggerType = 0;

    /**
     * 触发投资年化金额下限
     *
     * @var float
     * @optional
     */
    private $downAnnualizedAmount = 0;

    /**
     * 触发投资年化金额上限
     *
     * @var float
     * @optional
     */
    private $upAnnualizedAmount = 0;

    /**
     * 投资人tag中含有
     *
     * @var string
     * @optional
     */
    private $userTag = '';

    /**
     * 邀请人tag中含有
     *
     * @var string
     * @optional
     */
    private $inviterTag = '';

    /**
     * 前端是否显示券码
     *
     * @var int
     * @optional
     */
    private $isShowCouponNumber = 1;

    /**
     * 红包补贴类型
     *
     * @var int
     * @optional
     */
    private $luckyMoneyAllowanceType = 0;

    /**
     * 红包补贴期限
     *
     * @var int
     * @optional
     */
    private $luckyMoneyAllowanceLimit = 0;

    /**
     * 红包补贴金额
     *
     * @var float
     * @optional
     */
    private $luckyMoneyAllowanceMoney = 0;

    /**
     * 红包补贴个数
     *
     * @var int
     * @optional
     */
    private $luckyMoneyAllowanceCount = 0;

    /**
     * 网信补贴供应商红包数量
     *
     * @var int
     * @optional
     */
    private $wxAllowanceSupCount = 0;

    /**
     * 网信补贴商家红包数量
     *
     * @var int
     * @optional
     */
    private $wxAllowanceStoreCount = 0;

    /**
     * 网信补贴渠道红包数量
     *
     * @var int
     * @optional
     */
    private $wxAllowanceChannelCount = 0;

    /**
     * 供应商补贴商家红包数量
     *
     * @var int
     * @optional
     */
    private $supAllowanceStoreCount = 0;

    /**
     * 渠道补贴商家红包数量
     *
     * @var int
     * @optional
     */
    private $channelAllowanceStoreCount = 0;

    /**
     * 网信补贴邀请人红包数量
     *
     * @var int
     * @optional
     */
    private $wxAllowanceInviterCount = 0;

    /**
     * 第三方商家标识
     *
     * @var string
     * @required
     */
    private $couponProvider;

    /**
     * 券码兑换后显示内容
     *
     * @var string
     * @optional
     */
    private $couponExchangedDesc = '';

    /**
     * 标tag中含有
     *
     * @var string
     * @optional
     */
    private $dealTag = '';

    /**
     * 券组下线时间
     *
     * @var int
     * @optional
     */
    private $unavailableTime = 0;

    /**
     * 券组上线时间
     *
     * @var int
     * @optional
     */
    private $availableTime = 0;

    /**
     * 领券后网信补贴领券人类型
     *
     * @var int
     * @optional
     */
    private $acquiredWxOwnerAllowanceType = 0;

    /**
     * 领券后网信补贴领券人红包期限
     *
     * @var int
     * @optional
     */
    private $acquiredWxOwnerAllowanceLimit = 0;

    /**
     * 领券后网信补贴领券人红包金额
     *
     * @var float
     * @optional
     */
    private $acquiredWxOwnerAllowanceMoney = 0;

    /**
     * 领券后网信补贴领券人红包个数
     *
     * @var int
     * @optional
     */
    private $acquiredWxOwnerAllowanceCount = 0;

    /**
     * 邀请人tag中不含有
     *
     * @var string
     * @optional
     */
    private $nonInviterTag = '';

    /**
     * 投资人tag中不含有
     *
     * @var string
     * @optional
     */
    private $nonUserTag = '';

    /**
     * 标tag中不含有
     *
     * @var string
     * @optional
     */
    private $nonDealTag = '';

    /**
     * 触发规则
     *
     * @var array
     * @optional
     */
    private $triggerInfo = NULL;

    /**
     * 邀请人tag中不含有（新tag）
     *
     * @var string
     * @optional
     */
    private $newNonInviterTag = '';

    /**
     * 投资人tag中不含有（新tag）
     *
     * @var string
     * @optional
     */
    private $newNonUserTag = '';

    /**
     * 标tag中不含有（新tag）
     *
     * @var string
     * @optional
     */
    private $newNonDealTag = '';

    /**
     * 用户tag（新tag）
     *
     * @var string
     * @optional
     */
    private $newUserTag = '';

    /**
     * 邀请人tag（新tag）
     *
     * @var string
     * @optional
     */
    private $newInviterTag = '';

    /**
     * 投标的tag（新tag）
     *
     * @var string
     * @optional
     */
    private $newDealTag = '';

    /**
     * PC上礼券详情
     *
     * @var string
     * @optional
     */
    private $couponPcDesc = '';

    /**
     * PC上券码兑换后显示内容
     *
     * @var string
     * @optional
     */
    private $couponExchangedPcDesc = '';

    /**
     * 领券后网信补贴邀请人类型
     *
     * @var int
     * @optional
     */
    private $acquiredWxInviterAllowanceType = 0;

    /**
     * 领券后网信补贴邀请人红包期限
     *
     * @var int
     * @optional
     */
    private $acquiredWxInviterAllowanceLimit = 0;

    /**
     * 领券后网信补贴邀请人红包金额
     *
     * @var float
     * @optional
     */
    private $acquiredWxInviterAllowanceMoney = 0;

    /**
     * 领券后网信补贴邀请人红包个数
     *
     * @var int
     * @optional
     */
    private $acquiredWxInviterAllowanceCount = 0;

    /**
     * @return int
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param int $productId
     * @return RequestAddCouponGroup
     */
    public function setProductId($productId)
    {
        \Assert\Assertion::integer($productId);

        $this->productId = $productId;

        return $this;
    }
    /**
     * @return int
     */
    public function getCouponCount()
    {
        return $this->couponCount;
    }

    /**
     * @param int $couponCount
     * @return RequestAddCouponGroup
     */
    public function setCouponCount($couponCount)
    {
        \Assert\Assertion::integer($couponCount);

        $this->couponCount = $couponCount;

        return $this;
    }
    /**
     * @return string
     */
    public function getGoodPrice()
    {
        return $this->goodPrice;
    }

    /**
     * @param string $goodPrice
     * @return RequestAddCouponGroup
     */
    public function setGoodPrice($goodPrice = 0)
    {
        $this->goodPrice = $goodPrice;

        return $this;
    }
    /**
     * @return int
     */
    public function getUseTimeType()
    {
        return $this->useTimeType;
    }

    /**
     * @param int $useTimeType
     * @return RequestAddCouponGroup
     */
    public function setUseTimeType($useTimeType)
    {
        \Assert\Assertion::integer($useTimeType);

        $this->useTimeType = $useTimeType;

        return $this;
    }
    /**
     * @return int
     */
    public function getUseDayLimit()
    {
        return $this->useDayLimit;
    }

    /**
     * @param int $useDayLimit
     * @return RequestAddCouponGroup
     */
    public function setUseDayLimit($useDayLimit)
    {
        \Assert\Assertion::integer($useDayLimit);

        $this->useDayLimit = $useDayLimit;

        return $this;
    }
    /**
     * @return int
     */
    public function getCouponSource()
    {
        return $this->couponSource;
    }

    /**
     * @param int $couponSource
     * @return RequestAddCouponGroup
     */
    public function setCouponSource($couponSource)
    {
        \Assert\Assertion::integer($couponSource);

        $this->couponSource = $couponSource;

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
     * @return RequestAddCouponGroup
     */
    public function setTriggerMode($triggerMode)
    {
        \Assert\Assertion::integer($triggerMode);

        $this->triggerMode = $triggerMode;

        return $this;
    }
    /**
     * @return int
     */
    public function getUseRules()
    {
        return $this->useRules;
    }

    /**
     * @param int $useRules
     * @return RequestAddCouponGroup
     */
    public function setUseRules($useRules)
    {
        \Assert\Assertion::integer($useRules);

        $this->useRules = $useRules;

        return $this;
    }
    /**
     * @return int
     */
    public function getCouponGroupStatus()
    {
        return $this->couponGroupStatus;
    }

    /**
     * @param int $couponGroupStatus
     * @return RequestAddCouponGroup
     */
    public function setCouponGroupStatus($couponGroupStatus)
    {
        \Assert\Assertion::integer($couponGroupStatus);

        $this->couponGroupStatus = $couponGroupStatus;

        return $this;
    }
    /**
     * @return string
     */
    public function getCouponDesc()
    {
        return $this->couponDesc;
    }

    /**
     * @param string $couponDesc
     * @return RequestAddCouponGroup
     */
    public function setCouponDesc($couponDesc = '')
    {
        $this->couponDesc = $couponDesc;

        return $this;
    }
    /**
     * @return float
     */
    public function getDownBidAmount()
    {
        return $this->downBidAmount;
    }

    /**
     * @param float $downBidAmount
     * @return RequestAddCouponGroup
     */
    public function setDownBidAmount($downBidAmount = 0)
    {
        $this->downBidAmount = $downBidAmount;

        return $this;
    }
    /**
     * @return float
     */
    public function getUpBidAmount()
    {
        return $this->upBidAmount;
    }

    /**
     * @param float $upBidAmount
     * @return RequestAddCouponGroup
     */
    public function setUpBidAmount($upBidAmount = 0)
    {
        $this->upBidAmount = $upBidAmount;

        return $this;
    }
    /**
     * @return int
     */
    public function getSupplierUserId()
    {
        return $this->supplierUserId;
    }

    /**
     * @param int $supplierUserId
     * @return RequestAddCouponGroup
     */
    public function setSupplierUserId($supplierUserId = 0)
    {
        $this->supplierUserId = $supplierUserId;

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
     * @return RequestAddCouponGroup
     */
    public function setStoreId($storeId = 0)
    {
        $this->storeId = $storeId;

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
     * @return RequestAddCouponGroup
     */
    public function setWxUserId($wxUserId = 0)
    {
        $this->wxUserId = $wxUserId;

        return $this;
    }
    /**
     * @return int
     */
    public function getChannelId()
    {
        return $this->channelId;
    }

    /**
     * @param int $channelId
     * @return RequestAddCouponGroup
     */
    public function setChannelId($channelId = 0)
    {
        $this->channelId = $channelId;

        return $this;
    }
    /**
     * @return float
     */
    public function getWxAllowanceSup()
    {
        return $this->wxAllowanceSup;
    }

    /**
     * @param float $wxAllowanceSup
     * @return RequestAddCouponGroup
     */
    public function setWxAllowanceSup($wxAllowanceSup = 0)
    {
        $this->wxAllowanceSup = $wxAllowanceSup;

        return $this;
    }
    /**
     * @return float
     */
    public function getWxAllowanceStore()
    {
        return $this->wxAllowanceStore;
    }

    /**
     * @param float $wxAllowanceStore
     * @return RequestAddCouponGroup
     */
    public function setWxAllowanceStore($wxAllowanceStore = 0)
    {
        $this->wxAllowanceStore = $wxAllowanceStore;

        return $this;
    }
    /**
     * @return float
     */
    public function getWxAllowanceChannel()
    {
        return $this->wxAllowanceChannel;
    }

    /**
     * @param float $wxAllowanceChannel
     * @return RequestAddCouponGroup
     */
    public function setWxAllowanceChannel($wxAllowanceChannel = 0)
    {
        $this->wxAllowanceChannel = $wxAllowanceChannel;

        return $this;
    }
    /**
     * @return float
     */
    public function getWxAllowanceInviter()
    {
        return $this->wxAllowanceInviter;
    }

    /**
     * @param float $wxAllowanceInviter
     * @return RequestAddCouponGroup
     */
    public function setWxAllowanceInviter($wxAllowanceInviter = 0)
    {
        $this->wxAllowanceInviter = $wxAllowanceInviter;

        return $this;
    }
    /**
     * @return float
     */
    public function getSupAllowanceStore()
    {
        return $this->supAllowanceStore;
    }

    /**
     * @param float $supAllowanceStore
     * @return RequestAddCouponGroup
     */
    public function setSupAllowanceStore($supAllowanceStore = 0)
    {
        $this->supAllowanceStore = $supAllowanceStore;

        return $this;
    }
    /**
     * @return float
     */
    public function getChannelAllowanceStore()
    {
        return $this->channelAllowanceStore;
    }

    /**
     * @param float $channelAllowanceStore
     * @return RequestAddCouponGroup
     */
    public function setChannelAllowanceStore($channelAllowanceStore = 0)
    {
        $this->channelAllowanceStore = $channelAllowanceStore;

        return $this;
    }
    /**
     * @return int
     */
    public function getWxAllowanceSupType()
    {
        return $this->wxAllowanceSupType;
    }

    /**
     * @param int $wxAllowanceSupType
     * @return RequestAddCouponGroup
     */
    public function setWxAllowanceSupType($wxAllowanceSupType = 0)
    {
        $this->wxAllowanceSupType = $wxAllowanceSupType;

        return $this;
    }
    /**
     * @return int
     */
    public function getWxAllowanceStoreType()
    {
        return $this->wxAllowanceStoreType;
    }

    /**
     * @param int $wxAllowanceStoreType
     * @return RequestAddCouponGroup
     */
    public function setWxAllowanceStoreType($wxAllowanceStoreType = 0)
    {
        $this->wxAllowanceStoreType = $wxAllowanceStoreType;

        return $this;
    }
    /**
     * @return int
     */
    public function getWxAllowanceChannelType()
    {
        return $this->wxAllowanceChannelType;
    }

    /**
     * @param int $wxAllowanceChannelType
     * @return RequestAddCouponGroup
     */
    public function setWxAllowanceChannelType($wxAllowanceChannelType = 0)
    {
        $this->wxAllowanceChannelType = $wxAllowanceChannelType;

        return $this;
    }
    /**
     * @return int
     */
    public function getWxAllowanceInviterType()
    {
        return $this->wxAllowanceInviterType;
    }

    /**
     * @param int $wxAllowanceInviterType
     * @return RequestAddCouponGroup
     */
    public function setWxAllowanceInviterType($wxAllowanceInviterType = 0)
    {
        $this->wxAllowanceInviterType = $wxAllowanceInviterType;

        return $this;
    }
    /**
     * @return int
     */
    public function getSupAllowanceStoreType()
    {
        return $this->supAllowanceStoreType;
    }

    /**
     * @param int $supAllowanceStoreType
     * @return RequestAddCouponGroup
     */
    public function setSupAllowanceStoreType($supAllowanceStoreType = 0)
    {
        $this->supAllowanceStoreType = $supAllowanceStoreType;

        return $this;
    }
    /**
     * @return int
     */
    public function getChannelAllowanceStoreType()
    {
        return $this->channelAllowanceStoreType;
    }

    /**
     * @param int $channelAllowanceStoreType
     * @return RequestAddCouponGroup
     */
    public function setChannelAllowanceStoreType($channelAllowanceStoreType = 0)
    {
        $this->channelAllowanceStoreType = $channelAllowanceStoreType;

        return $this;
    }
    /**
     * @return int
     */
    public function getWxAllowanceSupLimit()
    {
        return $this->wxAllowanceSupLimit;
    }

    /**
     * @param int $wxAllowanceSupLimit
     * @return RequestAddCouponGroup
     */
    public function setWxAllowanceSupLimit($wxAllowanceSupLimit = 0)
    {
        $this->wxAllowanceSupLimit = $wxAllowanceSupLimit;

        return $this;
    }
    /**
     * @return int
     */
    public function getWxAllowanceStoreLimit()
    {
        return $this->wxAllowanceStoreLimit;
    }

    /**
     * @param int $wxAllowanceStoreLimit
     * @return RequestAddCouponGroup
     */
    public function setWxAllowanceStoreLimit($wxAllowanceStoreLimit = 0)
    {
        $this->wxAllowanceStoreLimit = $wxAllowanceStoreLimit;

        return $this;
    }
    /**
     * @return int
     */
    public function getWxAllowanceChannelLimit()
    {
        return $this->wxAllowanceChannelLimit;
    }

    /**
     * @param int $wxAllowanceChannelLimit
     * @return RequestAddCouponGroup
     */
    public function setWxAllowanceChannelLimit($wxAllowanceChannelLimit = 0)
    {
        $this->wxAllowanceChannelLimit = $wxAllowanceChannelLimit;

        return $this;
    }
    /**
     * @return int
     */
    public function getWxAllowanceInviterLimit()
    {
        return $this->wxAllowanceInviterLimit;
    }

    /**
     * @param int $wxAllowanceInviterLimit
     * @return RequestAddCouponGroup
     */
    public function setWxAllowanceInviterLimit($wxAllowanceInviterLimit = 0)
    {
        $this->wxAllowanceInviterLimit = $wxAllowanceInviterLimit;

        return $this;
    }
    /**
     * @return int
     */
    public function getSupAllowanceStoreLimit()
    {
        return $this->supAllowanceStoreLimit;
    }

    /**
     * @param int $supAllowanceStoreLimit
     * @return RequestAddCouponGroup
     */
    public function setSupAllowanceStoreLimit($supAllowanceStoreLimit = 0)
    {
        $this->supAllowanceStoreLimit = $supAllowanceStoreLimit;

        return $this;
    }
    /**
     * @return int
     */
    public function getChannelAllowanceStoreLimit()
    {
        return $this->channelAllowanceStoreLimit;
    }

    /**
     * @param int $channelAllowanceStoreLimit
     * @return RequestAddCouponGroup
     */
    public function setChannelAllowanceStoreLimit($channelAllowanceStoreLimit = 0)
    {
        $this->channelAllowanceStoreLimit = $channelAllowanceStoreLimit;

        return $this;
    }
    /**
     * @return int
     */
    public function getAcquireDayLimit()
    {
        return $this->acquireDayLimit;
    }

    /**
     * @param int $acquireDayLimit
     * @return RequestAddCouponGroup
     */
    public function setAcquireDayLimit($acquireDayLimit = 0)
    {
        $this->acquireDayLimit = $acquireDayLimit;

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
     * @return RequestAddCouponGroup
     */
    public function setUseStartTime($useStartTime = 0)
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
     * @return RequestAddCouponGroup
     */
    public function setUseEndTime($useEndTime = 0)
    {
        $this->useEndTime = $useEndTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getTriggerType()
    {
        return $this->triggerType;
    }

    /**
     * @param int $triggerType
     * @return RequestAddCouponGroup
     */
    public function setTriggerType($triggerType = 0)
    {
        $this->triggerType = $triggerType;

        return $this;
    }
    /**
     * @return float
     */
    public function getDownAnnualizedAmount()
    {
        return $this->downAnnualizedAmount;
    }

    /**
     * @param float $downAnnualizedAmount
     * @return RequestAddCouponGroup
     */
    public function setDownAnnualizedAmount($downAnnualizedAmount = 0)
    {
        $this->downAnnualizedAmount = $downAnnualizedAmount;

        return $this;
    }
    /**
     * @return float
     */
    public function getUpAnnualizedAmount()
    {
        return $this->upAnnualizedAmount;
    }

    /**
     * @param float $upAnnualizedAmount
     * @return RequestAddCouponGroup
     */
    public function setUpAnnualizedAmount($upAnnualizedAmount = 0)
    {
        $this->upAnnualizedAmount = $upAnnualizedAmount;

        return $this;
    }
    /**
     * @return string
     */
    public function getUserTag()
    {
        return $this->userTag;
    }

    /**
     * @param string $userTag
     * @return RequestAddCouponGroup
     */
    public function setUserTag($userTag = '')
    {
        $this->userTag = $userTag;

        return $this;
    }
    /**
     * @return string
     */
    public function getInviterTag()
    {
        return $this->inviterTag;
    }

    /**
     * @param string $inviterTag
     * @return RequestAddCouponGroup
     */
    public function setInviterTag($inviterTag = '')
    {
        $this->inviterTag = $inviterTag;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsShowCouponNumber()
    {
        return $this->isShowCouponNumber;
    }

    /**
     * @param int $isShowCouponNumber
     * @return RequestAddCouponGroup
     */
    public function setIsShowCouponNumber($isShowCouponNumber = 1)
    {
        $this->isShowCouponNumber = $isShowCouponNumber;

        return $this;
    }
    /**
     * @return int
     */
    public function getLuckyMoneyAllowanceType()
    {
        return $this->luckyMoneyAllowanceType;
    }

    /**
     * @param int $luckyMoneyAllowanceType
     * @return RequestAddCouponGroup
     */
    public function setLuckyMoneyAllowanceType($luckyMoneyAllowanceType = 0)
    {
        $this->luckyMoneyAllowanceType = $luckyMoneyAllowanceType;

        return $this;
    }
    /**
     * @return int
     */
    public function getLuckyMoneyAllowanceLimit()
    {
        return $this->luckyMoneyAllowanceLimit;
    }

    /**
     * @param int $luckyMoneyAllowanceLimit
     * @return RequestAddCouponGroup
     */
    public function setLuckyMoneyAllowanceLimit($luckyMoneyAllowanceLimit = 0)
    {
        $this->luckyMoneyAllowanceLimit = $luckyMoneyAllowanceLimit;

        return $this;
    }
    /**
     * @return float
     */
    public function getLuckyMoneyAllowanceMoney()
    {
        return $this->luckyMoneyAllowanceMoney;
    }

    /**
     * @param float $luckyMoneyAllowanceMoney
     * @return RequestAddCouponGroup
     */
    public function setLuckyMoneyAllowanceMoney($luckyMoneyAllowanceMoney = 0)
    {
        $this->luckyMoneyAllowanceMoney = $luckyMoneyAllowanceMoney;

        return $this;
    }
    /**
     * @return int
     */
    public function getLuckyMoneyAllowanceCount()
    {
        return $this->luckyMoneyAllowanceCount;
    }

    /**
     * @param int $luckyMoneyAllowanceCount
     * @return RequestAddCouponGroup
     */
    public function setLuckyMoneyAllowanceCount($luckyMoneyAllowanceCount = 0)
    {
        $this->luckyMoneyAllowanceCount = $luckyMoneyAllowanceCount;

        return $this;
    }
    /**
     * @return int
     */
    public function getWxAllowanceSupCount()
    {
        return $this->wxAllowanceSupCount;
    }

    /**
     * @param int $wxAllowanceSupCount
     * @return RequestAddCouponGroup
     */
    public function setWxAllowanceSupCount($wxAllowanceSupCount = 0)
    {
        $this->wxAllowanceSupCount = $wxAllowanceSupCount;

        return $this;
    }
    /**
     * @return int
     */
    public function getWxAllowanceStoreCount()
    {
        return $this->wxAllowanceStoreCount;
    }

    /**
     * @param int $wxAllowanceStoreCount
     * @return RequestAddCouponGroup
     */
    public function setWxAllowanceStoreCount($wxAllowanceStoreCount = 0)
    {
        $this->wxAllowanceStoreCount = $wxAllowanceStoreCount;

        return $this;
    }
    /**
     * @return int
     */
    public function getWxAllowanceChannelCount()
    {
        return $this->wxAllowanceChannelCount;
    }

    /**
     * @param int $wxAllowanceChannelCount
     * @return RequestAddCouponGroup
     */
    public function setWxAllowanceChannelCount($wxAllowanceChannelCount = 0)
    {
        $this->wxAllowanceChannelCount = $wxAllowanceChannelCount;

        return $this;
    }
    /**
     * @return int
     */
    public function getSupAllowanceStoreCount()
    {
        return $this->supAllowanceStoreCount;
    }

    /**
     * @param int $supAllowanceStoreCount
     * @return RequestAddCouponGroup
     */
    public function setSupAllowanceStoreCount($supAllowanceStoreCount = 0)
    {
        $this->supAllowanceStoreCount = $supAllowanceStoreCount;

        return $this;
    }
    /**
     * @return int
     */
    public function getChannelAllowanceStoreCount()
    {
        return $this->channelAllowanceStoreCount;
    }

    /**
     * @param int $channelAllowanceStoreCount
     * @return RequestAddCouponGroup
     */
    public function setChannelAllowanceStoreCount($channelAllowanceStoreCount = 0)
    {
        $this->channelAllowanceStoreCount = $channelAllowanceStoreCount;

        return $this;
    }
    /**
     * @return int
     */
    public function getWxAllowanceInviterCount()
    {
        return $this->wxAllowanceInviterCount;
    }

    /**
     * @param int $wxAllowanceInviterCount
     * @return RequestAddCouponGroup
     */
    public function setWxAllowanceInviterCount($wxAllowanceInviterCount = 0)
    {
        $this->wxAllowanceInviterCount = $wxAllowanceInviterCount;

        return $this;
    }
    /**
     * @return string
     */
    public function getCouponProvider()
    {
        return $this->couponProvider;
    }

    /**
     * @param string $couponProvider
     * @return RequestAddCouponGroup
     */
    public function setCouponProvider($couponProvider)
    {
        \Assert\Assertion::string($couponProvider);

        $this->couponProvider = $couponProvider;

        return $this;
    }
    /**
     * @return string
     */
    public function getCouponExchangedDesc()
    {
        return $this->couponExchangedDesc;
    }

    /**
     * @param string $couponExchangedDesc
     * @return RequestAddCouponGroup
     */
    public function setCouponExchangedDesc($couponExchangedDesc = '')
    {
        $this->couponExchangedDesc = $couponExchangedDesc;

        return $this;
    }
    /**
     * @return string
     */
    public function getDealTag()
    {
        return $this->dealTag;
    }

    /**
     * @param string $dealTag
     * @return RequestAddCouponGroup
     */
    public function setDealTag($dealTag = '')
    {
        $this->dealTag = $dealTag;

        return $this;
    }
    /**
     * @return int
     */
    public function getUnavailableTime()
    {
        return $this->unavailableTime;
    }

    /**
     * @param int $unavailableTime
     * @return RequestAddCouponGroup
     */
    public function setUnavailableTime($unavailableTime = 0)
    {
        $this->unavailableTime = $unavailableTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getAvailableTime()
    {
        return $this->availableTime;
    }

    /**
     * @param int $availableTime
     * @return RequestAddCouponGroup
     */
    public function setAvailableTime($availableTime = 0)
    {
        $this->availableTime = $availableTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getAcquiredWxOwnerAllowanceType()
    {
        return $this->acquiredWxOwnerAllowanceType;
    }

    /**
     * @param int $acquiredWxOwnerAllowanceType
     * @return RequestAddCouponGroup
     */
    public function setAcquiredWxOwnerAllowanceType($acquiredWxOwnerAllowanceType = 0)
    {
        $this->acquiredWxOwnerAllowanceType = $acquiredWxOwnerAllowanceType;

        return $this;
    }
    /**
     * @return int
     */
    public function getAcquiredWxOwnerAllowanceLimit()
    {
        return $this->acquiredWxOwnerAllowanceLimit;
    }

    /**
     * @param int $acquiredWxOwnerAllowanceLimit
     * @return RequestAddCouponGroup
     */
    public function setAcquiredWxOwnerAllowanceLimit($acquiredWxOwnerAllowanceLimit = 0)
    {
        $this->acquiredWxOwnerAllowanceLimit = $acquiredWxOwnerAllowanceLimit;

        return $this;
    }
    /**
     * @return float
     */
    public function getAcquiredWxOwnerAllowanceMoney()
    {
        return $this->acquiredWxOwnerAllowanceMoney;
    }

    /**
     * @param float $acquiredWxOwnerAllowanceMoney
     * @return RequestAddCouponGroup
     */
    public function setAcquiredWxOwnerAllowanceMoney($acquiredWxOwnerAllowanceMoney = 0)
    {
        $this->acquiredWxOwnerAllowanceMoney = $acquiredWxOwnerAllowanceMoney;

        return $this;
    }
    /**
     * @return int
     */
    public function getAcquiredWxOwnerAllowanceCount()
    {
        return $this->acquiredWxOwnerAllowanceCount;
    }

    /**
     * @param int $acquiredWxOwnerAllowanceCount
     * @return RequestAddCouponGroup
     */
    public function setAcquiredWxOwnerAllowanceCount($acquiredWxOwnerAllowanceCount = 0)
    {
        $this->acquiredWxOwnerAllowanceCount = $acquiredWxOwnerAllowanceCount;

        return $this;
    }
    /**
     * @return string
     */
    public function getNonInviterTag()
    {
        return $this->nonInviterTag;
    }

    /**
     * @param string $nonInviterTag
     * @return RequestAddCouponGroup
     */
    public function setNonInviterTag($nonInviterTag = '')
    {
        $this->nonInviterTag = $nonInviterTag;

        return $this;
    }
    /**
     * @return string
     */
    public function getNonUserTag()
    {
        return $this->nonUserTag;
    }

    /**
     * @param string $nonUserTag
     * @return RequestAddCouponGroup
     */
    public function setNonUserTag($nonUserTag = '')
    {
        $this->nonUserTag = $nonUserTag;

        return $this;
    }
    /**
     * @return string
     */
    public function getNonDealTag()
    {
        return $this->nonDealTag;
    }

    /**
     * @param string $nonDealTag
     * @return RequestAddCouponGroup
     */
    public function setNonDealTag($nonDealTag = '')
    {
        $this->nonDealTag = $nonDealTag;

        return $this;
    }
    /**
     * @return array
     */
    public function getTriggerInfo()
    {
        return $this->triggerInfo;
    }

    /**
     * @param array $triggerInfo
     * @return RequestAddCouponGroup
     */
    public function setTriggerInfo(array $triggerInfo = NULL)
    {
        $this->triggerInfo = $triggerInfo;

        return $this;
    }
    /**
     * @return string
     */
    public function getNewNonInviterTag()
    {
        return $this->newNonInviterTag;
    }

    /**
     * @param string $newNonInviterTag
     * @return RequestAddCouponGroup
     */
    public function setNewNonInviterTag($newNonInviterTag = '')
    {
        $this->newNonInviterTag = $newNonInviterTag;

        return $this;
    }
    /**
     * @return string
     */
    public function getNewNonUserTag()
    {
        return $this->newNonUserTag;
    }

    /**
     * @param string $newNonUserTag
     * @return RequestAddCouponGroup
     */
    public function setNewNonUserTag($newNonUserTag = '')
    {
        $this->newNonUserTag = $newNonUserTag;

        return $this;
    }
    /**
     * @return string
     */
    public function getNewNonDealTag()
    {
        return $this->newNonDealTag;
    }

    /**
     * @param string $newNonDealTag
     * @return RequestAddCouponGroup
     */
    public function setNewNonDealTag($newNonDealTag = '')
    {
        $this->newNonDealTag = $newNonDealTag;

        return $this;
    }
    /**
     * @return string
     */
    public function getNewUserTag()
    {
        return $this->newUserTag;
    }

    /**
     * @param string $newUserTag
     * @return RequestAddCouponGroup
     */
    public function setNewUserTag($newUserTag = '')
    {
        $this->newUserTag = $newUserTag;

        return $this;
    }
    /**
     * @return string
     */
    public function getNewInviterTag()
    {
        return $this->newInviterTag;
    }

    /**
     * @param string $newInviterTag
     * @return RequestAddCouponGroup
     */
    public function setNewInviterTag($newInviterTag = '')
    {
        $this->newInviterTag = $newInviterTag;

        return $this;
    }
    /**
     * @return string
     */
    public function getNewDealTag()
    {
        return $this->newDealTag;
    }

    /**
     * @param string $newDealTag
     * @return RequestAddCouponGroup
     */
    public function setNewDealTag($newDealTag = '')
    {
        $this->newDealTag = $newDealTag;

        return $this;
    }
    /**
     * @return string
     */
    public function getCouponPcDesc()
    {
        return $this->couponPcDesc;
    }

    /**
     * @param string $couponPcDesc
     * @return RequestAddCouponGroup
     */
    public function setCouponPcDesc($couponPcDesc = '')
    {
        $this->couponPcDesc = $couponPcDesc;

        return $this;
    }
    /**
     * @return string
     */
    public function getCouponExchangedPcDesc()
    {
        return $this->couponExchangedPcDesc;
    }

    /**
     * @param string $couponExchangedPcDesc
     * @return RequestAddCouponGroup
     */
    public function setCouponExchangedPcDesc($couponExchangedPcDesc = '')
    {
        $this->couponExchangedPcDesc = $couponExchangedPcDesc;

        return $this;
    }
    /**
     * @return int
     */
    public function getAcquiredWxInviterAllowanceType()
    {
        return $this->acquiredWxInviterAllowanceType;
    }

    /**
     * @param int $acquiredWxInviterAllowanceType
     * @return RequestAddCouponGroup
     */
    public function setAcquiredWxInviterAllowanceType($acquiredWxInviterAllowanceType = 0)
    {
        $this->acquiredWxInviterAllowanceType = $acquiredWxInviterAllowanceType;

        return $this;
    }
    /**
     * @return int
     */
    public function getAcquiredWxInviterAllowanceLimit()
    {
        return $this->acquiredWxInviterAllowanceLimit;
    }

    /**
     * @param int $acquiredWxInviterAllowanceLimit
     * @return RequestAddCouponGroup
     */
    public function setAcquiredWxInviterAllowanceLimit($acquiredWxInviterAllowanceLimit = 0)
    {
        $this->acquiredWxInviterAllowanceLimit = $acquiredWxInviterAllowanceLimit;

        return $this;
    }
    /**
     * @return float
     */
    public function getAcquiredWxInviterAllowanceMoney()
    {
        return $this->acquiredWxInviterAllowanceMoney;
    }

    /**
     * @param float $acquiredWxInviterAllowanceMoney
     * @return RequestAddCouponGroup
     */
    public function setAcquiredWxInviterAllowanceMoney($acquiredWxInviterAllowanceMoney = 0)
    {
        $this->acquiredWxInviterAllowanceMoney = $acquiredWxInviterAllowanceMoney;

        return $this;
    }
    /**
     * @return int
     */
    public function getAcquiredWxInviterAllowanceCount()
    {
        return $this->acquiredWxInviterAllowanceCount;
    }

    /**
     * @param int $acquiredWxInviterAllowanceCount
     * @return RequestAddCouponGroup
     */
    public function setAcquiredWxInviterAllowanceCount($acquiredWxInviterAllowanceCount = 0)
    {
        $this->acquiredWxInviterAllowanceCount = $acquiredWxInviterAllowanceCount;

        return $this;
    }

}