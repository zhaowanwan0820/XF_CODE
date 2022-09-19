<?php
namespace NCFGroup\Protos\Ptp;

use Assert\Assertion;
use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 理财师绩效统计日投资客户数
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong@
 */
class ResponsePerformanceDaysStat extends ResponseBase
{
    /**
     * 今天投资客户数
     *
     * @var int
     * @required
     */
    private $todayInvest;

    /**
     * 总客户数
     *
     * @var int
     * @required
     */
    private $totalCustomers;

    /**
     * 近日投资客户数统计详情
     *
     * @var array
     * @optional
     */
    private $dayInvestNumDetail = array (
);

    /**
     * 理财师下投资分析详情
     *
     * @var array
     * @optional
     */
    private $moneyInvestDetail = array (
);

    /**
     * @return int
     */
    public function getTodayInvest()
    {
        return $this->todayInvest;
    }

    /**
     * @param int $todayInvest
     * @return ResponsePerformanceDaysStat
     */
    public function setTodayInvest($todayInvest)
    {
        \Assert\Assertion::integer($todayInvest);

        $this->todayInvest = $todayInvest;

        return $this;
    }
    /**
     * @return int
     */
    public function getTotalCustomers()
    {
        return $this->totalCustomers;
    }

    /**
     * @param int $totalCustomers
     * @return ResponsePerformanceDaysStat
     */
    public function setTotalCustomers($totalCustomers)
    {
        \Assert\Assertion::integer($totalCustomers);

        $this->totalCustomers = $totalCustomers;

        return $this;
    }
    /**
     * @return array
     */
    public function getDayInvestNumDetail()
    {
        return $this->dayInvestNumDetail;
    }

    /**
     * @param array $dayInvestNumDetail
     * @return ResponsePerformanceDaysStat
     */
    public function setDayInvestNumDetail(array $dayInvestNumDetail = array (
))
    {
        $this->dayInvestNumDetail = $dayInvestNumDetail;

        return $this;
    }
    /**
     * @return array
     */
    public function getMoneyInvestDetail()
    {
        return $this->moneyInvestDetail;
    }

    /**
     * @param array $moneyInvestDetail
     * @return ResponsePerformanceDaysStat
     */
    public function setMoneyInvestDetail(array $moneyInvestDetail = array (
))
    {
        $this->moneyInvestDetail = $moneyInvestDetail;

        return $this;
    }

}