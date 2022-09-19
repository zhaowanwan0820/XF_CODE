<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use NCFGroup\Common\Library\Date\XDateTime;

/**
 * 某天七日年化与万份收益
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ProtoDailyProfitAndLatestWeeklyYield extends ProtoBufferBase
{
    /**
     * 时间
     *
     * @var XDateTime
     * @required
     */
    private $dateTime;

    /**
     * 万份收益
     *
     * @var float
     * @required
     */
    private $dailyProfit;

    /**
     * 七日年化
     *
     * @var float
     * @required
     */
    private $latestWeeklyYield;

    /**
     * @return XDateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * @param XDateTime $dateTime
     * @return ProtoDailyProfitAndLatestWeeklyYield
     */
    public function setDateTime(XDateTime $dateTime)
    {
        $this->dateTime = $dateTime;

        return $this;
    }
    /**
     * @return float
     */
    public function getDailyProfit()
    {
        return $this->dailyProfit;
    }

    /**
     * @param float $dailyProfit
     * @return ProtoDailyProfitAndLatestWeeklyYield
     */
    public function setDailyProfit($dailyProfit)
    {
        \Assert\Assertion::float($dailyProfit);

        $this->dailyProfit = $dailyProfit;

        return $this;
    }
    /**
     * @return float
     */
    public function getLatestWeeklyYield()
    {
        return $this->latestWeeklyYield;
    }

    /**
     * @param float $latestWeeklyYield
     * @return ProtoDailyProfitAndLatestWeeklyYield
     */
    public function setLatestWeeklyYield($latestWeeklyYield)
    {
        \Assert\Assertion::float($latestWeeklyYield);

        $this->latestWeeklyYield = $latestWeeklyYield;

        return $this;
    }

}