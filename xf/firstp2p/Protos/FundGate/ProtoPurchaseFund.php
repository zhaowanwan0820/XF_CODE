<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;

/**
 * 已经购买基金列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author chengQ <qicheng@ucfgroup.com>
 */
class ProtoPurchaseFund extends ProtoBufferBase
{
    /**
     * 基金代码
     *
     * @var string
     * @required
     */
    private $code;

    /**
     * 基金名称
     *
     * @var string
     * @required
     */
    private $name;

    /**
     * 基金类型
     *
     * @var string
     * @required
     */
    private $type;

    /**
     * 7日年化
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
     * 昨日收益
     *
     * @var float
     * @required
     */
    private $yesterdayProfit;

    /**
     * 持有份额
     *
     * @var float
     * @optional
     */
    private $holdShare = 0;

    /**
     * 赎回中份额
     *
     * @var float
     * @optional
     */
    private $redeemShare = 0;

    /**
     * 申购中份额
     *
     * @var float
     * @optional
     */
    private $purchaseShare = 0;

    /**
     * 未结算收益
     *
     * @var float
     * @optional
     */
    private $unliquidated = 0;

    /**
     * 可赎回份额
     *
     * @var float
     * @optional
     */
    private $usableRedeemShare = 0;

    /**
     * 剩余可购买份额
     *
     * @var float
     * @optional
     */
    private $remainShare = 0;

    /**
     * 管理人名称（契约基金专有）
     *
     * @var string
     * @optional
     */
    private $managerName = '';

    /**
     * 持有人名称（契约基金专有）
     *
     * @var string
     * @optional
     */
    private $custodianName = '';

    /**
     * 起投金额
     *
     * @var float
     * @required
     */
    private $leastPurchase;

    /**
     * 最低持有份额
     *
     * @var float
     * @required
     */
    private $leastShare;

    /**
     * 风险等级
     *
     * @var int
     * @required
     */
    private $riskLevel;

    /**
     * 期限
     *
     * @var string
     * @required
     */
    private $duration;

    /**
     * 状态
     *
     * @var int
     * @required
     */
    private $status;

    /**
     * 期望收益
     *
     * @var string
     * @optional
     */
    private $expectedProfit = '';

    /**
     * 截止日期
     *
     * @var string
     * @optional
     */
    private $declareEndDay = '';

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return ProtoPurchaseFund
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
     * @return ProtoPurchaseFund
     */
    public function setName($name)
    {
        \Assert\Assertion::string($name);

        $this->name = $name;

        return $this;
    }
    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return ProtoPurchaseFund
     */
    public function setType($type)
    {
        \Assert\Assertion::string($type);

        $this->type = $type;

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
     * @return ProtoPurchaseFund
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
     * @return ProtoPurchaseFund
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
    public function getYesterdayProfit()
    {
        return $this->yesterdayProfit;
    }

    /**
     * @param float $yesterdayProfit
     * @return ProtoPurchaseFund
     */
    public function setYesterdayProfit($yesterdayProfit)
    {
        \Assert\Assertion::float($yesterdayProfit);

        $this->yesterdayProfit = $yesterdayProfit;

        return $this;
    }
    /**
     * @return float
     */
    public function getHoldShare()
    {
        return $this->holdShare;
    }

    /**
     * @param float $holdShare
     * @return ProtoPurchaseFund
     */
    public function setHoldShare($holdShare = 0)
    {
        $this->holdShare = $holdShare;

        return $this;
    }
    /**
     * @return float
     */
    public function getRedeemShare()
    {
        return $this->redeemShare;
    }

    /**
     * @param float $redeemShare
     * @return ProtoPurchaseFund
     */
    public function setRedeemShare($redeemShare = 0)
    {
        $this->redeemShare = $redeemShare;

        return $this;
    }
    /**
     * @return float
     */
    public function getPurchaseShare()
    {
        return $this->purchaseShare;
    }

    /**
     * @param float $purchaseShare
     * @return ProtoPurchaseFund
     */
    public function setPurchaseShare($purchaseShare = 0)
    {
        $this->purchaseShare = $purchaseShare;

        return $this;
    }
    /**
     * @return float
     */
    public function getUnliquidated()
    {
        return $this->unliquidated;
    }

    /**
     * @param float $unliquidated
     * @return ProtoPurchaseFund
     */
    public function setUnliquidated($unliquidated = 0)
    {
        $this->unliquidated = $unliquidated;

        return $this;
    }
    /**
     * @return float
     */
    public function getUsableRedeemShare()
    {
        return $this->usableRedeemShare;
    }

    /**
     * @param float $usableRedeemShare
     * @return ProtoPurchaseFund
     */
    public function setUsableRedeemShare($usableRedeemShare = 0)
    {
        $this->usableRedeemShare = $usableRedeemShare;

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
     * @return ProtoPurchaseFund
     */
    public function setRemainShare($remainShare = 0)
    {
        $this->remainShare = $remainShare;

        return $this;
    }
    /**
     * @return string
     */
    public function getManagerName()
    {
        return $this->managerName;
    }

    /**
     * @param string $managerName
     * @return ProtoPurchaseFund
     */
    public function setManagerName($managerName = '')
    {
        $this->managerName = $managerName;

        return $this;
    }
    /**
     * @return string
     */
    public function getCustodianName()
    {
        return $this->custodianName;
    }

    /**
     * @param string $custodianName
     * @return ProtoPurchaseFund
     */
    public function setCustodianName($custodianName = '')
    {
        $this->custodianName = $custodianName;

        return $this;
    }
    /**
     * @return float
     */
    public function getLeastPurchase()
    {
        return $this->leastPurchase;
    }

    /**
     * @param float $leastPurchase
     * @return ProtoPurchaseFund
     */
    public function setLeastPurchase($leastPurchase)
    {
        \Assert\Assertion::float($leastPurchase);

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
     * @return ProtoPurchaseFund
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
    public function getRiskLevel()
    {
        return $this->riskLevel;
    }

    /**
     * @param int $riskLevel
     * @return ProtoPurchaseFund
     */
    public function setRiskLevel($riskLevel)
    {
        \Assert\Assertion::integer($riskLevel);

        $this->riskLevel = $riskLevel;

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
     * @return ProtoPurchaseFund
     */
    public function setDuration($duration)
    {
        \Assert\Assertion::string($duration);

        $this->duration = $duration;

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
     * @return ProtoPurchaseFund
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
     * @return ProtoPurchaseFund
     */
    public function setExpectedProfit($expectedProfit = '')
    {
        $this->expectedProfit = $expectedProfit;

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
     * @return ProtoPurchaseFund
     */
    public function setDeclareEndDay($declareEndDay = '')
    {
        $this->declareEndDay = $declareEndDay;

        return $this;
    }

}