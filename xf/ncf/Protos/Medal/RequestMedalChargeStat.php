<?php
namespace NCFGroup\Protos\Medal;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 充值事件的统计
 *
 * 由代码生成器生成, 不可人为修改
 * @author dengyi <dengyi@ucfgroup.com>
 */
class RequestMedalChargeStat extends AbstractRequestBase
{
    /**
     * 投资用户的ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 充值序列号
     *
     * @var string
     * @required
     */
    private $chargeSequence;

    /**
     * 充值金额
     *
     * @var int
     * @required
     */
    private $chargeMoney;

    /**
     * 充值时间
     *
     * @var int
     * @required
     */
    private $chargeTime;

    /**
     * 投资的站点
     *
     * @var int
     * @optional
     */
    private $siteId = 1;

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
     * @return RequestMedalChargeStat
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getChargeSequence()
    {
        return $this->chargeSequence;
    }

    /**
     * @param string $chargeSequence
     * @return RequestMedalChargeStat
     */
    public function setChargeSequence($chargeSequence)
    {
        \Assert\Assertion::string($chargeSequence);

        $this->chargeSequence = $chargeSequence;

        return $this;
    }
    /**
     * @return int
     */
    public function getChargeMoney()
    {
        return $this->chargeMoney;
    }

    /**
     * @param int $chargeMoney
     * @return RequestMedalChargeStat
     */
    public function setChargeMoney($chargeMoney)
    {
        \Assert\Assertion::integer($chargeMoney);

        $this->chargeMoney = $chargeMoney;

        return $this;
    }
    /**
     * @return int
     */
    public function getChargeTime()
    {
        return $this->chargeTime;
    }

    /**
     * @param int $chargeTime
     * @return RequestMedalChargeStat
     */
    public function setChargeTime($chargeTime)
    {
        \Assert\Assertion::integer($chargeTime);

        $this->chargeTime = $chargeTime;

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
     * @return RequestMedalChargeStat
     */
    public function setSiteId($siteId = 1)
    {
        $this->siteId = $siteId;

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
     * @return RequestMedalChargeStat
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
     * @return RequestMedalChargeStat
     */
    public function setInviterTag(array $inviterTag = NULL)
    {
        $this->inviterTag = $inviterTag;

        return $this;
    }

}