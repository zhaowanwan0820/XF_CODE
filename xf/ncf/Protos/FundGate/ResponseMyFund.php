<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 响应盈信用户基金数据
 *
 * 由代码生成器生成, 不可人为修改
 * @author yangdongjie <yangdongjie@ucfgroup.com>
 */
class ResponseMyFund extends ResponseBase
{
    /**
     * 持有份额
     *
     * @var string
     * @required
     */
    private $holdingShares;

    /**
     * 当前持仓总收益(元)
     *
     * @var string
     * @required
     */
    private $totalProfit;

    /**
     * 日收益
     *
     * @var string
     * @required
     */
    private $lastDayProfit;

    /**
     * 日收益日期
     *
     * @var string
     * @required
     */
    private $profitDate;

    /**
     * 总资产
     *
     * @var string
     * @required
     */
    private $totalAssets;

    /**
     * 是否开通盈信账户
     *
     * @var int
     * @required
     */
    private $hasYxAccount;

    /**
     * 是否有宜投基金交易记录
     *
     * @var int
     * @required
     */
    private $hasYitRecords;

    /**
     * 用户评测等级
     *
     * @var int
     * @required
     */
    private $riskTolerance;

    /**
     * 用户评测等级
     *
     * @var string
     * @required
     */
    private $riskToleranceLevelName;

    /**
     * 我的资产url
     *
     * @var string
     * @required
     */
    private $myAssetUrl;

    /**
     * 交易记录url
     *
     * @var string
     * @required
     */
    private $tradeRecordUrl;

    /**
     * 风险测评url
     *
     * @var string
     * @required
     */
    private $riskUrl;

    /**
     * @return string
     */
    public function getHoldingShares()
    {
        return $this->holdingShares;
    }

    /**
     * @param string $holdingShares
     * @return ResponseMyFund
     */
    public function setHoldingShares($holdingShares)
    {
        \Assert\Assertion::string($holdingShares);

        $this->holdingShares = $holdingShares;

        return $this;
    }
    /**
     * @return string
     */
    public function getTotalProfit()
    {
        return $this->totalProfit;
    }

    /**
     * @param string $totalProfit
     * @return ResponseMyFund
     */
    public function setTotalProfit($totalProfit)
    {
        \Assert\Assertion::string($totalProfit);

        $this->totalProfit = $totalProfit;

        return $this;
    }
    /**
     * @return string
     */
    public function getLastDayProfit()
    {
        return $this->lastDayProfit;
    }

    /**
     * @param string $lastDayProfit
     * @return ResponseMyFund
     */
    public function setLastDayProfit($lastDayProfit)
    {
        \Assert\Assertion::string($lastDayProfit);

        $this->lastDayProfit = $lastDayProfit;

        return $this;
    }
    /**
     * @return string
     */
    public function getProfitDate()
    {
        return $this->profitDate;
    }

    /**
     * @param string $profitDate
     * @return ResponseMyFund
     */
    public function setProfitDate($profitDate)
    {
        \Assert\Assertion::string($profitDate);

        $this->profitDate = $profitDate;

        return $this;
    }
    /**
     * @return string
     */
    public function getTotalAssets()
    {
        return $this->totalAssets;
    }

    /**
     * @param string $totalAssets
     * @return ResponseMyFund
     */
    public function setTotalAssets($totalAssets)
    {
        \Assert\Assertion::string($totalAssets);

        $this->totalAssets = $totalAssets;

        return $this;
    }
    /**
     * @return int
     */
    public function getHasYxAccount()
    {
        return $this->hasYxAccount;
    }

    /**
     * @param int $hasYxAccount
     * @return ResponseMyFund
     */
    public function setHasYxAccount($hasYxAccount)
    {
        \Assert\Assertion::integer($hasYxAccount);

        $this->hasYxAccount = $hasYxAccount;

        return $this;
    }
    /**
     * @return int
     */
    public function getHasYitRecords()
    {
        return $this->hasYitRecords;
    }

    /**
     * @param int $hasYitRecords
     * @return ResponseMyFund
     */
    public function setHasYitRecords($hasYitRecords)
    {
        \Assert\Assertion::integer($hasYitRecords);

        $this->hasYitRecords = $hasYitRecords;

        return $this;
    }
    /**
     * @return int
     */
    public function getRiskTolerance()
    {
        return $this->riskTolerance;
    }

    /**
     * @param int $riskTolerance
     * @return ResponseMyFund
     */
    public function setRiskTolerance($riskTolerance)
    {
        \Assert\Assertion::integer($riskTolerance);

        $this->riskTolerance = $riskTolerance;

        return $this;
    }
    /**
     * @return string
     */
    public function getRiskToleranceLevelName()
    {
        return $this->riskToleranceLevelName;
    }

    /**
     * @param string $riskToleranceLevelName
     * @return ResponseMyFund
     */
    public function setRiskToleranceLevelName($riskToleranceLevelName)
    {
        \Assert\Assertion::string($riskToleranceLevelName);

        $this->riskToleranceLevelName = $riskToleranceLevelName;

        return $this;
    }
    /**
     * @return string
     */
    public function getMyAssetUrl()
    {
        return $this->myAssetUrl;
    }

    /**
     * @param string $myAssetUrl
     * @return ResponseMyFund
     */
    public function setMyAssetUrl($myAssetUrl)
    {
        \Assert\Assertion::string($myAssetUrl);

        $this->myAssetUrl = $myAssetUrl;

        return $this;
    }
    /**
     * @return string
     */
    public function getTradeRecordUrl()
    {
        return $this->tradeRecordUrl;
    }

    /**
     * @param string $tradeRecordUrl
     * @return ResponseMyFund
     */
    public function setTradeRecordUrl($tradeRecordUrl)
    {
        \Assert\Assertion::string($tradeRecordUrl);

        $this->tradeRecordUrl = $tradeRecordUrl;

        return $this;
    }
    /**
     * @return string
     */
    public function getRiskUrl()
    {
        return $this->riskUrl;
    }

    /**
     * @param string $riskUrl
     * @return ResponseMyFund
     */
    public function setRiskUrl($riskUrl)
    {
        \Assert\Assertion::string($riskUrl);

        $this->riskUrl = $riskUrl;

        return $this;
    }

}