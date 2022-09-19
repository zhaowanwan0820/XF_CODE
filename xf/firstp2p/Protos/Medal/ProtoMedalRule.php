<?php
namespace NCFGroup\Protos\Medal;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * medal对应规则(即medal对应达成条件)
 *
 * 由代码生成器生成, 不可人为修改
 * @author luzhengshuai
 */
class ProtoMedalRule extends ProtoBufferBase
{
    /**
     * 数据时间统计类型
     *
     * @var int
     * @required
     */
    private $type;

    /**
     * 统计类型
     *
     * @var int
     * @required
     */
    private $statType;

    /**
     * 规则的名称
     *
     * @var string
     * @required
     */
    private $name;

    /**
     * 规则所属的勋章
     *
     * @var int
     * @required
     */
    private $medalId;

    /**
     * 投资次数
     *
     * @var int
     * @required
     */
    private $totalTimes;

    /**
     * 累计金额,单位（分）
     *
     * @var int
     * @required
     */
    private $totalMoney;

    /**
     * 累计人数
     *
     * @var int
     * @required
     */
    private $totalPersons;

    /**
     * 连续天数
     *
     * @var int
     * @required
     */
    private $continuousDays;

    /**
     * 单次最小金额,单位（分）
     *
     * @var int
     * @required
     */
    private $moneyPerTime;

    /**
     * 统计的订阅事件类型
     *
     * @var int
     * @required
     */
    private $eventType;

    /**
     * 规则生效起始时间
     *
     * @var int
     * @required
     */
    private $startTime;

    /**
     * 规则自增ID
     *
     * @var int
     * @optional
     */
    private $id = 0;

    /**
     * 规则结束时间
     *
     * @var int
     * @optional
     */
    private $endTime = 0;

    /**
     * 规则对应参数值
     *
     * @var int
     * @optional
     */
    private $expectedValue = 0;

    /**
     * 标的期限
     *
     * @var int
     * @optional
     */
    private $dealHorizon = 0;

    /**
     * 标的标签
     *
     * @var string
     * @optional
     */
    private $dealTag = '';

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return ProtoMedalRule
     */
    public function setType($type)
    {
        \Assert\Assertion::integer($type);

        $this->type = $type;

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
     * @return ProtoMedalRule
     */
    public function setStatType($statType)
    {
        \Assert\Assertion::integer($statType);

        $this->statType = $statType;

        return $this;
    }
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ProtoMedalRule
     */
    public function setName($name)
    {
        \Assert\Assertion::string($name);

        $this->name = $name;

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
     * @return ProtoMedalRule
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
    public function getTotalTimes()
    {
        return $this->totalTimes;
    }

    /**
     * @param int $totalTimes
     * @return ProtoMedalRule
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
    public function getTotalMoney()
    {
        return $this->totalMoney;
    }

    /**
     * @param int $totalMoney
     * @return ProtoMedalRule
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
    public function getTotalPersons()
    {
        return $this->totalPersons;
    }

    /**
     * @param int $totalPersons
     * @return ProtoMedalRule
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
     * @return ProtoMedalRule
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
    public function getMoneyPerTime()
    {
        return $this->moneyPerTime;
    }

    /**
     * @param int $moneyPerTime
     * @return ProtoMedalRule
     */
    public function setMoneyPerTime($moneyPerTime)
    {
        \Assert\Assertion::integer($moneyPerTime);

        $this->moneyPerTime = $moneyPerTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getEventType()
    {
        return $this->eventType;
    }

    /**
     * @param int $eventType
     * @return ProtoMedalRule
     */
    public function setEventType($eventType)
    {
        \Assert\Assertion::integer($eventType);

        $this->eventType = $eventType;

        return $this;
    }
    /**
     * @return int
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @param int $startTime
     * @return ProtoMedalRule
     */
    public function setStartTime($startTime)
    {
        \Assert\Assertion::integer($startTime);

        $this->startTime = $startTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ProtoMedalRule
     */
    public function setId($id = 0)
    {
        $this->id = $id;

        return $this;
    }
    /**
     * @return int
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param int $endTime
     * @return ProtoMedalRule
     */
    public function setEndTime($endTime = 0)
    {
        $this->endTime = $endTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getExpectedValue()
    {
        return $this->expectedValue;
    }

    /**
     * @param int $expectedValue
     * @return ProtoMedalRule
     */
    public function setExpectedValue($expectedValue = 0)
    {
        $this->expectedValue = $expectedValue;

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
     * @return ProtoMedalRule
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
     * @return ProtoMedalRule
     */
    public function setDealTag($dealTag = '')
    {
        $this->dealTag = $dealTag;

        return $this;
    }

}