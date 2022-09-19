<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 根据触发规则获取券组列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author Yu Tao <yutao@ucfgroup.com>
 */
class RequestGetCouponGroupListByTrigger extends AbstractRequestBase
{
    /**
     * 触发
     *
     * @var int
     * @required
     */
    private $triggerMode;

    /**
     * 触发时间
     *
     * @var int
     * @required
     */
    private $triggerTime;

    /**
     * 金额
     *
     * @var float
     * @optional
     */
    private $amount = 0;

    /**
     * 投资年化金额
     *
     * @var float
     * @optional
     */
    private $annualizedAmount = 0;

    /**
     * 券组ID
     *
     * @var array
     * @optional
     */
    private $couponGroupId = NULL;

    /**
     * 券组状态
     *
     * @var array
     * @optional
     */
    private $couponGroupStatus = NULL;

    /**
     * 分站ID
     *
     * @var string
     * @optional
     */
    private $siteId = NULL;

    /**
     * 用户ID
     *
     * @var string
     * @optional
     */
    private $userId = NULL;

    /**
     * 交易id
     *
     * @var int
     * @optional
     */
    private $dealLoadId = 0;

    /**
     * tag和groupID等过滤条件
     *
     * @var array
     * @optional
     */
    private $filter = NULL;

    /**
     * 交易类型,1为p2p,2为智多鑫,4为黄金,7为随心约
     *
     * @var int
     * @optional
     */
    private $dealType = 1;

    /**
     * 触发类型,1为p2p,2为智多鑫,3为黄金,4为随心约
     *
     * @var int
     * @optional
     */
    private $triggerType = 1;

    /**
     * 用户类型,0表示所有,1为个人,2为企业
     *
     * @var int
     * @optional
     */
    private $userType = 1;

    /**
     * @return int
     */
    public function getTriggerMode()
    {
        return $this->triggerMode;
    }

    /**
     * @param int $triggerMode
     * @return RequestGetCouponGroupListByTrigger
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
    public function getTriggerTime()
    {
        return $this->triggerTime;
    }

    /**
     * @param int $triggerTime
     * @return RequestGetCouponGroupListByTrigger
     */
    public function setTriggerTime($triggerTime)
    {
        \Assert\Assertion::integer($triggerTime);

        $this->triggerTime = $triggerTime;

        return $this;
    }
    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     * @return RequestGetCouponGroupListByTrigger
     */
    public function setAmount($amount = 0)
    {
        $this->amount = $amount;

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
     * @return RequestGetCouponGroupListByTrigger
     */
    public function setAnnualizedAmount($annualizedAmount = 0)
    {
        $this->annualizedAmount = $annualizedAmount;

        return $this;
    }
    /**
     * @return array
     */
    public function getCouponGroupId()
    {
        return $this->couponGroupId;
    }

    /**
     * @param array $couponGroupId
     * @return RequestGetCouponGroupListByTrigger
     */
    public function setCouponGroupId(array $couponGroupId = NULL)
    {
        $this->couponGroupId = $couponGroupId;

        return $this;
    }
    /**
     * @return array
     */
    public function getCouponGroupStatus()
    {
        return $this->couponGroupStatus;
    }

    /**
     * @param array $couponGroupStatus
     * @return RequestGetCouponGroupListByTrigger
     */
    public function setCouponGroupStatus(array $couponGroupStatus = NULL)
    {
        $this->couponGroupStatus = $couponGroupStatus;

        return $this;
    }
    /**
     * @return string
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param string $siteId
     * @return RequestGetCouponGroupListByTrigger
     */
    public function setSiteId($siteId = NULL)
    {
        $this->siteId = $siteId;

        return $this;
    }
    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestGetCouponGroupListByTrigger
     */
    public function setUserId($userId = NULL)
    {
        $this->userId = $userId;

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
     * @return RequestGetCouponGroupListByTrigger
     */
    public function setDealLoadId($dealLoadId = 0)
    {
        $this->dealLoadId = $dealLoadId;

        return $this;
    }
    /**
     * @return array
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param array $filter
     * @return RequestGetCouponGroupListByTrigger
     */
    public function setFilter(array $filter = NULL)
    {
        $this->filter = $filter;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealType()
    {
        return $this->dealType;
    }

    /**
     * @param int $dealType
     * @return RequestGetCouponGroupListByTrigger
     */
    public function setDealType($dealType = 1)
    {
        $this->dealType = $dealType;

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
     * @return RequestGetCouponGroupListByTrigger
     */
    public function setTriggerType($triggerType = 1)
    {
        $this->triggerType = $triggerType;

        return $this;
    }
    /**
     * @return int
     */
    public function getUserType()
    {
        return $this->userType;
    }

    /**
     * @param int $userType
     * @return RequestGetCouponGroupListByTrigger
     */
    public function setUserType($userType = 1)
    {
        $this->userType = $userType;

        return $this;
    }

}