<?php
namespace NCFGroup\Protos\Medal;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 投资完成后，统计通知相关的信息，然后根据统计结果发放勋章
 *
 * 由代码生成器生成, 不可人为修改
 * @author dengyi <dengyi@ucfgroup.com>
 */
class RequestMedalInvestStat extends AbstractRequestBase
{
    /**
     * 投资用户的ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 投资唯一的ID，用来做幂等
     *
     * @var int
     * @required
     */
    private $investId;

    /**
     * 投资时间戳，北京时区
     *
     * @var int
     * @required
     */
    private $investTime;

    /**
     * 是否是公益标
     *
     * @var bool
     * @required
     */
    private $isCharity;

    /**
     * 是否是第一次投资
     *
     * @var bool
     * @required
     */
    private $isFirstInvest;

    /**
     * 投资金额(分)
     *
     * @var int
     * @required
     */
    private $investMoney;

    /**
     * 红包使用金额(分)
     *
     * @var int
     * @required
     */
    private $bonus;

    /**
     * 投资的方式:web/mobile/mobileapp/openapi/api
     *
     * @var string
     * @required
     */
    private $platform;

    /**
     * 投资的站点
     *
     * @var int
     * @optional
     */
    private $siteId = 1;

    /**
     * 邀请人的ID
     *
     * @var int
     * @optional
     */
    private $inviterId = -1;

    /**
     * 标期限
     *
     * @var int
     * @optional
     */
    private $dealHorizon = 0;

    /**
     * 标TAG
     *
     * @var string
     * @optional
     */
    private $dealTag = NULL;

    /**
     * 用户的Tag
     *
     * @var array
     * @optional
     */
    private $userTag = NULL;

    /**
     * 邀请人的Tag
     *
     * @var array
     * @optional
     */
    private $inviterTag = NULL;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestMedalInvestStat
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
    public function getInvestId()
    {
        return $this->investId;
    }

    /**
     * @param int $investId
     * @return RequestMedalInvestStat
     */
    public function setInvestId($investId)
    {
        \Assert\Assertion::integer($investId);

        $this->investId = $investId;

        return $this;
    }
    /**
     * @return int
     */
    public function getInvestTime()
    {
        return $this->investTime;
    }

    /**
     * @param int $investTime
     * @return RequestMedalInvestStat
     */
    public function setInvestTime($investTime)
    {
        \Assert\Assertion::integer($investTime);

        $this->investTime = $investTime;

        return $this;
    }
    /**
     * @return bool
     */
    public function getIsCharity()
    {
        return $this->isCharity;
    }

    /**
     * @param bool $isCharity
     * @return RequestMedalInvestStat
     */
    public function setIsCharity($isCharity)
    {
        \Assert\Assertion::boolean($isCharity);

        $this->isCharity = $isCharity;

        return $this;
    }
    /**
     * @return bool
     */
    public function getIsFirstInvest()
    {
        return $this->isFirstInvest;
    }

    /**
     * @param bool $isFirstInvest
     * @return RequestMedalInvestStat
     */
    public function setIsFirstInvest($isFirstInvest)
    {
        \Assert\Assertion::boolean($isFirstInvest);

        $this->isFirstInvest = $isFirstInvest;

        return $this;
    }
    /**
     * @return int
     */
    public function getInvestMoney()
    {
        return $this->investMoney;
    }

    /**
     * @param int $investMoney
     * @return RequestMedalInvestStat
     */
    public function setInvestMoney($investMoney)
    {
        \Assert\Assertion::integer($investMoney);

        $this->investMoney = $investMoney;

        return $this;
    }
    /**
     * @return int
     */
    public function getBonus()
    {
        return $this->bonus;
    }

    /**
     * @param int $bonus
     * @return RequestMedalInvestStat
     */
    public function setBonus($bonus)
    {
        \Assert\Assertion::integer($bonus);

        $this->bonus = $bonus;

        return $this;
    }
    /**
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @param string $platform
     * @return RequestMedalInvestStat
     */
    public function setPlatform($platform)
    {
        \Assert\Assertion::string($platform);

        $this->platform = $platform;

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
     * @return RequestMedalInvestStat
     */
    public function setSiteId($siteId = 1)
    {
        $this->siteId = $siteId;

        return $this;
    }
    /**
     * @return int
     */
    public function getInviterId()
    {
        return $this->inviterId;
    }

    /**
     * @param int $inviterId
     * @return RequestMedalInvestStat
     */
    public function setInviterId($inviterId = -1)
    {
        $this->inviterId = $inviterId;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealHorizon()
    {
        return $this->dealHorizon;
    }

    /**
     * @param int $dealHorizon
     * @return RequestMedalInvestStat
     */
    public function setDealHorizon($dealHorizon = 0)
    {
        $this->dealHorizon = $dealHorizon;

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
     * @return RequestMedalInvestStat
     */
    public function setDealTag($dealTag = NULL)
    {
        $this->dealTag = $dealTag;

        return $this;
    }
    /**
     * @return array
     */
    public function getUserTag()
    {
        return $this->userTag;
    }

    /**
     * @param array $userTag
     * @return RequestMedalInvestStat
     */
    public function setUserTag(array $userTag = NULL)
    {
        $this->userTag = $userTag;

        return $this;
    }
    /**
     * @return array
     */
    public function getInviterTag()
    {
        return $this->inviterTag;
    }

    /**
     * @param array $inviterTag
     * @return RequestMedalInvestStat
     */
    public function setInviterTag(array $inviterTag = NULL)
    {
        $this->inviterTag = $inviterTag;

        return $this;
    }

}