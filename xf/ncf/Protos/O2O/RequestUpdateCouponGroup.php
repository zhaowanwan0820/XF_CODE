<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:编辑券组信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class RequestUpdateCouponGroup extends ProtoBufferBase
{
    /**
     * 券组id
     *
     * @var int
     * @required
     */
    private $id;

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
     * 投标的tag中含有
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return RequestUpdateCouponGroup
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
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param int $productId
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
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
     * @return RequestUpdateCouponGroup
     */
    public function setAcquiredWxInviterAllowanceCount($acquiredWxInviterAllowanceCount = 0)
    {
        $this->acquiredWxInviterAllowanceCount = $acquiredWxInviterAllowanceCount;

        return $this;
    }

}