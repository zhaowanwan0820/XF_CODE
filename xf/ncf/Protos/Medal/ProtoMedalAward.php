<?php
namespace NCFGroup\Protos\Medal;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 新手勋章medal对应奖励
 *
 * 由代码生成器生成, 不可人为修改
 * @author luzhengshuai
 */
class ProtoMedalAward extends ProtoBufferBase
{
    /**
     * 奖励ID
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * 奖励所属勋章ID
     *
     * @var int
     * @required
     */
    private $medalId;

    /**
     * 奖励开始时间
     *
     * @var int
     * @required
     */
    private $timeStart;

    /**
     * 奖励列表（券组ID列表)
     *
     * @var string
     * @required
     */
    private $awardList;

    /**
     * 奖励可领取数量
     *
     * @var int
     * @required
     */
    private $awardNum;

    /**
     * 奖励领取时间限制
     *
     * @var int
     * @required
     */
    private $awardLimitTime;

    /**
     * 邀请人奖励
     *
     * @var string
     * @optional
     */
    private $inviterAwardList = '';

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ProtoMedalAward
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
    public function getMedalId()
    {
        return $this->medalId;
    }

    /**
     * @param int $medalId
     * @return ProtoMedalAward
     */
    public function setMedalId($medalId)
    {
        \Assert\Assertion::integer($medalId);

        $this->medalId = $medalId;

        return $this;
    }
    /**
     * @return int
     */
    public function getTimeStart()
    {
        return $this->timeStart;
    }

    /**
     * @param int $timeStart
     * @return ProtoMedalAward
     */
    public function setTimeStart($timeStart)
    {
        \Assert\Assertion::integer($timeStart);

        $this->timeStart = $timeStart;

        return $this;
    }
    /**
     * @return string
     */
    public function getAwardList()
    {
        return $this->awardList;
    }

    /**
     * @param string $awardList
     * @return ProtoMedalAward
     */
    public function setAwardList($awardList)
    {
        \Assert\Assertion::string($awardList);

        $this->awardList = $awardList;

        return $this;
    }
    /**
     * @return int
     */
    public function getAwardNum()
    {
        return $this->awardNum;
    }

    /**
     * @param int $awardNum
     * @return ProtoMedalAward
     */
    public function setAwardNum($awardNum)
    {
        \Assert\Assertion::integer($awardNum);

        $this->awardNum = $awardNum;

        return $this;
    }
    /**
     * @return int
     */
    public function getAwardLimitTime()
    {
        return $this->awardLimitTime;
    }

    /**
     * @param int $awardLimitTime
     * @return ProtoMedalAward
     */
    public function setAwardLimitTime($awardLimitTime)
    {
        \Assert\Assertion::integer($awardLimitTime);

        $this->awardLimitTime = $awardLimitTime;

        return $this;
    }
    /**
     * @return string
     */
    public function getInviterAwardList()
    {
        return $this->inviterAwardList;
    }

    /**
     * @param string $inviterAwardList
     * @return ProtoMedalAward
     */
    public function setInviterAwardList($inviterAwardList = '')
    {
        $this->inviterAwardList = $inviterAwardList;

        return $this;
    }

}