<?php
namespace NCFGroup\Protos\Ptp;

use Assert\Assertion;
use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 绩效首页数据
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong@
 */
class ResponsePerformanceIndex extends ResponseBase
{
    /**
     * 今年投资金额
     *
     * @var float
     * @optional
     */
    private $totalMoney = 0;

    /**
     * 今年投资人数
     *
     * @var int
     * @optional
     */
    private $totalUsers = 0;

    /**
     * 年化总额
     *
     * @var float
     * @optional
     */
    private $avgTotalMoney = 0;

    /**
     * 今日佣金
     *
     * @var float
     * @optional
     */
    private $todayProfit = 0;

    /**
     * 最近60天佣金数据
     *
     * @var array
     * @optional
     */
    private $profitData = array (
);

    /**
     * @return float
     */
    public function getTotalMoney()
    {
        return $this->totalMoney;
    }

    /**
     * @param float $totalMoney
     * @return ResponsePerformanceIndex
     */
    public function setTotalMoney($totalMoney = 0)
    {
        $this->totalMoney = $totalMoney;

        return $this;
    }
    /**
     * @return int
     */
    public function getTotalUsers()
    {
        return $this->totalUsers;
    }

    /**
     * @param int $totalUsers
     * @return ResponsePerformanceIndex
     */
    public function setTotalUsers($totalUsers = 0)
    {
        $this->totalUsers = $totalUsers;

        return $this;
    }
    /**
     * @return float
     */
    public function getAvgTotalMoney()
    {
        return $this->avgTotalMoney;
    }

    /**
     * @param float $avgTotalMoney
     * @return ResponsePerformanceIndex
     */
    public function setAvgTotalMoney($avgTotalMoney = 0)
    {
        $this->avgTotalMoney = $avgTotalMoney;

        return $this;
    }
    /**
     * @return float
     */
    public function getTodayProfit()
    {
        return $this->todayProfit;
    }

    /**
     * @param float $todayProfit
     * @return ResponsePerformanceIndex
     */
    public function setTodayProfit($todayProfit = 0)
    {
        $this->todayProfit = $todayProfit;

        return $this;
    }
    /**
     * @return array
     */
    public function getProfitData()
    {
        return $this->profitData;
    }

    /**
     * @param array $profitData
     * @return ResponsePerformanceIndex
     */
    public function setProfitData(array $profitData = array (
))
    {
        $this->profitData = $profitData;

        return $this;
    }

}