<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 中国信贷投资统计
 *
 * 由代码生成器生成, 不可人为修改
 * @author yangqing
 */
class ResponseCreditDealCount extends ResponseBase
{
    /**
     * 总投资人数
     *
     * @var int
     * @required
     */
    private $totalDealCount;

    /**
     * 今年投资人数
     *
     * @var int
     * @required
     */
    private $yearDealCount;

    /**
     * 月投资人数
     *
     * @var int
     * @required
     */
    private $monthDealCount;

    /**
     * 日投资人数
     *
     * @var int
     * @required
     */
    private $dayDealCount;

    /**
     * @return int
     */
    public function getTotalDealCount()
    {
        return $this->totalDealCount;
    }

    /**
     * @param int $totalDealCount
     * @return ResponseCreditDealCount
     */
    public function setTotalDealCount($totalDealCount)
    {
        \Assert\Assertion::integer($totalDealCount);

        $this->totalDealCount = $totalDealCount;

        return $this;
    }
    /**
     * @return int
     */
    public function getYearDealCount()
    {
        return $this->yearDealCount;
    }

    /**
     * @param int $yearDealCount
     * @return ResponseCreditDealCount
     */
    public function setYearDealCount($yearDealCount)
    {
        \Assert\Assertion::integer($yearDealCount);

        $this->yearDealCount = $yearDealCount;

        return $this;
    }
    /**
     * @return int
     */
    public function getMonthDealCount()
    {
        return $this->monthDealCount;
    }

    /**
     * @param int $monthDealCount
     * @return ResponseCreditDealCount
     */
    public function setMonthDealCount($monthDealCount)
    {
        \Assert\Assertion::integer($monthDealCount);

        $this->monthDealCount = $monthDealCount;

        return $this;
    }
    /**
     * @return int
     */
    public function getDayDealCount()
    {
        return $this->dayDealCount;
    }

    /**
     * @param int $dayDealCount
     * @return ResponseCreditDealCount
     */
    public function setDayDealCount($dayDealCount)
    {
        \Assert\Assertion::integer($dayDealCount);

        $this->dayDealCount = $dayDealCount;

        return $this;
    }

}