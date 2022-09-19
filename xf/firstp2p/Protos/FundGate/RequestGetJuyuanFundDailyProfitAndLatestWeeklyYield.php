<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获得最近recentDays天七日年化与万份收益
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class RequestGetJuyuanFundDailyProfitAndLatestWeeklyYield extends AbstractRequestBase
{
    /**
     * 基金编码
     *
     * @var string
     * @required
     */
    private $fundCode;

    /**
     * 最近天数
     *
     * @var int
     * @required
     */
    private $recentDays;

    /**
     * @return string
     */
    public function getFundCode()
    {
        return $this->fundCode;
    }

    /**
     * @param string $fundCode
     * @return RequestGetJuyuanFundDailyProfitAndLatestWeeklyYield
     */
    public function setFundCode($fundCode)
    {
        \Assert\Assertion::string($fundCode);

        $this->fundCode = $fundCode;

        return $this;
    }
    /**
     * @return int
     */
    public function getRecentDays()
    {
        return $this->recentDays;
    }

    /**
     * @param int $recentDays
     * @return RequestGetJuyuanFundDailyProfitAndLatestWeeklyYield
     */
    public function setRecentDays($recentDays)
    {
        \Assert\Assertion::integer($recentDays);

        $this->recentDays = $recentDays;

        return $this;
    }

}