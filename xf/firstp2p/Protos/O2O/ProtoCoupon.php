<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:券码信息Proto
 *
 * 由代码生成器生成, 不可人为修改
 * @author yangqing
 */
class ProtoCoupon extends ProtoBufferBase
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
    private $couponGroupId = '';

    /**
     * 券号
     *
     * @var string
     * @optional
     */
    private $couponNumber = '';

    /**
     * 领取人ID
     *
     * @var int
     * @optional
     */
    private $ownerUserId = '';

    /**
     * 零售终端,对应的网信id
     *
     * @var int
     * @optional
     */
    private $storeUserId = '';

    /**
     * 券码来源
     *
     * @var int
     * @optional
     */
    private $source = '';

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
     * 网信补贴供应商
     *
     * @var float
     * @optional
     */
    private $wxAllowanceSup = 0;

    /**
     * 网信补贴零售店
     *
     * @var float
     * @optional
     */
    private $wxAllowanceStore = 0;

    /**
     * 网信补贴渠道
     *
     * @var float
     * @optional
     */
    private $wxAllowanceChannel = 0;

    /**
     * 网信补贴邀请人
     *
     * @var float
     * @optional
     */
    private $wxAllowanceInviter = 0;

    /**
     * 供应商补贴零售店
     *
     * @var float
     * @optional
     */
    private $supAllowanceStore = 0;

    /**
     * 渠道补贴零售店
     *
     * @var float
     * @optional
     */
    private $channelAllowanceStore = 0;

    /**
     * 网信补贴供应商类型
     *
     * @var int
     * @optional
     */
    private $wxAllowanceSupType = 0;

    /**
     * 网信补贴零售店类型
     *
     * @var int
     * @optional
     */
    private $wxAllowanceStoreType = 0;

    /**
     * 网信补贴渠道类型
     *
     * @var int
     * @optional
     */
    private $wxAllowanceChannelType = 0;

    /**
     * 网信补贴邀请人类型
     *
     * @var int
     * @optional
     */
    private $wxAllowanceInviterType = 0;

    /**
     * 供应商补贴零售店类型
     *
     * @var int
     * @optional
     */
    private $supAllowanceStoreType = 0;

    /**
     * 渠道补贴零售店类型
     *
     * @var int
     * @optional
     */
    private $channelAllowanceStoreType = 0;

    /**
     * 网信补贴供应商
     *
     * @var int
     * @optional
     */
    private $wxAllowanceSupLimit = 0;

    /**
     * 网信补贴零售店
     *
     * @var int
     * @optional
     */
    private $wxAllowanceStoreLimit = 0;

    /**
     * 网信补贴渠道
     *
     * @var int
     * @optional
     */
    private $wxAllowanceChannelLimit = 0;

    /**
     * 网信补贴邀请人
     *
     * @var int
     * @optional
     */
    private $wxAllowanceInviterLimit = 0;

    /**
     * 供应商补贴零售店
     *
     * @var int
     * @optional
     */
    private $supAllowanceStoreLimit = 0;

    /**
     * 渠道补贴零售店
     *
     * @var int
     * @optional
     */
    private $channelAllowanceStoreLimit = 0;

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
     * 网信用户id
     *
     * @var int
     * @optional
     */
    private $wxUserId = 0;

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
     * 使用规则
     *
     * @var int
     * @optional
     */
    private $useRules = 0;

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
     * 消息推送
     *
     * @var array
     * @optional
     */
    private $push = NULL;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ProtoCoupon
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
    public function getCouponGroupId()
    {
        return $this->couponGroupId;
    }

    /**
     * @param int $couponGroupId
     * @return ProtoCoupon
     */
    public function setCouponGroupId($couponGroupId = '')
    {
        $this->couponGroupId = $couponGroupId;

        return $this;
    }
    /**
     * @return string
     */
    public function getCouponNumber()
    {
        return $this->couponNumber;
    }

    /**
     * @param string $couponNumber
     * @return ProtoCoupon
     */
    public function setCouponNumber($couponNumber = '')
    {
        $this->couponNumber = $couponNumber;

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
     * @return ProtoCoupon
     */
    public function setOwnerUserId($ownerUserId = '')
    {
        $this->ownerUserId = $ownerUserId;

        return $this;
    }
    /**
     * @return int
     */
    public function getStoreUserId()
    {
        return $this->storeUserId;
    }

    /**
     * @param int $storeUserId
     * @return ProtoCoupon
     */
    public function setStoreUserId($storeUserId = '')
    {
        $this->storeUserId = $storeUserId;

        return $this;
    }
    /**
     * @return int
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param int $source
     * @return ProtoCoupon
     */
    public function setSource($source = '')
    {
        $this->source = $source;

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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
     */
    public function setUpdateTime($updateTime = '')
    {
        $this->updateTime = $updateTime;

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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
     */
    public function setChannelAllowanceStoreLimit($channelAllowanceStoreLimit = 0)
    {
        $this->channelAllowanceStoreLimit = $channelAllowanceStoreLimit;

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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
     */
    public function setLuckyMoneyAllowanceCount($luckyMoneyAllowanceCount = 0)
    {
        $this->luckyMoneyAllowanceCount = $luckyMoneyAllowanceCount;

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
     * @return ProtoCoupon
     */
    public function setWxUserId($wxUserId = 0)
    {
        $this->wxUserId = $wxUserId;

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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
     */
    public function setWxAllowanceInviterCount($wxAllowanceInviterCount = 0)
    {
        $this->wxAllowanceInviterCount = $wxAllowanceInviterCount;

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
     * @return ProtoCoupon
     */
    public function setUseRules($useRules = 0)
    {
        $this->useRules = $useRules;

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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
     */
    public function setAcquiredWxOwnerAllowanceCount($acquiredWxOwnerAllowanceCount = 0)
    {
        $this->acquiredWxOwnerAllowanceCount = $acquiredWxOwnerAllowanceCount;

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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
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
     * @return ProtoCoupon
     */
    public function setAcquiredWxInviterAllowanceCount($acquiredWxInviterAllowanceCount = 0)
    {
        $this->acquiredWxInviterAllowanceCount = $acquiredWxInviterAllowanceCount;

        return $this;
    }
    /**
     * @return array
     */
    public function getPush()
    {
        return $this->push;
    }

    /**
     * @param array $push
     * @return ProtoCoupon
     */
    public function setPush(array $push = NULL)
    {
        $this->push = $push;

        return $this;
    }

}