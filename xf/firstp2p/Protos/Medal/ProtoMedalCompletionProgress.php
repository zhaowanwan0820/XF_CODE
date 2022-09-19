<?php
namespace NCFGroup\Protos\Medal;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * medal规则下的完成进度
 *
 * 由代码生成器生成, 不可人为修改
 * @author luzhengshuai
 */
class ProtoMedalCompletionProgress extends ProtoBufferBase
{
    /**
     * 进度的自增ID
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 勋章ID
     *
     * @var int
     * @required
     */
    private $medalId;

    /**
     * 勋章的规则
     *
     * @var int
     * @required
     */
    private $medalRuleId;

    /**
     * 统计类型
     *
     * @var int
     * @required
     */
    private $statType;

    /**
     * 累计金额
     *
     * @var int
     * @required
     */
    private $totalMoney;

    /**
     * 累计次数
     *
     * @var int
     * @required
     */
    private $totalTimes;

    /**
     * 累计人数
     *
     * @var int
     * @required
     */
    private $totalPersons;

    /**
     * 连续投资天数
     *
     * @var int
     * @required
     */
    private $continuousDays;

    /**
     * 是否完成
     *
     * @var int
     * @required
     */
    private $isComplete;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ProtoMedalCompletionProgress
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
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return ProtoMedalCompletionProgress
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
    public function getMedalId()
    {
        return $this->medalId;
    }

    /**
     * @param int $medalId
     * @return ProtoMedalCompletionProgress
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
    public function getMedalRuleId()
    {
        return $this->medalRuleId;
    }

    /**
     * @param int $medalRuleId
     * @return ProtoMedalCompletionProgress
     */
    public function setMedalRuleId($medalRuleId)
    {
        \Assert\Assertion::integer($medalRuleId);

        $this->medalRuleId = $medalRuleId;

        return $this;
    }
    /**
     * @return int
     */
    public function getStatType()
    {
        return $this->statType;
    }

    /**
     * @param int $statType
     * @return ProtoMedalCompletionProgress
     */
    public function setStatType($statType)
    {
        \Assert\Assertion::integer($statType);

        $this->statType = $statType;

        return $this;
    }
    /**
     * @return int
     */
    public function getTotalMoney()
    {
        return $this->totalMoney;
    }

    /**
     * @param int $totalMoney
     * @return ProtoMedalCompletionProgress
     */
    public function setTotalMoney($totalMoney)
    {
        \Assert\Assertion::integer($totalMoney);

        $this->totalMoney = $totalMoney;

        return $this;
    }
    /**
     * @return int
     */
    public function getTotalTimes()
    {
        return $this->totalTimes;
    }

    /**
     * @param int $totalTimes
     * @return ProtoMedalCompletionProgress
     */
    public function setTotalTimes($totalTimes)
    {
        \Assert\Assertion::integer($totalTimes);

        $this->totalTimes = $totalTimes;

        return $this;
    }
    /**
     * @return int
     */
    public function getTotalPersons()
    {
        return $this->totalPersons;
    }

    /**
     * @param int $totalPersons
     * @return ProtoMedalCompletionProgress
     */
    public function setTotalPersons($totalPersons)
    {
        \Assert\Assertion::integer($totalPersons);

        $this->totalPersons = $totalPersons;

        return $this;
    }
    /**
     * @return int
     */
    public function getContinuousDays()
    {
        return $this->continuousDays;
    }

    /**
     * @param int $continuousDays
     * @return ProtoMedalCompletionProgress
     */
    public function setContinuousDays($continuousDays)
    {
        \Assert\Assertion::integer($continuousDays);

        $this->continuousDays = $continuousDays;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsComplete()
    {
        return $this->isComplete;
    }

    /**
     * @param int $isComplete
     * @return ProtoMedalCompletionProgress
     */
    public function setIsComplete($isComplete)
    {
        \Assert\Assertion::integer($isComplete);

        $this->isComplete = $isComplete;

        return $this;
    }

}