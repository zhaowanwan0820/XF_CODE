<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 中国信贷注册统计
 *
 * 由代码生成器生成, 不可人为修改
 * @author yangqing
 */
class ResponseCreditRegCount extends ResponseBase
{
    /**
     * 总注册人数
     *
     * @var int
     * @required
     */
    private $totalRegCount;

    /**
     * 今年注册人数
     *
     * @var int
     * @required
     */
    private $yearRegCount;

    /**
     * 月注册人数
     *
     * @var int
     * @required
     */
    private $monthRegCount;

    /**
     * 日注册人数
     *
     * @var int
     * @required
     */
    private $dayRegCount;

    /**
     * @return int
     */
    public function getTotalRegCount()
    {
        return $this->totalRegCount;
    }

    /**
     * @param int $totalRegCount
     * @return ResponseCreditRegCount
     */
    public function setTotalRegCount($totalRegCount)
    {
        \Assert\Assertion::integer($totalRegCount);

        $this->totalRegCount = $totalRegCount;

        return $this;
    }
    /**
     * @return int
     */
    public function getYearRegCount()
    {
        return $this->yearRegCount;
    }

    /**
     * @param int $yearRegCount
     * @return ResponseCreditRegCount
     */
    public function setYearRegCount($yearRegCount)
    {
        \Assert\Assertion::integer($yearRegCount);

        $this->yearRegCount = $yearRegCount;

        return $this;
    }
    /**
     * @return int
     */
    public function getMonthRegCount()
    {
        return $this->monthRegCount;
    }

    /**
     * @param int $monthRegCount
     * @return ResponseCreditRegCount
     */
    public function setMonthRegCount($monthRegCount)
    {
        \Assert\Assertion::integer($monthRegCount);

        $this->monthRegCount = $monthRegCount;

        return $this;
    }
    /**
     * @return int
     */
    public function getDayRegCount()
    {
        return $this->dayRegCount;
    }

    /**
     * @param int $dayRegCount
     * @return ResponseCreditRegCount
     */
    public function setDayRegCount($dayRegCount)
    {
        \Assert\Assertion::integer($dayRegCount);

        $this->dayRegCount = $dayRegCount;

        return $this;
    }

}