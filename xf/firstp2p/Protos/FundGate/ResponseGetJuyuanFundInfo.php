<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 聚源基金信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ResponseGetJuyuanFundInfo extends ResponseBase
{
    /**
     * 基金名称
     *
     * @var string
     * @required
     */
    private $secuAbbr;

    /**
     * 万份收益
     *
     * @var float
     * @required
     */
    private $dailyProfit;

    /**
     * 最近七天收益
     *
     * @var float
     * @required
     */
    private $latestWeeklyYield;

    /**
     * 基金公司
     *
     * @var string
     * @required
     */
    private $investAdvisorAbbrName;

    /**
     * 基金规模
     *
     * @var float
     * @required
     */
    private $nVAtEnd;

    /**
     * 基金类型
     *
     * @var string
     * @required
     */
    private $fundType;

    /**
     * 基金简介
     *
     * @var string
     * @required
     */
    private $briefIntro;

    /**
     * 一周 最新净值表现
     *
     * @var float
     * @required
     */
    private $rRInSingleWeek;

    /**
     * 一个月 最新净值表现
     *
     * @var float
     * @required
     */
    private $rRInSingleMonth;

    /**
     * 三个月 最新净值表现
     *
     * @var float
     * @required
     */
    private $rRInThreeMonth;

    /**
     * 六个月 最新净值表现
     *
     * @var float
     * @required
     */
    private $rRInSixMonth;

    /**
     * 今年以来 净值表现
     *
     * @var float
     * @required
     */
    private $rRSinceThisYear;

    /**
     * 一周同类平均收益率
     *
     * @var float
     * @required
     */
    private $avgRRInSingleWeek;

    /**
     * 一月同类平均收益率
     *
     * @var float
     * @required
     */
    private $avgRRInSingleMonth;

    /**
     * 三个月同类平均收益率
     *
     * @var float
     * @required
     */
    private $avgRRInThreeMonth;

    /**
     * 六个月同类平均收益率
     *
     * @var float
     * @required
     */
    private $avgRRInSixMonth;

    /**
     * 今年以来同类平均收益率
     *
     * @var float
     * @required
     */
    private $avgRRSinceThisYear;

    /**
     * @return string
     */
    public function getSecuAbbr()
    {
        return $this->secuAbbr;
    }

    /**
     * @param string $secuAbbr
     * @return ResponseGetJuyuanFundInfo
     */
    public function setSecuAbbr($secuAbbr)
    {
        \Assert\Assertion::string($secuAbbr);

        $this->secuAbbr = $secuAbbr;

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
     * @return ResponseGetJuyuanFundInfo
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
     * @return ResponseGetJuyuanFundInfo
     */
    public function setLatestWeeklyYield($latestWeeklyYield)
    {
        \Assert\Assertion::float($latestWeeklyYield);

        $this->latestWeeklyYield = $latestWeeklyYield;

        return $this;
    }
    /**
     * @return string
     */
    public function getInvestAdvisorAbbrName()
    {
        return $this->investAdvisorAbbrName;
    }

    /**
     * @param string $investAdvisorAbbrName
     * @return ResponseGetJuyuanFundInfo
     */
    public function setInvestAdvisorAbbrName($investAdvisorAbbrName)
    {
        \Assert\Assertion::string($investAdvisorAbbrName);

        $this->investAdvisorAbbrName = $investAdvisorAbbrName;

        return $this;
    }
    /**
     * @return float
     */
    public function getNVAtEnd()
    {
        return $this->nVAtEnd;
    }

    /**
     * @param float $nVAtEnd
     * @return ResponseGetJuyuanFundInfo
     */
    public function setNVAtEnd($nVAtEnd)
    {
        \Assert\Assertion::float($nVAtEnd);

        $this->nVAtEnd = $nVAtEnd;

        return $this;
    }
    /**
     * @return string
     */
    public function getFundType()
    {
        return $this->fundType;
    }

    /**
     * @param string $fundType
     * @return ResponseGetJuyuanFundInfo
     */
    public function setFundType($fundType)
    {
        \Assert\Assertion::string($fundType);

        $this->fundType = $fundType;

        return $this;
    }
    /**
     * @return string
     */
    public function getBriefIntro()
    {
        return $this->briefIntro;
    }

    /**
     * @param string $briefIntro
     * @return ResponseGetJuyuanFundInfo
     */
    public function setBriefIntro($briefIntro)
    {
        \Assert\Assertion::string($briefIntro);

        $this->briefIntro = $briefIntro;

        return $this;
    }
    /**
     * @return float
     */
    public function getRRInSingleWeek()
    {
        return $this->rRInSingleWeek;
    }

    /**
     * @param float $rRInSingleWeek
     * @return ResponseGetJuyuanFundInfo
     */
    public function setRRInSingleWeek($rRInSingleWeek)
    {
        \Assert\Assertion::float($rRInSingleWeek);

        $this->rRInSingleWeek = $rRInSingleWeek;

        return $this;
    }
    /**
     * @return float
     */
    public function getRRInSingleMonth()
    {
        return $this->rRInSingleMonth;
    }

    /**
     * @param float $rRInSingleMonth
     * @return ResponseGetJuyuanFundInfo
     */
    public function setRRInSingleMonth($rRInSingleMonth)
    {
        \Assert\Assertion::float($rRInSingleMonth);

        $this->rRInSingleMonth = $rRInSingleMonth;

        return $this;
    }
    /**
     * @return float
     */
    public function getRRInThreeMonth()
    {
        return $this->rRInThreeMonth;
    }

    /**
     * @param float $rRInThreeMonth
     * @return ResponseGetJuyuanFundInfo
     */
    public function setRRInThreeMonth($rRInThreeMonth)
    {
        \Assert\Assertion::float($rRInThreeMonth);

        $this->rRInThreeMonth = $rRInThreeMonth;

        return $this;
    }
    /**
     * @return float
     */
    public function getRRInSixMonth()
    {
        return $this->rRInSixMonth;
    }

    /**
     * @param float $rRInSixMonth
     * @return ResponseGetJuyuanFundInfo
     */
    public function setRRInSixMonth($rRInSixMonth)
    {
        \Assert\Assertion::float($rRInSixMonth);

        $this->rRInSixMonth = $rRInSixMonth;

        return $this;
    }
    /**
     * @return float
     */
    public function getRRSinceThisYear()
    {
        return $this->rRSinceThisYear;
    }

    /**
     * @param float $rRSinceThisYear
     * @return ResponseGetJuyuanFundInfo
     */
    public function setRRSinceThisYear($rRSinceThisYear)
    {
        \Assert\Assertion::float($rRSinceThisYear);

        $this->rRSinceThisYear = $rRSinceThisYear;

        return $this;
    }
    /**
     * @return float
     */
    public function getAvgRRInSingleWeek()
    {
        return $this->avgRRInSingleWeek;
    }

    /**
     * @param float $avgRRInSingleWeek
     * @return ResponseGetJuyuanFundInfo
     */
    public function setAvgRRInSingleWeek($avgRRInSingleWeek)
    {
        \Assert\Assertion::float($avgRRInSingleWeek);

        $this->avgRRInSingleWeek = $avgRRInSingleWeek;

        return $this;
    }
    /**
     * @return float
     */
    public function getAvgRRInSingleMonth()
    {
        return $this->avgRRInSingleMonth;
    }

    /**
     * @param float $avgRRInSingleMonth
     * @return ResponseGetJuyuanFundInfo
     */
    public function setAvgRRInSingleMonth($avgRRInSingleMonth)
    {
        \Assert\Assertion::float($avgRRInSingleMonth);

        $this->avgRRInSingleMonth = $avgRRInSingleMonth;

        return $this;
    }
    /**
     * @return float
     */
    public function getAvgRRInThreeMonth()
    {
        return $this->avgRRInThreeMonth;
    }

    /**
     * @param float $avgRRInThreeMonth
     * @return ResponseGetJuyuanFundInfo
     */
    public function setAvgRRInThreeMonth($avgRRInThreeMonth)
    {
        \Assert\Assertion::float($avgRRInThreeMonth);

        $this->avgRRInThreeMonth = $avgRRInThreeMonth;

        return $this;
    }
    /**
     * @return float
     */
    public function getAvgRRInSixMonth()
    {
        return $this->avgRRInSixMonth;
    }

    /**
     * @param float $avgRRInSixMonth
     * @return ResponseGetJuyuanFundInfo
     */
    public function setAvgRRInSixMonth($avgRRInSixMonth)
    {
        \Assert\Assertion::float($avgRRInSixMonth);

        $this->avgRRInSixMonth = $avgRRInSixMonth;

        return $this;
    }
    /**
     * @return float
     */
    public function getAvgRRSinceThisYear()
    {
        return $this->avgRRSinceThisYear;
    }

    /**
     * @param float $avgRRSinceThisYear
     * @return ResponseGetJuyuanFundInfo
     */
    public function setAvgRRSinceThisYear($avgRRSinceThisYear)
    {
        \Assert\Assertion::float($avgRRSinceThisYear);

        $this->avgRRSinceThisYear = $avgRRSinceThisYear;

        return $this;
    }

}