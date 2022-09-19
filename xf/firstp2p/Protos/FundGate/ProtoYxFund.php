<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 可用基金列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author Gu Weigang <guweigang@ucfgroup.com>
 */
class ProtoYxFund extends ProtoBufferBase
{
    /**
     * 基金ID
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * 基金编码
     *
     * @var string
     * @required
     */
    private $code;

    /**
     * 基多名称
     *
     * @var string
     * @required
     */
    private $name;

    /**
     * 基金类型
     *
     * @var int
     * @required
     */
    private $type;

    /**
     * 基金风险等级
     *
     * @var int
     * @required
     */
    private $riskLevel;

    /**
     * 起投金额（单位：元）
     *
     * @var int
     * @required
     */
    private $leastPurchase;

    /**
     * 基金净值（单位：元）
     *
     * @var float
     * @optional
     */
    private $netValue = '0.0000';

    /**
     * 基金利率
     *
     * @var float
     * @optional
     */
    private $dailyProfit = '0.0000';

    /**
     * 近一个月收益
     *
     * @var string
     * @optional
     */
    private $oneMonthYield = '';

    /**
     * 近三个月收益
     *
     * @var string
     * @optional
     */
    private $threeMonthYield = '';

    /**
     * 近半年收益
     *
     * @var string
     * @optional
     */
    private $halfYearYield = '';

    /**
     * 近一年收益
     *
     * @var string
     * @optional
     */
    private $oneYearYield = '';

    /**
     * 七日年化收益率
     *
     * @var float
     * @required
     */
    private $weeklyYield;

    /**
     * 基金详情url
     *
     * @var string
     * @required
     */
    private $url;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ProtoYxFund
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

        return $this;
    }
    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return ProtoYxFund
     */
    public function setCode($code)
    {
        \Assert\Assertion::string($code);

        $this->code = $code;

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
     * @return ProtoYxFund
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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return ProtoYxFund
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
    public function getRiskLevel()
    {
        return $this->riskLevel;
    }

    /**
     * @param int $riskLevel
     * @return ProtoYxFund
     */
    public function setRiskLevel($riskLevel)
    {
        \Assert\Assertion::integer($riskLevel);

        $this->riskLevel = $riskLevel;

        return $this;
    }
    /**
     * @return int
     */
    public function getLeastPurchase()
    {
        return $this->leastPurchase;
    }

    /**
     * @param int $leastPurchase
     * @return ProtoYxFund
     */
    public function setLeastPurchase($leastPurchase)
    {
        \Assert\Assertion::integer($leastPurchase);

        $this->leastPurchase = $leastPurchase;

        return $this;
    }
    /**
     * @return float
     */
    public function getNetValue()
    {
        return $this->netValue;
    }

    /**
     * @param float $netValue
     * @return ProtoYxFund
     */
    public function setNetValue($netValue = '0.0000')
    {
        $this->netValue = $netValue;

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
     * @return ProtoYxFund
     */
    public function setDailyProfit($dailyProfit = '0.0000')
    {
        $this->dailyProfit = $dailyProfit;

        return $this;
    }
    /**
     * @return string
     */
    public function getOneMonthYield()
    {
        return $this->oneMonthYield;
    }

    /**
     * @param string $oneMonthYield
     * @return ProtoYxFund
     */
    public function setOneMonthYield($oneMonthYield = '')
    {
        $this->oneMonthYield = $oneMonthYield;

        return $this;
    }
    /**
     * @return string
     */
    public function getThreeMonthYield()
    {
        return $this->threeMonthYield;
    }

    /**
     * @param string $threeMonthYield
     * @return ProtoYxFund
     */
    public function setThreeMonthYield($threeMonthYield = '')
    {
        $this->threeMonthYield = $threeMonthYield;

        return $this;
    }
    /**
     * @return string
     */
    public function getHalfYearYield()
    {
        return $this->halfYearYield;
    }

    /**
     * @param string $halfYearYield
     * @return ProtoYxFund
     */
    public function setHalfYearYield($halfYearYield = '')
    {
        $this->halfYearYield = $halfYearYield;

        return $this;
    }
    /**
     * @return string
     */
    public function getOneYearYield()
    {
        return $this->oneYearYield;
    }

    /**
     * @param string $oneYearYield
     * @return ProtoYxFund
     */
    public function setOneYearYield($oneYearYield = '')
    {
        $this->oneYearYield = $oneYearYield;

        return $this;
    }
    /**
     * @return float
     */
    public function getWeeklyYield()
    {
        return $this->weeklyYield;
    }

    /**
     * @param float $weeklyYield
     * @return ProtoYxFund
     */
    public function setWeeklyYield($weeklyYield)
    {
        \Assert\Assertion::float($weeklyYield);

        $this->weeklyYield = $weeklyYield;

        return $this;
    }
    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return ProtoYxFund
     */
    public function setUrl($url)
    {
        \Assert\Assertion::string($url);

        $this->url = $url;

        return $this;
    }

}