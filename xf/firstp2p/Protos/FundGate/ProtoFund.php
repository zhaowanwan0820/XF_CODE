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
class ProtoFund extends ProtoBufferBase
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
     * 基金净值
     *
     * @var float
     * @required
     */
    private $netValue;

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
     * 七日年化收益率
     *
     * @var float
     * @required
     */
    private $weeklyYield;

    /**
     * 万份收益
     *
     * @var float
     * @required
     */
    private $dailyProfit;

    /**
     * 售卖类型，1 随买随卖 2 固定期限 3 定期开放
     *
     * @var int
     * @required
     */
    private $saleType;

    /**
     * 起投金额（单位：分）
     *
     * @var int
     * @required
     */
    private $leastPurchase;

    /**
     * 最低持有份额（单位：份）
     *
     * @var float
     * @required
     */
    private $leastShare;

    /**
     * 最多投资金额（单位：分）
     *
     * @var int
     * @required
     */
    private $mostPurchase;

    /**
     * 追加金额（单位：分）
     *
     * @var int
     * @required
     */
    private $supplymentPurchase;

    /**
     * 基金管理人编码
     *
     * @var string
     * @required
     */
    private $managerCode;

    /**
     * 基金供应商ID
     *
     * @var int
     * @required
     */
    private $vendorId;

    /**
     * 基金状态
     *
     * @var int
     * @required
     */
    private $status;

    /**
     * 预期收益率
     *
     * @var string
     * @optional
     */
    private $expectedProfit = '';

    /**
     * 基金投资期限
     *
     * @var string
     * @optional
     */
    private $duration = '';

    /**
     * 基金可购买份额
     *
     * @var float
     * @optional
     */
    private $remainShare = 0;

    /**
     * 开放日期
     *
     * @var string
     * @optional
     */
    private $openday = '';

    /**
     * 基金原始状态
     *
     * @var string
     * @optional
     */
    private $showStatus = '0';

    /**
     * 截止日期
     *
     * @var string
     * @optional
     */
    private $declareEndDay = '';

    /**
     * 基金单位净值近1年来涨跌幅
     *
     * @var float
     * @optional
     */
    private $RRInSingleYear = 0;

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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ProtoFund
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
     * @return ProtoFund
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
     * @return ProtoFund
     */
    public function setName($name)
    {
        \Assert\Assertion::string($name);

        $this->name = $name;

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
     * @return ProtoFund
     */
    public function setNetValue($netValue)
    {
        \Assert\Assertion::float($netValue);

        $this->netValue = $netValue;

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
     * @return ProtoFund
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
     * @return ProtoFund
     */
    public function setRiskLevel($riskLevel)
    {
        \Assert\Assertion::integer($riskLevel);

        $this->riskLevel = $riskLevel;

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
     * @return ProtoFund
     */
    public function setWeeklyYield($weeklyYield)
    {
        \Assert\Assertion::float($weeklyYield);

        $this->weeklyYield = $weeklyYield;

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
     * @return ProtoFund
     */
    public function setDailyProfit($dailyProfit)
    {
        \Assert\Assertion::float($dailyProfit);

        $this->dailyProfit = $dailyProfit;

        return $this;
    }
    /**
     * @return int
     */
    public function getSaleType()
    {
        return $this->saleType;
    }

    /**
     * @param int $saleType
     * @return ProtoFund
     */
    public function setSaleType($saleType)
    {
        \Assert\Assertion::integer($saleType);

        $this->saleType = $saleType;

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
     * @return ProtoFund
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
    public function getLeastShare()
    {
        return $this->leastShare;
    }

    /**
     * @param float $leastShare
     * @return ProtoFund
     */
    public function setLeastShare($leastShare)
    {
        \Assert\Assertion::float($leastShare);

        $this->leastShare = $leastShare;

        return $this;
    }
    /**
     * @return int
     */
    public function getMostPurchase()
    {
        return $this->mostPurchase;
    }

    /**
     * @param int $mostPurchase
     * @return ProtoFund
     */
    public function setMostPurchase($mostPurchase)
    {
        \Assert\Assertion::integer($mostPurchase);

        $this->mostPurchase = $mostPurchase;

        return $this;
    }
    /**
     * @return int
     */
    public function getSupplymentPurchase()
    {
        return $this->supplymentPurchase;
    }

    /**
     * @param int $supplymentPurchase
     * @return ProtoFund
     */
    public function setSupplymentPurchase($supplymentPurchase)
    {
        \Assert\Assertion::integer($supplymentPurchase);

        $this->supplymentPurchase = $supplymentPurchase;

        return $this;
    }
    /**
     * @return string
     */
    public function getManagerCode()
    {
        return $this->managerCode;
    }

    /**
     * @param string $managerCode
     * @return ProtoFund
     */
    public function setManagerCode($managerCode)
    {
        \Assert\Assertion::string($managerCode);

        $this->managerCode = $managerCode;

        return $this;
    }
    /**
     * @return int
     */
    public function getVendorId()
    {
        return $this->vendorId;
    }

    /**
     * @param int $vendorId
     * @return ProtoFund
     */
    public function setVendorId($vendorId)
    {
        \Assert\Assertion::integer($vendorId);

        $this->vendorId = $vendorId;

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
     * @return ProtoFund
     */
    public function setStatus($status)
    {
        \Assert\Assertion::integer($status);

        $this->status = $status;

        return $this;
    }
    /**
     * @return string
     */
    public function getExpectedProfit()
    {
        return $this->expectedProfit;
    }

    /**
     * @param string $expectedProfit
     * @return ProtoFund
     */
    public function setExpectedProfit($expectedProfit = '')
    {
        $this->expectedProfit = $expectedProfit;

        return $this;
    }
    /**
     * @return string
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param string $duration
     * @return ProtoFund
     */
    public function setDuration($duration = '')
    {
        $this->duration = $duration;

        return $this;
    }
    /**
     * @return float
     */
    public function getRemainShare()
    {
        return $this->remainShare;
    }

    /**
     * @param float $remainShare
     * @return ProtoFund
     */
    public function setRemainShare($remainShare = 0)
    {
        $this->remainShare = $remainShare;

        return $this;
    }
    /**
     * @return string
     */
    public function getOpenday()
    {
        return $this->openday;
    }

    /**
     * @param string $openday
     * @return ProtoFund
     */
    public function setOpenday($openday = '')
    {
        $this->openday = $openday;

        return $this;
    }
    /**
     * @return string
     */
    public function getShowStatus()
    {
        return $this->showStatus;
    }

    /**
     * @param string $showStatus
     * @return ProtoFund
     */
    public function setShowStatus($showStatus = '0')
    {
        $this->showStatus = $showStatus;

        return $this;
    }
    /**
     * @return string
     */
    public function getDeclareEndDay()
    {
        return $this->declareEndDay;
    }

    /**
     * @param string $declareEndDay
     * @return ProtoFund
     */
    public function setDeclareEndDay($declareEndDay = '')
    {
        $this->declareEndDay = $declareEndDay;

        return $this;
    }
    /**
     * @return float
     */
    public function getRRInSingleYear()
    {
        return $this->RRInSingleYear;
    }

    /**
     * @param float $RRInSingleYear
     * @return ProtoFund
     */
    public function setRRInSingleYear($RRInSingleYear = 0)
    {
        $this->RRInSingleYear = $RRInSingleYear;

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
     * @return ProtoFund
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
     * @return ProtoFund
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
     * @return ProtoFund
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
     * @return ProtoFund
     */
    public function setOneYearYield($oneYearYield = '')
    {
        $this->oneYearYield = $oneYearYield;

        return $this;
    }

}